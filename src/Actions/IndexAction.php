<?php

namespace Zak\Lists\Actions;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;
use Yajra\DataTables\DataTables;
use Yajra\DataTables\EloquentDataTable;
use Zak\Lists\Action;
use Zak\Lists\Component;
use Zak\Lists\Contracts\AuthorizationContract;
use Zak\Lists\Contracts\ComponentLoaderContract;
use Zak\Lists\Fields\Field;
use Zak\Lists\Jobs\ExportListJob;
use Zak\Lists\Models\ListExport;
use Zak\Lists\Services\ExportService;
use Zak\Lists\Services\QueryService;

/**
 * Обрабатывает отображение списка: HTML-страница, AJAX-данные для DataTables, экспорт в Excel.
 */
class IndexAction
{
    public function __construct(
        private readonly ComponentLoaderContract $loader,
        private readonly QueryService $queryService,
        private readonly AuthorizationContract $authService,
        private readonly ExportService $exportService,
    ) {}

    /**
     * @return View|JsonResponse|BinaryFileResponse
     */
    public function handle(Request $request, string $list): mixed
    {
        $component = $this->loader->resolve($list, true);
        $this->authService->ensureCanViewAny($component);

        $isAjax = $this->isAjaxRequest($request);
        $isExport = $request->get('excel') === 'Y';

        $visibleFields = $this->getVisibleFields($component, $isExport);

        if ($isAjax || $isExport) {
            return $this->handleDataRequest($request, $component, $list, $visibleFields, $isAjax, $isExport);
        }

        return $this->renderIndexView($request, $component, $list, $visibleFields);
    }

    /**
     * Обрабатывает AJAX-запрос DataTables или запрос на экспорт.
     *
     * @param  array<int, Field>  $fields
     * @return JsonResponse|BinaryFileResponse
     */
    private function handleDataRequest(
        Request $request,
        Component $component,
        string $list,
        array $fields,
        bool $isAjax,
        bool $isExport,
    ): mixed {
        $this->updateSortPreference($request, $component);
        $this->updateLengthPreference($request, $component);

        $curSort = $component->options->value['curSort'] ?? [];
        $curSort = (is_array($curSort) && count($curSort) >= 2) ? $curSort : ['id', 'desc'];
        $length = $this->normalizeLength($component->options->value['length'] ?? config('lists.default_length', 25));
        $request->merge(['length' => $length]);

        $query = $this->queryService->buildIndexQuery($component, $request);

        foreach ($fields as $field) {
            $field->generateFilter($query);
        }

        if ($isExport) {
            $exportQuery = clone $query;
            $exportQuery->orderBy($curSort[0], $curSort[1]);

            return $this->handleExport($component, $exportQuery, $fields, $list, $request);
        }

        // SQL Server forbids ORDER BY in subqueries without TOP/OFFSET.
        // Sorting for DataTables AJAX requests is handled by yajra from request order[] params.
        // The default sort column/direction is driven by curSort passed to the DataTables JS instance.
        /** @var EloquentDataTable $datatable */
        $datatable = DataTables::of($query);

        $columns = array_map(fn (Field $f) => $f->attribute, $fields);

        if ($component->getActions()) {
            $columns[] = 'action';
        }

        if ($component->bulkActions) {
            $columns[] = 'bulk_action_checkbox';
        }

        $datatable->only($columns);

        $defaultAction = Arr::first($component->getActions(), fn (Action $a) => $a->default);

        foreach ($fields as $field) {
            $datatable->editColumn(
                $field->attribute,
                fn ($item) => $field->item($item)->showIndex($item, $list, $defaultAction)
            );
        }

        $datatable->rawColumns($columns);

        if ($component->getActions()) {
            $actionsView = $component->customViews['actions'] ?? 'lists::actions';
            $datatable->addColumn('action', fn ($item) => view($actionsView, [
                'item' => $item,
                'actions' => $component->getFilteredActions($item),
                'list' => $list,
            ]));
        }

        if ($component->bulkActions) {
            $datatable->addColumn(
                'bulk_action_checkbox',
                fn ($item) => '<input type="checkbox"
                    x-on:click="$(\'#select-all-bulk\').prop(\'checked\', false);"
                    x-model="bulk_action"
                    name="bulk-action-checkbox"
                    class="bulk-action-checkbox"
                    value="'.$item->id.'"/>'
            );
        }

        if ($isAjax) {
            try {
                return $datatable->make(true);
            } catch (Throwable $e) {
                info('Zak.Lists.IndexAction SQL: '.$query->toRawSql());

                if (isReportable($e)) {
                    report('Zak.Lists.IndexAction: '.$e->getMessage());
                }

                throw $e;
            }
        }

        throw new \LogicException('Export flow should be handled before DataTables rendering.');
    }

