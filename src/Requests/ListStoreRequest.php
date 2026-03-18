<?php

namespace Zak\Lists\Requests;

use Illuminate\Support\Arr;
use Zak\Lists\Contracts\FieldServiceContract;
use Zak\Lists\Fields\Field;

/**
 * Form Request для создания нового элемента списка.
 * Авторизует операцию и собирает правила валидации из полей компонента.
 */
class ListStoreRequest extends BaseListRequest
{
    public function authorize(): bool
    {
        return $this->component()->userCanAdd();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $fields = Arr::where(
            $this->component()->getFields(),
            fn (Field $f) => $f->show_on_add
        );

        return app(FieldServiceContract::class)->buildValidationRules($fields);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $fields = Arr::where(
            $this->component()->getFields(),
            fn (Field $f) => $f->show_on_add
        );

        return app(FieldServiceContract::class)->buildValidationMessages($fields);
    }
}
