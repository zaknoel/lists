<?php

namespace Zak\Lists;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;
use Yajra\DataTables\DataTables;
use Zak\Lists\Fields\BelongToMany;
use Zak\Lists\Fields\Field;

class ListComponent
{
    public static function listHandler(Request $request, string $list)
    {
        $isAjax = $request->ajax() && $request->header('X-Requested-With') === 'XMLHttpRequest';
        $component = self::getComponent($list);
        $isExcel = $request->get('excel', "N") === "Y";
        if ($component->options->value["sort"] ?? []) {
            $fields = [];
            foreach ($component->options->value["sort"] as $col) {
                $first = Arr::where($component->fields, fn($item) => $item->attribute === $col);
                $fields[] = reset($first);
            }
            if (count($fields) !== count($component->fields)) {
                foreach ($component->fields as $field) {
                    if (!in_array($field, $fields, true)) {
                        $fields[] = $field;
                    }
                }
            }
            $component->fields = $fields;
        }


        $fields = Arr::where($component->fields, static function (Field $field) use ($component) {
            return $field->show_in_index && (!$component->options->value["columns"] || in_array($field->attribute,
                        $component->options->value["columns"], false));
        });

        $curSort = $component->options->value['curSort'] ?? ['id', "desc"];
        if ($isAjax || $isExcel) {
            if ($request->has('order')) {
                $orderCol = $request->get('order')[0] ?? [];
                if ($orderCol) {
                    $colName = $request->get('columns')[$orderCol['column']]["name"];
                    $dir = $orderCol['dir'] ?? "asc";
                    $dd = $component->options->value;
                    $dd["curSort"] = [$colName, $dir];
                    $component->options->value = $dd;
                    $component->options->save();
                    $curSort = $component->options->value['curSort'] ?? ['id', "desc"];
                }
            }
            if ($request->has('length')) {
                $dd = $component->options->value;
                $dd["length"] = $request->get('length');
                $component->options->value = $dd;
                $component->options->save();
            }

            $length = $component->options->value["length"] ?? 25;
            request()?->merge(['length' => $length]);

            $dm = $component->onList($component->getModel());
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
            if ($component->actions) {
                $columns[] = "action";
            }
            $data->only($columns);
            $defaultAction = Arr::first($component->actions, function (Action $action) {
                return $action->default;
            });

            Arr::map($fields, static function (Field $field) use ($data, $defaultAction, $list) {

                $data->editColumn($field->attribute,
                    fn($item) => $field->item($item)->showIndex($item, $list, $defaultAction));
            });
            $data->rawColumns($columns);
            if ($component->actions) {
                $data->addColumn("action", fn($item) => view('lists::actions',

                    ["item" => $item, "actions" => $component->getActions($item), "list" => $list]));
            }
            if ($isAjax) {
                return $data->make(true);
            }

            if ($isExcel) {
                try {
                    return Excel::download(new ListImport($data->toArray(), $fields), $list.'.xlsx');
                } catch (Throwable $e) {
                    report("Zak.Lists.Error:".$e->getMessage()."\n".$e->getFile()."\n".$e->getLine());
                }

            }

        }
        $length = $component->options->value["length"] ?? 25;

        $index = array_search($curSort[0], array_column($fields, 'attribute'), true);
        $curSort[2] = $index;
        $filters = [];
        $dm = $component->getModel();
        foreach ($component->fields as $field) {
            if ($field->filterable && in_array($field->attribute, $component->options->value["filters"], true)) {
                $field->generateFilter($dm);
                $filters[] = $field;
            }
        }

        return view("lists::list", [
            "length" => $length,
            "curSort" => $curSort,
            "component" => $component,
            "fields" => $fields,
            "list" => $list,
            "filters" => $filters
        ]);
    }

    private static function getComponent(string $list): Component
    {
        $file = (config('lists.path') ?? app_path("/Lists/")).$list.".php";
        if (!file_exists($file)) {
            abort(404);
        }
        $data = (include $file) ?? null;
        if (!$data) {
            abort(404);
        }
        return $data;
    }

