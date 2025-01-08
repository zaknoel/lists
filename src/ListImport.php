<?php

namespace Zak\Lists;

use Maatwebsite\Excel\Concerns\FromCollection;

class ListImport implements FromCollection
{
    private array $all;


    public function __construct($all, $fields)
    {
        $data = [];
        $header = [];
        $allowed = [];
        foreach ($fields as $field) {
            $allowed[] = $field->attribute;
            $header[] = $field->name;
        }
        $data[] = $header;
        foreach ($all["data"] as $item) {
            $d = [];
            foreach ($item as $k => $v) {
                if (in_array($k, $allowed)) {
                    $d[$k] = strip_tags($v);
                }
            }
            $data[] = $d;
        }
        $this->all = $data;
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
