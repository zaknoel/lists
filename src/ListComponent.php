<?php

namespace Zak\Lists;

use Artisan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;
use Yajra\DataTables\DataTables;
use Zak\Lists\Fields\Field;

class ListComponent
{
    public static function listHandler(Request $request, string $list)
    {
        $isAjax = self::isAjax($request);
        $component = self::getComponent($list, true);
        if (! $component->userCanViewAny()) {
            abort(403);
        }
        $isExcel = $request->get('excel', 'N') === 'Y';
        $fields = self::filterFields($component, 'show_in_index');
        $curSort = $component->options->value['curSort'] ?? ['id', 'desc'];
        if ($isAjax || $isExcel) {
            if ($request->has('order')) {
                $orderCol = $request->get('order')[0] ?? [];
                if ($orderCol) {
                    $colName = $request->get('columns')[$orderCol['column']]['name'];
                    $dir = $orderCol['dir'] ?? 'asc';
                    $dd = $component->options->value;
                    $dd['curSort'] = [$colName, $dir];
                    $component->options->value = $dd;
                    $component->options->save();
                    $curSort = $component->options->value['curSort'] ?? ['id', 'desc'];
                }
            }
            if ($request->has('length')) {
                $dd = $component->options->value;
                $dd['length'] = $request->get('length');
                $component->options->value = $dd;
                $component->options->save();
            }

            $length = $component->options->value['length'] ?? 25;
            request()?->merge(['length' => $length]);
            $dm = $component->getQuery();

            $component->eventOnIndexQuery($dm);

            Arr::map($fields, static function (Field $field) use ($dm) {
                $field->generateFilter($dm);
            });
            $data = DataTables::of($dm);
            $data->order(function ($query) use ($curSort) {
                $query->orderBy($curSort[0], $curSort[1]);
            });

            $columns = Arr::map($fields, static function ($item) {
                return $item->attribute;
            });
            if ($component->getActions()) {
                $columns[] = 'action';
            }
            $data->only($columns);
            $defaultAction = Arr::first($component->getActions(), function (Action $action) {
                return $action->default;
            });

            Arr::map($fields, static function (Field $field) use ($data, $defaultAction, $list) {

                $data->editColumn($field->attribute,
                    fn ($item) => $field->item($item)->showIndex($item, $list, $defaultAction));
            });
            $data->rawColumns($columns);
            if ($component->getActions()) {
                $view=$component->customViews['actions'] ?? 'lists::actions';
                $data->addColumn('action', fn ($item) => view($view,
                    ['item' => $item, 'actions' => $component->getFilteredActions($item), 'list' => $list]));
            }
            if ($isAjax) {
                return $data->make(true);
            }

            if ($isExcel) {
                try {
                    return Excel::download(new ListImport($data->toArray(), $fields), $list.'.xlsx');
                } catch (Throwable $e) {
                    report('Zak.Lists.Error:'.$e->getMessage()."\n".$e->getFile()."\n".$e->getLine());
                }

            }

        }
        $length = $component->options->value['length'] ?? 25;

        $index = array_search($curSort[0], array_column($fields, 'attribute'), true);
        $curSort[2] = $index;
        $filters = [];
        foreach ($component->getFields() as $field) {
            if ($field->filterable && in_array($field->attribute, $component->options->value['filters'], true)) {
                $field->generateFilter();
                $filters[] = $field;
            }
        }
        $view = $component->customViews['index'] ?? 'lists::index';
        return view($view, [
            'length' => $length,
            'curSort' => $curSort,
            'component' => $component,
            'fields' => $fields,
            'list' => $list,
            'filters' => $filters,
        ]);
    }

    public static function isAjax($request): bool
    {
        return $request->ajax() && $request->header('X-Requested-With') === 'XMLHttpRequest';
    }

