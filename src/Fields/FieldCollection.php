<?php

namespace Zak\Lists\Fields;

use Illuminate\Support\Collection;

/**
 * Типизированная коллекция полей компонента.
 * Предоставляет удобные методы фильтрации по видимости и свойствам.
 *
 * @extends Collection<int, Field>
 */
class FieldCollection extends Collection
{
    /**
     * Создаёт коллекцию из массива полей.
     *
     * @param  array<int, Field>  $fields
     */
    public static function fromArray(array $fields): static
    {
        return new static(
            array_values(array_filter($fields, static fn ($f) => $f instanceof Field))
        );
    }

    /**
     * Возвращает поля, отображаемые в таблице списка.
     */
    public function visibleForIndex(): static
    {
        return $this->filter(fn (Field $field) => $field->show_in_index)->values();
    }

    /**
     * Возвращает поля, отображаемые на детальной странице.
     */
    public function visibleForDetail(): static
    {
        return $this->filter(fn (Field $field) => $field->show_in_detail)->values();
    }

    /**
     * Возвращает поля, отображаемые на форме создания.
     */
    public function visibleForCreate(): static
    {
        return $this->filter(fn (Field $field) => $field->show_on_add)->values();
    }

    /**
     * Возвращает поля, отображаемые на форме редактирования.
     */
    public function visibleForUpdate(): static
    {
        return $this->filter(fn (Field $field) => $field->show_on_update)->values();
    }

    /**
     * Возвращает поля с поддержкой фильтрации.
     */
    public function filterable(): static
    {
        return $this->filter(fn (Field $field) => $field->filterable)->values();
    }

    /**
     * Возвращает поля с поддержкой поиска.
     */
    public function searchable(): static
    {
        return $this->filter(fn (Field $field) => $field->searchable)->values();
    }

    /**
     * Возвращает поля с поддержкой сортировки.
     */
    public function sortable(): static
    {
        return $this->filter(fn (Field $field) => $field->sortable)->values();
    }

    /**
     * Возвращает поля, не скрытые при экспорте.
     */
    public function exportable(): static
    {
        return $this->filter(fn (Field $field) => ! $field->hide_on_export)->values();
    }

    /**
     * Возвращает имена атрибутов всех полей в коллекции.
     *
     * @return array<int, string>
     */
    public function attributes(): array
    {
        return $this->map(fn (Field $field) => $field->attribute)->values()->all();
    }

    /**
     * Применяет пользовательский порядок сортировки полей из сохранённых настроек.
     *
     * @param  array<int, string>  $savedSort  Массив имён атрибутов в нужном порядке
     */
    public function sortByUserPreference(array $savedSort): static
    {
        if (empty($savedSort)) {
            return $this;
        }

        $ordered = [];

        foreach ($savedSort as $attributeName) {
            $found = $this->first(fn (Field $f) => $f->attribute === $attributeName);

            if ($found !== null) {
                $ordered[] = $found;
            }
        }

        // Добавляем поля, которых нет в сохранённом порядке
        foreach ($this->items as $field) {
            if (! in_array($field, $ordered, true)) {
                $ordered[] = $field;
            }
        }

        return new static($ordered);
    }

    /**
     * Возвращает поля только из заданного столбца видимости.
     * Применяет фильтр пользовательских колонок, если он задан.
     *
     * @param  array<int, string>  $visibleColumns  Пустой массив = все колонки
     */
    public function withColumnFilter(array $visibleColumns): static
    {
        if (empty($visibleColumns)) {
            return $this;
        }

        return $this->filter(
            fn (Field $field) => in_array($field->attribute, $visibleColumns, false)
        )->values();
    }
}
