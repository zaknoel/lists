<?php

namespace Zak\Lists;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;

/**
 * Default Excel export import class.
 *
 * Receives a pre-prepared flat rows array (header row + data rows) from
 * ExportService::prepareExportData() and returns it as an Eloquent collection.
 *
 * To customise export behaviour (styles, events, merged cells), publish the
 * config and set 'lists.import_class' to your own class that accepts the same
 * (array $rows) constructor signature and implements FromCollection.
 */
class ListImport implements FromCollection
{
    /** @param array<int, array<int|string, mixed>> $rows */
    public function __construct(private readonly array $rows) {}

    public function collection(): Collection
    {
        return collect($this->rows);
    }
}
