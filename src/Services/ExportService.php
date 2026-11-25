<?php

namespace Zak\Lists\Services;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;
use Zak\Lists\Component;
use Zak\Lists\Fields\Field;
use Zak\Lists\ListImport;

/**
 * Генерирует Excel-файлы для экспорта данных таблицы.
 */
class ExportService
{
    /**
     * Request keys that belong to DataTables / transport and must not be treated as list filters.
     *
     * @var array<int, string>
     */
    public const NON_FILTER_KEYS = ['excel', 'order', 'length', 'start', 'draw', 'search', 'columns', '_token', 'format', 'query'];

    /**
     * Fields that can be exported: shown in the list and not hidden for export.
     *
     * @return array<int, Field>
     */
    public function exportableFields(Component $component): array
    {
        return array_values(array_filter(
            $component->getFields(),
            static fn (Field $field): bool => $field->show_in_index && ! $field->hide_on_export
        ));
    }

    /**
     * Attributes of the columns currently visible in the user's table (default export selection).
     *
     * @return array<int, string>
     */
    public function defaultColumns(Component $component): array
    {
        $visible = $component->options->value['columns'] ?? [];

        return array_values(array_map(
            static fn (Field $field): string => $field->attribute,
            array_filter(
                $this->exportableFields($component),
                static fn (Field $field): bool => empty($visible) || in_array($field->attribute, $visible, false)
            )
        ));
    }

    /**
     * Drops transport keys and empty values from raw request data, leaving only list filters.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function extractFilters(array $data): array
    {
        return array_filter(
            array_diff_key($data, array_flip(self::NON_FILTER_KEYS)),
            static fn (mixed $value): bool => $value !== null && $value !== '',
        );
    }

    /**
     * Builds the filtered and sorted list query for the current user.
     *
     * Filters are applied to every column shown in the table (not only the exported ones), so the
     * file always matches what the user sees. Field::generateFilter() reads the global request(),
     * hence the input is replaced here; callers that need the original input must restore it.
     *
     * @param  array<string, mixed>  $filters
     */
    public function makeQuery(Component $component, array $filters): Builder
    {
        request()->replace($filters);

        $query = app(QueryService::class)->buildIndexQuery($component, request());

        $visible = $component->options->value['columns'] ?? [];

        foreach ($component->getFields() as $field) {
            if ($field->show_in_index && (empty($visible) || in_array($field->attribute, $visible, false))) {
                $field->generateFilter($query);
            }
        }

        $curSort = $component->options->value['curSort'] ?? [];
        $curSort = (is_array($curSort) && count($curSort) >= 2) ? $curSort : ['id', 'desc'];

        return $query->orderBy($curSort[0], $curSort[1]);
    }

    /**
     * Human-readable export label: component label + active filter values.
     *
     * Example: "Визиты | Дата: 01.01–31.03.2026 | Менеджер: Иванов"
     *
     * @param  array<string, mixed>  $filters
     */
    public function buildLabel(Component $component, array $filters): string
    {
        $parts = [$component->getLabel()];

        foreach ($component->getFields() as $field) {
            if (! $field->filterable) {
                continue;
            }

            $raw = $filters[$field->attribute] ?? null;

            if (! is_string($raw) || $raw === '') {
                continue;
            }

            $readable = $this->parseFilterValue($raw);

            if ($readable !== '') {
                $parts[] = $field->name.': '.$readable;
            }
        }

        return implode(' | ', $parts);
    }

    /**
     * Converts a raw filter URL value (e.g. "f2026-01-01⚬t2026-03-31") to a readable string.
     */
    public function parseFilterValue(string $raw): string
    {
        $readable = [];

        foreach (explode('⚬', $raw) as $part) {
            $part = trim(strip_tags($part));

            if ($part === '') {
                continue;
            }

            $part = (string) preg_replace('/^[ftFT](?=[\d])/', '', $part);

            if (preg_match('/^[a-z]{2,10}$/', $part)) {
                continue;
            }

            $readable[] = $part;
        }

        return implode('–', $readable);
    }

    /**
     * Скачивает данные в формате xlsx.
     *
     * @param  array<int, mixed>  $rows
     * @param  array<int, Field>  $fields
     */
    public function download(array $rows, array $fields, string $filename): BinaryFileResponse
    {
        $prepared = $this->prepareExportData($rows, $fields);
        $importClass = config('lists.import_class', ListImport::class);

        return Excel::download(new $importClass($prepared), $filename.'.xlsx');
    }

