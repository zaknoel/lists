<?php

namespace Zak\Lists\Fields\Casts;

/**
 * Базовый класс для всех cast-преобразований значений полей.
 * Позволяет поля трансформировать значения при чтении и записи
 * независимо от встроенных Eloquent-кастов.
 */
abstract class FieldCast
{
    /**
     * Преобразует значение при получении (чтение из модели → отображение).
     */
    abstract public function get(mixed $value): mixed;

    /**
     * Преобразует значение при сохранении (данные формы → запись в модель).
     */
    abstract public function set(mixed $value): mixed;
}
