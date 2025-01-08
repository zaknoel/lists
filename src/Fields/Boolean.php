<?php

namespace Zak\Lists\Fields;

class Boolean extends Text
{
    public bool $searchable = false;
    public array $rules = [
        'boolean' => 'Неправильная значение'
    ];

    public function searchable():static
    {
        $this->searchable=false;
        return $this;
    }


    public int $width = 12;
    public string $jsOptions = "width:'100px'";

    public function componentName(): string
    {
        return "checkbox";
    }



    public function indexHandler()
    {
        $this->value=$this->item->{$this->attribute} ?
            "<span class='badge text-bg-success'>Да</span>"
            : "<span class='badge text-bg-danger'>Нет</span>";
    }

    public function detailHandler()
    {
        $this->indexHandler(); // TODO: Change the autogenerated stub
    }

    public function filteredValue():string
    {
        return implode(' | ', $this->filter_value); // TODO: Change the autogenerated stub
    }

    public function generateFilter($query=false)
    {
        $this->filter_value = [];

        if (request()?->has($this->attribute)) {
            $v = explode("⚬", request()?->get($this->attribute, ''));
            if ($v) {
                if (in_array("1", $v)) {
                    $this->filter_value[1] = "Да";
                }
                if (in_array("0", $v)) {
                    $this->filter_value[0] = "Нет";
                }
                if($query) {
                    $query->whereIn($this->attribute, array_keys($this->filter_value));
                }
            }
        }
    }

}