    public static function detailHandler(Request $request, string $list, int $item)
    {
        $component = self::getComponent($list);
        $item = $component->onDetail($component->getModel()->where('id', $item)->firstOrFail());
        $fields = Arr::where($component->fields, static function (Field $field) {
            return $field->show_in_detail;
        });
        $fields = Arr::map($fields, static function (Field $field) use ($item) {
            if (array_key_exists($field->attribute, $item->getAttributes())) {
                $field->item($item)->fillValue($item->{$field->attribute});
            } elseif ($field instanceof BelongToMany) {
                $field->item($item)->fillValue($item->{$field->attribute});
            }
            return $field;
        });
        return view('lists::detail',
            [
                "pages" => $component->pages,
                'component' => $component,
                "item" => $item,
                "list" => $list,
                "fields" => $fields
            ]);
    }

    public static function editFormHandler(Request $request, string $list, int $item)
    {

        $component = self::getComponent($list);
        $item = $component->onDetail($component->getModel()->where('id', $item)->firstOrFail());
        $fields = Arr::where($component->fields, static function (Field $field) {
            return $field->show_on_update;
        });
        $fields = Arr::map($fields, static function (Field $field) use ($item) {
            if (array_key_exists($field->attribute, $item->getAttributes())) {
                $field->item($item)->fillValue($item->{$field->attribute});
            } elseif ($field instanceof BelongToMany) {
                $field->item($item)->fillValue($item->{$field->attribute});
            }
            return $field;
        });


        return view("lists::form",
            [
                "item" => $item,
                "scripts" => $component->scripts(),
                "fields" => $fields,
                "list" => $list,
                "title" => "Редактировать ".$component->getSingleLabel(),
                "pageTitle" => "Редактировать ".$component->getSingleLabel()
            ]
        );
    }

    public static function editSaveHandler(Request $request, string $list, int $item)
    {
        $component = self::getComponent($list);
        $item = $component->onDetail($component->getModel()->where('id', $item)->firstOrFail());
        $rules = [];
        $params = [];
        $fields = Arr::where($component->fields, static function (Field $field) {
            return $field->show_on_update;
        });
        $fields = Arr::map($fields, static function (Field $field) use (&$params, &$rules, $item) {

            $rules = array_merge($rules, $field->getRules($item));
            $params = array_merge($params, $field->getRuleParams());
            if (array_key_exists($field->attribute, $item->getAttributes())) {
                $field->item($item)->fillValue($item->{$field->attribute});
            } elseif ($field instanceof BelongToMany) {
                $field->item($item)->fillValue($item->{$field->attribute});
            }
            return $field;

        });
        $data = $request->validate($rules, $params);
        $delete = $request->get('delete') ?? [];
        try {
            Arr::map($fields, static function (Field $field) use ($data, $delete, $item) {
                if ($field->show_on_update && !$field->virtual && array_key_exists($field->attribute, $data)) {
                    $value = is_array($data[$field->attribute])

                        ? $field->arrayValue(Arr::map($data[$field->attribute],
                            static fn($_item) => $field->beforeSave($_item)))

                        : $field->beforeSave($data[$field->attribute]);

                    $field->saveValue($item, $value);

                } elseif (array_key_exists($field->attribute, $delete)) {
                    $field->beforeSave("");
                    $field->saveValue($item, "");
                }
            });

            $component->onBeforeSave($item);
            $item->save();
            $component->onAfterSave($item);
        } catch (Throwable $e) {
            report("Zak.Lists.Error:".$e->getMessage()."\n".$e->getFile()."\n".$e->getLine());
            // Throw the exception
            throw ValidationException::withMessages([
                'custom_field' => $e->getMessage(),
            ]);

        }

        return Redirect::route('lists_detail', ["list" => $list, "item" => $item])->with('js_success',
            "Успешно обновлено!");
    }

