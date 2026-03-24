<?php

namespace Zak\Lists\Requests;

use Illuminate\Support\Arr;
use Zak\Lists\Contracts\FieldServiceContract;
use Zak\Lists\Fields\Field;

/**
 * Form Request для обновления существующего элемента списка.
 * Авторизует операцию и собирает правила валидации с учётом текущего элемента
 * (например, игнорирует уникальность для current ID).
 */
class ListUpdateRequest extends BaseListRequest
{
    public function authorize(): bool
    {
        $itemId = (int) $this->route('item');
        $item = $this->component()->getQuery()->find($itemId);

        if (! $item) {
            return false;
        }

        return $this->component()->userCanEdit($item);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $itemId = (int) $this->route('item');
        $item = $this->component()->getQuery()->find($itemId);

        $fields = Arr::where(
            $this->component()->getFields(),
            fn (Field $f) => $f->show_on_update
        );

        return app(FieldServiceContract::class)->buildValidationRules($fields, $item);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $fields = Arr::where(
            $this->component()->getFields(),
            fn (Field $f) => $f->show_on_update
        );

        return app(FieldServiceContract::class)->buildValidationMessages($fields);
    }
}
