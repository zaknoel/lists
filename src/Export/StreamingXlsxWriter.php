<?php

declare(strict_types=1);

namespace Zak\Lists\Export;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Zak\Lists\Component;
use Zak\Lists\Fields\Field;

/**
 * Writes an XLSX file row-by-row via OpenSpout without accumulating data in memory.
 *
 * Unlike the ExportService array-accumulation path, this class opens a file handle,
 * streams each model row through the field renderers, and closes the handle — keeping
 * peak memory proportional to chunk size rather than total row count.
 */
class StreamingXlsxWriter
{
    /**
     * Write all rows from $query to $storedPath on $disk and return the path.
     *
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @param  array<int, Field>  $fields
     */
    public function write(
        Component $component,
        Builder $query,
        array $fields,
        string $list,
        string $storedPath,
        string $disk,
        int $chunkSize = 500,
    ): string {
        $diskInstance = Storage::disk($disk);

        // Resolve an absolute writable temp path, then move to the target disk.
        $tmpPath = sys_get_temp_dir().'/lists_export_'.uniqid('', true).'.xlsx';

        $writer = new Writer();
        $writer->openToFile($tmpPath);

        // Header row
        $writer->addRow($this->makeRow(
            array_map(static fn (Field $f) => $f->name, $fields)
        ));

        // Data rows — lazy cursor keeps memory O(chunkSize)
        foreach ($query->lazy(max(1, $chunkSize)) as $item) {
            $values = [];

            foreach ($fields as $field) {
                $rendered = $field->item($item)->showIndex($item, $list);
                $values[] = strip_tags((string) $rendered);
            }

            $writer->addRow($this->makeRow($values));
        }

        $writer->close();

        // Move from local tmp to target storage disk.
        $diskInstance->put($storedPath, fopen($tmpPath, 'rb'));
        @unlink($tmpPath);

        return $storedPath;
    }

    /**
     * @param  array<int, string>  $values
     */
    private function makeRow(array $values): Row
    {
        $cells = array_map(
            static fn (string $value) => Cell::fromValue($value),
            $values,
        );

        return new Row($cells);
    }
}

