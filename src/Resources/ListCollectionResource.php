<?php

namespace Zak\Lists\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Collection;
use Zak\Lists\Component;
use Zak\Lists\Fields\Field;

/**
 * API Resource для коллекции элементов списка.
 * Оборачивает набор элементов с метаданными компонента и (опционально) пагинацией.
 */
class ListCollectionResource extends JsonResource
{
    /**
     * @param  Collection|AbstractPaginator  $resource
     * @param  array<int, Field>  $fields
     */
    public function __construct(
        mixed $resource,
        private readonly Component $component,
        private readonly array $fields = [],
        private readonly string $list = '',
    ) {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $fields = $this->fields ?: $this->component->getFields();
        $items = $this->resource instanceof AbstractPaginator
            ? $this->resource->getCollection()
            : $this->resource;

        $data = $items->map(
            fn ($item) => (new ListItemResource($item, $this->component, $fields, $this->list))
                ->toArray($request)
        )->values()->all();

        $meta = [
            'component' => [
                'label' => $this->component->getLabel(),
                'model' => $this->component->getModel(),
            ],
        ];

        if ($this->resource instanceof AbstractPaginator) {
            $meta['pagination'] = [
                'total' => $this->resource->total(),
                'per_page' => $this->resource->perPage(),
                'current_page' => $this->resource->currentPage(),
                'last_page' => $this->resource->lastPage(),
            ];
        }

        return [
            'data' => $data,
            'meta' => $meta,
        ];
    }
}
