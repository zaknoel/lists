<?php

declare(strict_types=1);

namespace Zak\Lists\Export;

use OpenSpout\Writer\CSV\Options;
use OpenSpout\Writer\CSV\Writer;
use OpenSpout\Writer\WriterInterface;

/**
 * Writes a UTF-8 CSV file (with BOM, so Excel opens Cyrillic correctly) row-by-row.
 */
class StreamingCsvWriter extends AbstractStreamingWriter
{
    protected function makeWriter(): WriterInterface
    {
        $options = new Options;
        $options->FIELD_DELIMITER = (string) config('lists.csv_delimiter', ';');
        $options->SHOULD_ADD_BOM = true;

        return new Writer($options);
    }

    protected function extension(): string
    {
        return 'csv';
    }
}