    /**
     * Применяет лимит строк и стратегию async перед синхронной выгрузкой.
     *
     * @param  array<int, Field>  $fields
     */
    private function handleExport(
        Component $component,
        Builder $query,
        array $fields,
        string $list,
        Request $request,
    ): BinaryFileResponse|RedirectResponse {
        $count = $this->exportService->countRows($query);

        // Жёсткий лимит: если строк слишком много — отклоняем сразу.
        if ($this->exportService->exceedsExportLimitByCount($count)) {
            $max = (int) config('lists.max_export_rows', 50000);

            return back()->with('js_error', __('lists.errors.export_limit_exceeded', [
                'count' => number_format($count),
                'max' => number_format($max),
            ]));
        }

        // Мягкий порог: если строк больше порога — ставим в очередь.
        if ($this->exportService->shouldQueueExportByCount($count)) {
            $userId = (int) auth()->id();

            // Create the tracking record before dispatch so the user sees 'pending' immediately.
            $export = ListExport::create([
                'user_id' => $userId,
                'list' => $list,
                'filename' => $list,
                'status' => ListExport::STATUS_PENDING,
                'disk' => config('lists.export_disk', 'local'),
            ]);

            ExportListJob::dispatch($list, $request->all(), $userId, $list, $export->id);

            return back()->with('js_success', __('lists.export.queued_rows', [
                'count' => number_format($count),
            ]));
        }

        return $this->exportService->downloadQuerySafe(
            $component,
            $query,
            $fields,
            $list,
            $list,
            $query->toRawSql(),
            (int) config('lists.export_chunk_size', 500),
        );
    }

    /**
     * Рендерит HTML-страницу со списком.
     *
     * @param  array<int, Field>  $fields
     * @return View
     */
    private function renderIndexView(Request $request, Component $component, string $list, array $fields): mixed
    {
        $curSort = $component->options->value['curSort'] ?? [];
        $curSort = (is_array($curSort) && count($curSort) >= 2) ? $curSort : ['id', 'desc'];
        $length = $this->normalizeLength($component->options->value['length'] ?? config('lists.default_length', 25));

        $sortColumnIndex = (int) array_search($curSort[0], array_column($fields, 'attribute'), true);
        $curSort[2] = $sortColumnIndex;

        $filters = $this->resolveActiveFilters($component);

        $view = $component->customViews['index'] ?? 'lists::list';

        return view($view, [
            'length' => $length,
            'curSort' => $curSort,
            'component' => $component,
            'fields' => $fields,
            'list' => $list,
            'filters' => $filters,
        ]);
    }

    /**
     * Возвращает поля, видимые на странице списка.
     *
     * @return array<int, Field>
     */
    private function getVisibleFields(Component $component, bool $forExport = false): array
    {
        $visibleColumns = $component->options->value['columns'] ?? [];

        $fields = array_filter(
            $component->getFields(),
            fn (Field $field) => $field->show_in_index
                && (empty($visibleColumns) || in_array($field->attribute, $visibleColumns, false))
        );

        if ($forExport) {
            $fields = array_filter($fields, fn (Field $field) => ! $field->hide_on_export);
        }

        return array_values($fields);
    }

    /**
     * Возвращает активные фильтры (отмеченные пользователем в настройках).
     *
     * @return array<int, Field>
     */
    private function resolveActiveFilters(Component $component): array
    {
        $savedFilters = $component->options->value['filters'] ?? [];
        $filters = [];

        foreach ($component->getFields() as $field) {
            if ($field->filterable && in_array($field->attribute, $savedFilters, true)) {
                $field->generateFilter();
                $filters[] = $field;
            }
        }

        return $filters;
    }

    /**
     * Сохраняет предпочтение сортировки пользователя.
     */
    private function updateSortPreference(Request $request, Component $component): void
    {
        if (! $request->has('order')) {
            return;
        }

        $orderCol = $request->get('order')[0] ?? [];

        if (! $orderCol) {
            return;
        }

        $colName = $request->get('columns')[$orderCol['column']]['name'] ?? null;
        $dir = $orderCol['dir'] ?? 'asc';

        if ($colName) {
            $options = $component->options->value;
            $newSort = [$colName, $dir];
            $currentSort = $options['curSort'] ?? null;

            if ($currentSort !== $newSort) {
                $options['curSort'] = $newSort;
                $component->options->value = $options;
                $component->options->save();
            }
        }
    }

    /**
     * Сохраняет предпочтение количества строк на странице.
     */
    private function updateLengthPreference(Request $request, Component $component): void
    {
        if (! $request->has('length')) {
            return;
        }

        $options = $component->options->value;
        $newLength = $this->normalizeLength((int) $request->get('length'));
        $currentLength = (int) ($options['length'] ?? 0);

        if ($currentLength !== $newLength) {
            $options['length'] = $newLength;
            $component->options->value = $options;
            $component->options->save();
        }
    }

    private function normalizeLength(mixed $length): int
    {
        $default = max(1, (int) config('lists.default_length', 25));
        $max = max($default, (int) config('lists.max_length', 250));
        $length = (int) $length;

        if ($length <= 0) {
            return $default;
        }

        return min($length, $max);
    }

    private function isAjaxRequest(Request $request): bool
    {
        return $request->ajax() && $request->header('X-Requested-With') === 'XMLHttpRequest';
    }
}
