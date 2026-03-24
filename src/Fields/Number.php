<?php

namespace Zak\Lists\Fields;

class Number extends Text
{
    public array $rules = [
        'numeric' => 'lists.fields.validation.numeric',
    ];

    public string $type = 'number';
}
