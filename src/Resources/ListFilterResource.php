<?php

namespace Zak\Lists\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Zak\Lists\Fields\Boolean;
use Zak\Lists\Fields\Field;
use Zak\Lists\Fields\Select;

/**
 * API Resource для метаданных фильтра поля.
 * Используется для передачи клиенту конфигурации доступных фильтров.
 */
class ListFilterResource extends JsonResource
{
    public function __construct(Field $resource)
    {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Field $field */
        $field = $this->resource;

        return [
            'attribute' => $field->attribute,
            'label' => $field->name,
            'type' => $field->componentName(),
            'filter_view' => $field->filter_view,
            'options' => $this->resolveOptions($field),
        ];
    }

    /**
     * Возвращает доступные опции для фильтрации.
     * Для Select и Relation — перечисление значений enum.
     * Для Boolean — фиксированные значения Да/Нет.
     * Для остальных типов — null (свободный ввод).
     *
     * @return array<int|string, string>|null
     */
    private function resolveOptions(Field $field): ?array
    {
        if ($field instanceof Boolean) {
            return [
                '1' => __('lists.filter.yes'),
                '0' => __('lists.filter.no'),
            ];
        }

        if ($field instanceof Select) {
            return $field->enum;
        }

        return null;
    }
}
