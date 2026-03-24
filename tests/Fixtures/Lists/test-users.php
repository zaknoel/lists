<?php

use Zak\Lists\Action;
use Zak\Lists\Component;
use Zak\Lists\Fields\Boolean;
use Zak\Lists\Fields\ID;
use Zak\Lists\Fields\Text;
use Zak\Lists\Tests\Fixtures\Models\TestUser;

return new Component(
    model: TestUser::class,
    label: 'Тестовые пользователи',
    singleLabel: 'пользователь',
    fields: [
        ID::make('ID', 'id')
            ->hideOnForms()
            ->sortable()
            ->filterable()
            ->showOnIndex(),
        Boolean::make('Активность', 'active')
            ->sortable()
            ->filterable()
            ->default(true),
        Text::make('Имя', 'name')
            ->sortable()
            ->searchable()
            ->required()
            ->defaultAction()
            ->width(6),
        Text::make('Email', 'email')
            ->searchable()
            ->required()
            ->addRule('unique:test_users', 'Email уже занят')
            ->width(6),
    ],
    actions: [
        Action::make('Просмотр')->showAction()->default(),
        Action::make('Редактировать')->editAction(),
        Action::make('Удалить')->deleteAction(),
    ],
    bulkActions: [],
);
