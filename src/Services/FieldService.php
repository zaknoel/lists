<?php

namespace Zak\Lists\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;
use Zak\Lists\Component;
use Zak\Lists\Contracts\FieldServiceContract;

/**
 * Отвечает за работу с полями: валидацию, сохранение, заполнение формы.
 */
class FieldService implements FieldServiceContract
{
    public function saveFields(?Model $item, array $fields, Request $request, Component $component): Model
    {
        $rules = $this->buildValidationRules($fields, $item);
        $messages = $this->buildValidationMessages($fields);

        $data = $request->validate($rules, $messages);

        $model = $item ?? new ($component->getModel());

        try {
            foreach ($fields as $field) {
                $field->saveValue($model, $data);
            }

            $component->eventOnBeforeSave($model);
            $model->save();
            $component->eventOnAfterSave($model);
        } catch (Throwable $e) {
            if (isReportable($e)) {
                report('Zak.Lists.FieldService: '.$e->getMessage()."\n".$e->getFile().':'.$e->getLine());
            }

            throw ValidationException::withMessages([
                'custom_field' => $e->getMessage(),
            ]);
        }

        return $model;
    }

    public function fillForForm(array $fields, Model $item): array
    {
        foreach ($fields as $field) {
            $field->item($item)->showEdit();
        }

        return $fields;
    }

    public function buildValidationRules(array $fields, ?Model $item = null): array
    {
        $rules = [];

        foreach ($fields as $field) {
            $rules = array_merge($rules, $field->getRules($item));
        }

        return $rules;
    }

    public function buildValidationMessages(array $fields): array
    {
        $messages = [];

        foreach ($fields as $field) {
            $messages = array_merge($messages, $field->getRuleParams());
        }

        return $messages;
    }
}