    public static function addFormHandler(Request $request, string $list)
    {
        $component = self::getComponent($list);
        $item = $component->onModel(new $component->model);
        if ($request->has('copy_from')) {
            $copy = $component->getModel()->where('id', $request->get('copy_from'))->first();
            if ($copy) {
                $item = $copy;
                unset($item->id);
            }
        }

        $fields = Arr::where($component->fields, static function (Field $field) {
            return $field->show_on_add;
        });
        $fields = Arr::map($fields, static function (Field $field) use ($item) {
            if (array_key_exists($field->attribute, $item->getAttributes())) {
                $field->item($item)->fillValue($item->{$field->attribute});
            } elseif ($field instanceof BelongToMany) {
                $field->item($item)->fillValue($item->{$field->attribute});
            }
            return $field;
        });

        return view("lists::form",
            [
                "item" => $item,
                "scripts" => $component->scripts(),
                "fields" => $fields,
                "list" => $list,
                "title" => "Добавить новый ".$component->getSingleLabel(),
                "pageTitle" => "Новый ".$component->getSingleLabel()
            ]
        );
    }

    public static function addSaveHandler(Request $request, string $list)
    {
        $component = self::getComponent($list);
        $item = $component->onModel(new $component->model);
        $rules = [];
        $params = [];
        Arr::map($component->fields, static function (Field $field) use (&$params, &$rules) {
            if ($field->show_on_add) {
                $rules = array_merge($rules, $field->getRules());
                $params = array_merge($params, $field->getRuleParams());
            }
        });
        //dd($request->all(), $rules);
        $data = $request->validate($rules, $params);
        try {


            Arr::map($component->fields, static function (Field $field) use ($data, $item) {
                if ($field->show_on_add && !$field->virtual && array_key_exists($field->attribute, $data)) {
                    $value = is_array($data[$field->attribute])

                        ? $field->arrayValue(Arr::map($data[$field->attribute],
                            static fn($item) => $field->beforeSave($item)))

                        : $field->beforeSave($data[$field->attribute]);

                    $field->saveValue($item, $value);
                }
            });
            $component->onBeforeSave($item);
            $item->save();
            $component->onAfterSave($item);
        } catch (Throwable $e) {
            if ($item->id) {
                $item->delete();
            }
            //dd($e);
            // Throw the exception
            throw ValidationException::withMessages([
                'custom_field' => $e->getMessage(),
            ]);
        }

        return Redirect::route('lists', $list)->with('js_success', "Успешно добавлен");
    }

    public static function deleteHandler(Request $request, string $list, int $item)
    {
        $component = self::getComponent($list);
        $item = $component->getModel()->where('id', $item)->firstOrFail();
        /**@var Model $item */
        $component->OnBeforeDelete($item);
        $item->deleteOrFail();
        $component->OnAfterDelete($item);
        return Redirect::route('lists', $list)->with('js_success', "Элемент удалена успешно !");
    }

    public static function optionHandler(Request $request, string $list)
    {
        $component = self::getComponent($list);
        $data = $request->validate([
            "columns" => ["array", "nullable"],
            "sort" => ["array", "nullable"],
            "filters" => ["array", "nullable"],
        ]);
        $data["columns"] = array_keys($data["columns"] ?? []);
        $data["filters"] = array_keys($data["filters"] ?? []);
        $data["sort"] = $data["sort"] ?? [];
        $data = array_merge($component->options->value, $data);
        $component->options->value = $data;
        $component->options->save();
        return Redirect::route('lists', $list)->with('js_success', "Настройки сохранены!");
    }

    public static function pagesHandler(Request $request, string $list, int $item, string $page)
    {
        $component = self::getComponent($list);
        $item = $component->onDetail($component->model::findOrFail($item));
        $curPage = $component->pages[$page];
        if (!$curPage) {
            abort(404);
        }
        if (is_callable($curPage["view"])) {
            $view = call_user_func_array($curPage["view"], [$item]);
        } else {
            $view = "";
        }

        return view('lists::detail',
            [
                'component' => $component,
                "view" => $view,
                "pages" => $component->pages,
                "item" => $item,
                "list" => $list,
                "fields" => [],
                "page" => $page
            ]);
    }


}
