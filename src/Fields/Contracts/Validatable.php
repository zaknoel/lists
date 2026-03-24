<?php

namespace Zak\Lists\Fields\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * Контракт для полей с правилами валидации.
 */
interface Validatable
{
    /**
     * Возвращает правила валидации Laravel для этого поля.
     *
     * @return array<string, array<int, mixed>>
     */
    public function getRules(?Model $item = null): array;

    /**
     * Возвращает сообщения валидации для этого поля.
     *
     * @return array<string, string>
     */
    public function getRuleParams(): array;
}
