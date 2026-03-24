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
    private function filtered(callable $callback): static
    {
        /** @var array<int, Field> $items */
        $items = $this->filter($callback)->values()->all();

        return new static($items);
    }

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
        return $this->filtered(fn (Field $field) => $field->show_in_index);
    }

    /**
     * Возвращает поля, отображаемые на детальной странице.
     */
    public function visibleForDetail(): static
    {
        return $this->filtered(fn (Field $field) => $field->show_in_detail);
    }

    /**
     * Возвращает поля, отображаемые на форме создания.
     */
    public function visibleForCreate(): static
    {
        return $this->filtered(fn (Field $field) => $field->show_on_add);
    }

    /**
     * Возвращает поля, отображаемые на форме редактирования.
     */
    public function visibleForUpdate(): static
    {
        return $this->filtered(fn (Field $field) => $field->show_on_update);
    }

    /**
     * Возвращает поля с поддержкой фильтрации.
     */
    public function filterable(): static
    {
        return $this->filtered(fn (Field $field) => $field->filterable);
    }

    /**
     * Возвращает поля с поддержкой поиска.
     */
    public function searchable(): static
    {
        return $this->filtered(fn (Field $field) => $field->searchable);
    }

    /**
     * Возвращает поля с поддержкой сортировки.
     */
    public function sortable(): static
    {
        return $this->filtered(fn (Field $field) => $field->sortable);
    }

    /**
     * Возвращает поля, не скрытые при экспорте.
     */
    public function exportable(): static
    {
        return $this->filtered(fn (Field $field) => ! $field->hide_on_export);
    }

    /**
     * Возвращает имена атрибутов всех полей в коллекции.
     *
     * @return array<int, string>
     */
    public function attributes(): array
    {
        /** @var array<int, string> */
        return $this->map(fn (Field $field) => (string) $field->attribute)->values()->all();
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

        return $this->filtered(
            fn (Field $field) => in_array($field->attribute, $visibleColumns, false)
        );
    }
}
