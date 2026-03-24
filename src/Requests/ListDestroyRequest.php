<?php

namespace Zak\Lists\Requests;

/**
 * Form Request для удаления элемента списка.
 * Содержит только авторизацию — данных для валидации нет.
 */
class ListDestroyRequest extends BaseListRequest
{
    public function authorize(): bool
    {
        $itemId = (int) $this->route('item');
        $item = $this->component()->getQuery()->find($itemId);

        if (! $item) {
            abort(404);
        }

        return $this->component()->userCanDelete($item);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
