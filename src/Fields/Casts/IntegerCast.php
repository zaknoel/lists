<?php

namespace Zak\Lists\Fields\Casts;

/**
 * Приводит значение к целому числу.
 */
class IntegerCast extends FieldCast
{
    public function get(mixed $value): int
    {
        return (int) ($value ?? 0);
    }

    public function set(mixed $value): int
    {
        return (int) ($value ?? 0);
    }
}
