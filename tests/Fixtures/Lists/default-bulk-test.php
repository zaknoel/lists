<?php

use Zak\Lists\Component;
use Zak\Lists\Fields\ID;
use Zak\Lists\Fields\Text;
use Zak\Lists\Tests\Fixtures\Models\TestUser;

// No bulkActions specified → gets the default bulk-delete action.
// canDelete is explicitly allowed so tests can verify deletion works.
return new Component(
    model: TestUser::class,
    label: 'Default Bulk Test',
    singleLabel: 'пользователь',
    fields: [
        ID::make('ID', 'id')->hideOnForms()->showOnIndex(),
        Text::make('Имя', 'name')->showOnIndex(),
    ],
    canViewAny: fn () => true,
    canView: fn ($item) => true,
    canAdd: fn () => true,
    canEdit: fn ($item) => true,
    canDelete: fn ($item) => true,
);
