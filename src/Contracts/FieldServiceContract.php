<?php

namespace Zak\Lists\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Zak\Lists\Component;
use Zak\Lists\Fields\Field;

interface FieldServiceContract
{
    /**
     * Валидирует данные запроса и сохраняет поля в модель.
     * Возвращает сохранённую модель.
     *
     * @param  array<int, Field>  $fields
     */
    public function saveFields(?Model $item, array $fields, Request $request, Component $component): Model;

    /**
     * Заполняет поля значениями из модели для отображения в форме редактирования.
     *
     * @param  array<int, Field>  $fields
     * @return array<int, Field>
     */
    public function fillForForm(array $fields, Model $item): array;

    /**
     * Собирает правила валидации из всех полей.
     *
     * @param  array<int, Field>  $fields
     * @return array<string, array<int, mixed>>
     */
    public function buildValidationRules(array $fields, ?Model $item = null): array;

    /**
     * Собирает сообщения валидации из всех полей.
     *
     * @param  array<int, Field>  $fields
     * @return array<string, string>
     */
    public function buildValidationMessages(array $fields): array;
}
