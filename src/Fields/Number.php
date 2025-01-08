<?php

namespace Zak\Lists\Fields;

class Number extends Text
{
    public array $rules = [
        'numeric' => 'Неправильный число',
    ];

    public string $type = 'number';
}