    private static function getComponent(string $list, $is_index = false): Component
    {
        $file = (config('lists.path') ?? app_path('/Lists/')).$list.'.php';
        if (! file_exists($file)) {
            Artisan::call('zak:component', ['name' => $list]);
            abort(404, 'Component not found: '.$list.'. Auto generated at '.$file);
        }
        $data = (include $file) ?? null;
        if (! $data instanceof Component) {
            abort(404, 'Component not configured properly: '.$list);
        }
        $component = $data;
        if (($component->options->value['sort'] ?? []) && $is_index) {
            $fields = [];
            foreach ($component->options->value['sort'] as $col) {
                $first = Arr::where($component->getFields(), fn ($item) => $item->attribute === $col);
                if($first){
                    $fields[] = reset($first);
                }
            }
            if (count($fields) !== count($component->getFields())) {
                foreach ($component->getFields() as $field) {
                    if (! in_array($field, $fields, true)) {
                        $fields[] = $field;
                    }
                }
            }
            $component->setFields($fields);
        }

        return $component;
    }

    private static function filterFields(Component $component, string $string)
    {
        return Arr::where($component->getFields(), static function (Field $field) use ($component, $string) {
            return $field->{$string} && (! $component->options->value['columns'] || in_array($field->attribute,
                $component->options->value['columns'], false));
        });
    }

    private static function save($item, array $fields, Request $request, $component)
    {
        $rules = [];
        $params = [];
        $fields = Arr::map($fields, static function (Field $field) use (&$params, &$rules, $item) {

            $rules = array_merge($rules, $field->getRules($item));
            $params = array_merge($params, $field->getRuleParams());

            return $field;
        });
        $data = $request->validate($rules, $params);
        $item = $item ?? new ($component->getModel());
        try {
            Arr::map($fields, static function (Field $field) use ($data, $item) {
                $field->saveValue($item, $data);
            });
            $component->eventOnBeforeSave($item);
            $item->save();
            $component->eventOnAfterSave($item);

        } catch (Throwable $e) {
            report('Zak.Lists.Error:'.$e->getMessage()."\n".$e->getFile()."\n".$e->getLine());
            // Throw the exception
            throw ValidationException::withMessages([
                'custom_field' => $e->getMessage(),
            ]);

        }

        return $item;
    }

    public static function detailHandler(Request $request, string $list, int $item)
    {
        $component = self::getComponent($list);
        $query = $component->getQuery();
        $component->eventOnDetailQuery($query);
        $item = $query->where('id', $item)->firstOrFail();
        if (! $component->userCanView($item)) {
            abort(403);
        }
        $fields = $component->getFilteredFields(fn (Field $item) => $item->show_in_detail);
        $fields = Arr::map($fields, static function (Field $field) use ($item) {
            $field->item($item);
            return $field;
        });
        $view=$component->customViews['detail'] ?? 'lists::detail';


        return view($view,
            [
                'pages' => $component->getPages(),
                'component' => $component,
                'item' => $item,
                'list' => $list,
                'fields' => $fields,
            ]);
    }

    public static function editFormHandler(Request $request, string $list, int $item)
    {
        $component = self::getComponent($list);
        $query = $component->getQuery();
        $component->eventOnEditQuery($query);
        $item = $query->where('id', $item)->firstOrFail();
        if (! $component->userCanEdit($item)) {
            abort(403);
        }

        $fields = Arr::where($component->getFields(), static function (Field $field) {
            return $field->show_on_update;
        });
        $fields = Arr::map($fields, static function (Field $field) use ($item) {
            $field->item($item)->showEdit();

            return $field;
        });
        $view=$component->customViews['form'] ?? 'lists::form';
        return view($view,
            [
                'item' => $item,
                'scripts' => $component->scripts(),
                'fields' => $fields,
                'list' => $list,
                'title' => 'Редактировать '.$component->getSingleLabel(),
                'pageTitle' => 'Редактировать '.$component->getSingleLabel(),
                'frame' => $request->get('frame', 0),
            ]
        );
    }

