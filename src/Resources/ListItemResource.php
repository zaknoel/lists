<?php

namespace Zak\Lists\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Zak\Lists\Action;
use Zak\Lists\Component;
use Zak\Lists\Fields\Field;

/**
 * API Resource для одного элемента списка.
 * Возвращает чистые атрибуты модели, метаданные прав и доступных действий.
 */
class ListItemResource extends JsonResource
{
    /**
     * @param  array<int, Field>  $fields  Поля для включения в ответ; пустой массив = все поля компонента
     */
    public function __construct(
        mixed $resource,
        private readonly Component $component,
        private readonly array $fields = [],
        string $_list = '',
    ) {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $fields = $this->fields ?: $this->component->getFields();

        $attributes = [];

        foreach ($fields as $field) {
            $attributes[$field->attribute] = $this->resource->{$field->attribute};
        }

        return [
            'id' => $this->resource->id,
            'attributes' => $attributes,
            'meta' => [
                'permissions' => [
                    'can_view' => $this->component->userCanView($this->resource),
                    'can_edit' => $this->component->userCanEdit($this->resource),
                    'can_delete' => $this->component->userCanDelete($this->resource),
                ],
                'actions' => array_values(array_map(
                    fn (Action $a) => $a->action,
                    $this->component->getFilteredActions($this->resource)
                )),
            ],
        ];
    }
}
