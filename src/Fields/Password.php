<?php

namespace Zak\Lists\Fields;

use Illuminate\Support\Facades\Hash;
use function Laravel\Prompts\password;

class Password extends Text
{
    public bool $show_in_detail=false;
    public bool $searchable = false;
    public bool $show_in_index=false;
    public bool $filterable=false;
    public string $type="password";
    public array $rules=[
        'min:8'=>'Пароль должен быть не менее 8 символов длиной.'
    ];
    public function componentName(): string
    {
        return "password";
    }
    public function beforeSave(mixed $attribute)
    {

        if($attribute){
            $attribute=Hash::make($attribute);
        }

        return parent::beforeSave($attribute);
    }
    public function saveValue($item, $value)
    {
        if(!$value){
            return false;
        }
        return parent::saveValue($item, $value);
    }

}
