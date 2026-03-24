<?php

namespace Zak\Lists\Services;

use Zak\Lists\Component;
use Zak\Lists\Contracts\ComponentLoaderContract;
use Zak\Lists\Fields\Field;

/**
 * Загружает и кэширует конфигурацию компонента из файла app/Lists/*.php.
 */
class ComponentLoader implements ComponentLoaderContract
{
    /** @var array<string, Component> Кэш загруженных компонентов */
    private array $cache = [];

    public function resolve(string $list, bool $applySortOrder = false): Component
    {
        $cacheKey = $list.($applySortOrder ? ':sorted' : '');

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        // Обеспечиваем базовый компонент в кэше прежде чем создавать sorted-вариант.
        // Это гарантирует ровно один вызов include и один запрос UserOption::firstOrCreate.
        if (! isset($this->cache[$list])) {
            $file = rtrim(config('lists.path', app_path('Lists/')), '/').'/'.$list.'.php';

            if (! file_exists($file)) {
                abort(404, __('lists.errors.component_not_found', ['list' => $list, 'file' => $file]));
            }

            $component = include $file;

            if (! $component instanceof Component) {
                abort(404, __('lists.errors.component_invalid', ['list' => $list]));
            }

            $this->cache[$list] = $component;
        }

        if (! $applySortOrder) {
            return $this->cache[$list];
        }

        // Клонируем базовый компонент чтобы не мутировать закэшированный экземпляр.
        $sorted = clone $this->cache[$list];
        $sorted = $this->applyColumnSortOrder($sorted);

        return $this->cache[$cacheKey] = $sorted;
    }

    /**
     * Сортирует поля компонента согласно пользовательскому порядку из UserOption.
     */
    private function applyColumnSortOrder(Component $component): Component
    {
        $savedSort = $component->options->value['sort'] ?? [];

        if (empty($savedSort)) {
            return $component;
        }

        $allFields = $component->getFields();
        $ordered = [];

        foreach ($savedSort as $attributeName) {
            $found = array_filter($allFields, fn (Field $f) => $f->attribute === $attributeName);
            if ($found) {
                $ordered[] = reset($found);
            }
        }

        // Добавляем поля, которых нет в сохранённом порядке
        foreach ($allFields as $field) {
            if (! in_array($field, $ordered, true)) {
                $ordered[] = $field;
            }
        }

        if (count($ordered) === count($allFields)) {
            $component->setFields($ordered);
        }

        return $component;
    }
}