    /**
     * Подготавливает плоский массив строк для передачи в класс Excel-экспорта.
     *
     * Первая строка — заголовки из названий полей.
     * Последующие строки — данные, отфильтрованные по разрешённым полям, с удалённым HTML.
     *
     * @param  array{data: array<int, array<string, mixed>>}  $rows
     * @param  array<int, Field>  $fields
     * @return array<int, array<int|string, mixed>>
     */
    public function prepareExportData(array $rows, array $fields): array
    {
        $allowed = [];
        $header = [];

        foreach ($fields as $field) {
            $allowed[] = $field->attribute;
            $header[] = $field->name;
        }

        $data = [$header];

        foreach ($rows['data'] as $item) {
            $row = [];

            foreach ($item as $key => $value) {
                if (in_array($key, $allowed, true)) {
                    $row[$key] = strip_tags((string) $value);
                }
            }

            $data[] = $row;
        }

        return $data;
    }

    /**
     * Строит export-ready массив данных напрямую из запроса без промежуточного DataTables::toArray().
     * Это уменьшает лишние преобразования и безопаснее для больших наборов данных.
     *
     * @param  array<int, Field>  $fields
     * @return array{data: array<int, array<string, mixed>>}
     */
    public function buildRowsFromQuery(
        Component $component,
        Builder $query,
        array $fields,
        string $list,
        int $chunkSize = 500,
    ): array {
        $rows = [];

        foreach ($query->lazy(max(1, $chunkSize)) as $item) {
            $row = [];

            foreach ($fields as $field) {
                $row[$field->attribute] = $field->item($item)->showIndex($item, $list);
            }

            $rows[] = $row;
        }

        return ['data' => $rows];
    }

    /**
     * Выполняет экспорт напрямую из Builder с lazy iteration.
     *
     * @param  array<int, Field>  $fields
     */
    public function downloadQuery(
        Component $component,
        Builder $query,
        array $fields,
        string $filename,
        string $list,
        int $chunkSize = 500,
    ): BinaryFileResponse {
        return $this->download(
            $this->buildRowsFromQuery($component, $query, $fields, $list, $chunkSize),
            $fields,
            $filename,
        );
    }

    /**
     * Безопасно генерирует Excel. В случае ошибки — логирует и бросает исходное исключение.
     *
     * @param  array<int, mixed>  $rows
     * @param  array<int, Field>  $fields
     *
     * @throws Throwable
     */
    public function downloadSafe(array $rows, array $fields, string $filename, string $rawSql = ''): BinaryFileResponse
    {
        try {
            return $this->download($rows, $fields, $filename);
        } catch (Throwable $e) {
            if ($rawSql) {
                info('Zak.Lists.Export SQL: '.$rawSql);
            }

            if (isReportable($e)) {
                report('Zak.Lists.Export: '.$e->getMessage());
            }

            throw $e;
        }
    }

    /**
     * Безопасный export path для Builder-based выгрузки.
     *
     * @param  array<int, Field>  $fields
     *
     * @throws Throwable
     */
    public function downloadQuerySafe(
        Component $component,
        Builder $query,
        array $fields,
        string $filename,
        string $list,
        string $rawSql = '',
        int $chunkSize = 500,
    ): BinaryFileResponse {
        try {
            return $this->downloadQuery($component, $query, $fields, $filename, $list, $chunkSize);
        } catch (Throwable $e) {
            if ($rawSql) {
                info('Zak.Lists.Export SQL: '.$rawSql);
            }

            if (isReportable($e)) {
                report('Zak.Lists.Export: '.$e->getMessage());
            }

            throw $e;
        }
    }

    /**
     * Возвращает true, если количество строк превышает жёсткий лимит экспорта.
     * При нулевом лимите проверка отключена.
     */
    public function exceedsExportLimit(Builder $query): bool
    {
        return $this->exceedsExportLimitByCount($this->countRows($query));
    }

    /**
     * Возвращает true, если количество строк превышает жёсткий лимит экспорта.
     */
    public function exceedsExportLimitByCount(int $rowsCount): bool
    {
        $max = (int) config('lists.max_export_rows', 50000);

        if ($max <= 0) {
            return false;
        }

        return $rowsCount > $max;
    }

    /**
     * Возвращает true, если экспорт следует отправить в очередь (строк больше порога).
     * При нулевом пороге очередь не используется.
     */
    public function shouldQueueExport(Builder $query): bool
    {
        return $this->shouldQueueExportByCount($this->countRows($query));
    }

    /**
     * Возвращает true, если экспорт следует отправить в очередь.
     */
    public function shouldQueueExportByCount(int $rowsCount): bool
    {
        $threshold = (int) config('lists.export_async_threshold', 5000);

        if ($threshold <= 0) {
            return false;
        }

        return $rowsCount > $threshold;
    }

    /**
     * Возвращает количество строк в запросе.
     *
     * Использует clone чтобы не модифицировать оригинальный Builder.
     * reorder() снимает ORDER BY перед агрегацией — SQL Server не допускает ORDER BY
     * в derived table без TOP/OFFSET (ошибка 1033).
     */
    public function countRows(Builder $query): int
    {
        return (clone $query)->reorder()->count();
    }
}
