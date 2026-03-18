<?php

namespace Zak\Lists\Fields\Casts;

use Carbon\Carbon;

/**
 * Приводит значение к Carbon-дате.
 * При set() возвращает строку в формате Y-m-d (или null для пустых значений).
 */
class DateCast extends FieldCast
{
    public function __construct(
        private readonly string $format = 'Y-m-d',
    ) {}

    public function get(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value);
    }

    public function set(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value)->format($this->format);
    }
}
