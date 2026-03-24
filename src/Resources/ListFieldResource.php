<?php

namespace Zak\Lists\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Zak\Lists\Fields\Field;

/**
 * API Resource для метаданных поля компонента.
 * Используется для передачи конфигурации полей клиенту (заголовки таблицы, параметры формы).
 */
class ListFieldResource extends JsonResource
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
            'type' => $field->type(),
            'component' => $field->componentName(),
            'sortable' => $field->sortable,
            'filterable' => $field->filterable,
            'searchable' => $field->searchable,
            'required' => $field->required,
            'multiple' => $field->multiple,
            'width' => $field->width,
            'show_in_index' => $field->show_in_index,
            'show_in_detail' => $field->show_in_detail,
            'show_on_add' => $field->show_on_add,
            'show_on_update' => $field->show_on_update,
            'hide_on_export' => $field->hide_on_export,
        ];
    }
}
