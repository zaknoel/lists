<?php

declare(strict_types=1);

namespace Zak\Lists\Export;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\WriterInterface;
use Zak\Lists\Component;
use Zak\Lists\Fields\Field;

/**
 * Shared row streaming for file writers: keeps peak memory proportional to the chunk size.
 */
abstract class AbstractStreamingWriter implements ExportWriter
{
    abstract protected function makeWriter(): WriterInterface;

    abstract protected function extension(): string;

    public function write(
        Component $component,
        Builder $query,
        array $fields,
        string $list,
        string $storedPath,
        string $disk,
        int $chunkSize = 500,
        ?Closure $onProgress = null,
    ): ?string {
        $chunkSize = max(1, $chunkSize);
        $tmpPath = sys_get_temp_dir().'/lists_export_'.uniqid('', true).'.'.$this->extension();

        $writer = $this->makeWriter();
        $writer->openToFile($tmpPath);

        $writer->addRow($this->makeRow(
            array_map(static fn (Field $f): string => (string) $f->name, $fields)
        ));

        $processed = 0;
        $aborted = false;

        try {
            foreach ($query->lazy($chunkSize) as $item) {
                $values = [];

                foreach ($fields as $field) {
                    $values[] = strip_tags((string) $field->item($item)->showIndex($item, $list));
                }

                $writer->addRow($this->makeRow($values));
                $processed++;

                if ($onProgress !== null && $processed % $chunkSize === 0 && $onProgress($processed) === false) {
                    $aborted = true;
                    break;
                }
            }
        } finally {
            $writer->close();
        }

        if ($aborted) {
            @unlink($tmpPath);

            return null;
        }

        if ($onProgress !== null) {
            $onProgress($processed);
        }

        $handle = fopen($tmpPath, 'rb');

        try {
            Storage::disk($disk)->put($storedPath, $handle);
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }

            @unlink($tmpPath);
        }

        return $storedPath;
    }

    /**
     * @param  array<int, string>  $values
     */
    private function makeRow(array $values): Row
    {
        return new Row(array_map(static fn (string $value): Cell => Cell::fromValue($value), $values));
    }
}
