<?php

namespace Zak\Lists\Requests;

/**
 * Form Request для выполнения групповых действий (bulk actions).
 */
class ListBulkActionRequest extends BaseListRequest
{
    public function authorize(): bool
    {
        return $this->component()->userCanViewAny();
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', 'string'],
            'items' => ['required', 'array'],
            'items.*' => ['integer'],
        ];
    }
}
