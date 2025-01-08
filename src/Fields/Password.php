<?php

namespace Zak\Lists\Fields;

use Illuminate\Support\Facades\Hash;

class Password extends Text
{
    public bool $show_in_detail = false;
    public bool $searchable = false;
    public bool $show_in_index = false;
    public bool $filterable = false;
    public string $type = "password";
    public array $rules = [
        'min:8' => 'Пароль должен быть не менее 8 символов длиной.'
    ];

    public function componentName(): string
    {
        return "password";
    }

    public function saveHandler($item, $data)
    {
        $value = $data[$this->attribute]??'';
        if ($value) {
            $item->{$this->attribute} = Hash::make($value);
        }
        return $item;
    }

}
