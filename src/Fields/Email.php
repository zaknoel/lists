<?php

namespace Zak\Lists\Fields;

class Email extends Text
{
    public string $type = 'email';

    public array $rules = [
        'email' => 'Неправильный email',
    ];
}
