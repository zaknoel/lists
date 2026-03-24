<?php

namespace Zak\Lists\Fields;

class Email extends Text
{
    public string $type = 'email';

    public array $rules = [
        'email' => 'lists.fields.validation.email',
    ];
}
