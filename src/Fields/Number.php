<?php

namespace Zak\Lists\Fields;

class Number extends Text
{
    public array $rules=[
        'numeric'=>'Неправильный число',
    ];
    public string $type="number";

    public function beforeSave(mixed $attribute)
    {
        if($this->beforeSaveCallback && is_callable($this->beforeSaveCallback)){
            $attribute=call_user_func_array($this->beforeSaveCallback, $attribute);
        }

        return (float)$attribute;
    }

}
