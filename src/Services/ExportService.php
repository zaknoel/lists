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
