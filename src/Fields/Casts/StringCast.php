<?php

namespace Zak\Lists\Fields\Casts;

/**
 * Приводит значение к строке. Обрезает пробелы при сохранении.
 */
class StringCast extends FieldCast
{
    public function get(mixed $value): string
    {
        return (string) ($value ?? '');
    }

    public function set(mixed $value): string
    {
        return trim((string) ($value ?? ''));
    }
}
