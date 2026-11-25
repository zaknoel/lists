<?php

declare(strict_types=1);

namespace Zak\Lists\Export;

use OpenSpout\Writer\WriterInterface;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Writes an XLSX file row-by-row via OpenSpout without accumulating data in memory.
 */
class StreamingXlsxWriter extends AbstractStreamingWriter
{
    protected function makeWriter(): WriterInterface
    {
        return new Writer;
    }

    protected function extension(): string
    {
        return 'xlsx';
    }
}
