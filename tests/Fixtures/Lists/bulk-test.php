<?php

use Zak\Lists\Action;
use Zak\Lists\BulkAction;
use Zak\Lists\Component;
use Zak\Lists\Fields\Boolean;
use Zak\Lists\Fields\ID;
use Zak\Lists\Fields\Text;
use Zak\Lists\Tests\Fixtures\Models\TestUser;

return new Component(
    model: TestUser::class,
    label: 'Пользователи (bulk)',
    singleLabel: 'пользователь',
    fields: [
        ID::make('ID', 'id')->hideOnForms()->sortable()->showOnIndex(),
        Boolean::make('Активность', 'active')->sortable()->filterable()->default(true),
        Text::make('Имя', 'name')->sortable()->searchable()->required()->defaultAction()->width(6),
        Text::make('Email', 'email')->searchable()->required()->width(6),
    ],
    actions: [
        Action::make('Просмотр')->showAction()->default(),
        Action::make('Редактировать')->editAction(),
        Action::make('Удалить')->deleteAction(),
    ],
    bulkActions: [
        BulkAction::make('Деактивировать', 'deactivate', function ($items) {
            foreach ($items as $item) {
                $item->active = false;
                $item->save();
            }
        }),
        BulkAction::make('Активировать', 'activate', function ($items) {
            foreach ($items as $item) {
                $item->active = true;
                $item->save();
            }
        }),
        BulkAction::make('Вызвать ошибку', 'throw-error', function ($items) {
            throw new RuntimeException('Test bulk error');
        }),
    ],
);