    public static function editSaveHandler(Request $request, string $list, int $item)
    {
        $component = self::getComponent($list);
        $query = $component->getQuery();
        $component->eventOnEditQuery($query);
        $item = $query->where('id', $item)->firstOrFail();
        if (! $component->userCanEdit($item)) {
            abort(403);
        }

        $fields = Arr::where($component->getFields(), static function (Field $field) {
            return $field->show_on_update;
        });
        $item = self::save($item, $fields, $request, $component);
        if ($request->get('frame', 0)) {
            $view=$component->customViews['success'] ?? 'lists::success';
            return view($view, ['item' => $item]);
        }

        return Redirect::route('lists_detail', ['list' => $list, 'item' => $item])->with('js_success',
            'Успешно обновлено!');
    }

    public static function addFormHandler(Request $request, string $list)
    {
        $component = self::getComponent($list);
        if (! $component->userCanAdd()) {
            abort(403);
        }

        $item = new ($component->getModel());
        if ($request->has('copy_from')) {
            $copy = $component->getQuery()->where('id', $request->get('copy_from'))->first();
            if ($copy) {
                $item = $copy;
                unset($item->id);
            }
        }

        $fields = Arr::where($component->getFields(), static function (Field $field) {
            return $field->show_on_add;
        });
        $fields = Arr::map($fields, static function (Field $field) use ($item) {
            $field->item($item)->showEdit();

            return $field;
        });
        $view=$component->customViews['form'] ?? 'lists::form';
        return view($view,
            [
                'item' => $item,
                'scripts' => $component->scripts(),
                'fields' => $fields,
                'list' => $list,
                'title' => 'Добавить новый '.$component->getSingleLabel(),
                'pageTitle' => 'Новый '.$component->getSingleLabel(),
                'frame' => $request->get('frame', 0),
            ]
        );
    }

    public static function addSaveHandler(Request $request, string $list)
    {

        $component = self::getComponent($list);
        if (! $component->userCanAdd()) {
            abort(403);
        }

        $fields = Arr::where($component->getFields(), static function (Field $field) {
            return $field->show_on_add;
        });
        $item = self::save(null, $fields, $request, $component);
        if ($request->get('frame', 0)) {
            $view=$component->customViews['success'] ?? 'lists::success';
            return view($view, ['item' => $item]);
        }

        return Redirect::route('lists', $list)->with('js_success', 'Успешно добавлен');
    }

    public static function deleteHandler(Request $request, string $list, int $item)
    {
        $component = self::getComponent($list);
        $item = $component->getQuery()->where('id', $item)->firstOrFail();
        /** @var Model $item */
        if (! $component->userCanDelete($item)) {
            abort(403);
        }

        $component->eventOnBeforeDelete($item);
        $item->deleteOrFail();
        $component->eventOnAfterDelete($item);

        return back()->with('js_success', 'Элемент удалена успешно !');
    }

    public static function optionHandler(Request $request, string $list)
    {

        $component = self::getComponent($list);
        $data = $request->validate([
            'columns' => ['array', 'nullable'],
            'sort' => ['array', 'nullable'],
            'filters' => ['array', 'nullable'],
        ]);
        $data['columns'] = array_keys($data['columns'] ?? []);
        $data['filters'] = array_keys($data['filters'] ?? []);
        $data['sort'] = $data['sort'] ?? [];
        $data = array_merge($component->options->value, $data);
        $component->options->value = $data;
        $component->options->save();

        return back()->with('js_success', 'Настройки сохранены!');
    }

    public static function pagesHandler(Request $request, string $list, int $item, string $page)
    {
        $component = self::getComponent($list);
        $query = $component->getQuery();
        $component->eventOnDetailQuery($query);
        $item = $query->where('id', $item)->firstOrFail();
        if (! $component->userCanView($item)) {
            abort(403);
        }
        $curPage = $component->getPages()[$page] ?? null;
        if (! $curPage) {
            abort(404);
        }
        if (is_callable($curPage['view'])) {
            $view = call_user_func($curPage['view'], $item);
        } else {
            $view = '';
        }
        $detail_view=$component->customViews['detail'] ?? 'lists::detail';
        return view($detail_view,
            [
                'component' => $component,
                'view' => $view,
                'pages' => $component->getPages(),
                'item' => $item,
                'list' => $list,
                'fields' => [],
                'page' => $page,
            ]);
    }
}
