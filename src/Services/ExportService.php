<?php

namespace Zak\Lists\Services;

use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;
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
        return Excel::download(new ListImport($rows, $fields), $filename.'.xlsx');
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
}
