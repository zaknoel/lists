<?php

namespace Zak\Lists\Requests;

/**
 * Form Request для сохранения пользовательских настроек таблицы
 * (видимые колонки, порядок, фильтры).
 */
class ListOptionsRequest extends BaseListRequest
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
            'columns' => ['nullable', 'array'],
            'sort' => ['nullable', 'array'],
            'filters' => ['nullable', 'array'],
        ];
    }
}
