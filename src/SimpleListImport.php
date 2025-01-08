<?php

namespace Zak\Lists;

use Maatwebsite\Excel\Concerns\FromCollection;

class SimpleListImport implements FromCollection
{
    private array $all;

    public function __construct($all)
    {
        $this->all = $all;
    }

    public function collection()
    {
        return collect($this->all);
    }

    public function setData($all)
    {
        return $this->all = $all;
    }
}
