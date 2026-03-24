<?php

namespace Zak\Lists\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Zak\Lists\Action;

/**
 * API Resource для метаданных действия над элементом списка.
 * Используется для передачи клиенту конфигурации доступных действий.
 */
class ListActionResource extends JsonResource
{
    public function __construct(Action $resource)
    {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Action $action */
        $action = $this->resource;

        return [
            'name' => $action->name,
            'type' => $action->type,
            'action' => $action->action,
            'blank' => $action->blank,
            'default' => $action->default,
        ];
    }
}
