<?php

use Illuminate\Support\Facades\Auth;
use Zak\Lists\Fields\Boolean;
use Zak\Lists\Fields\FieldCollection;
use Zak\Lists\Fields\ID;
use Zak\Lists\Fields\Text;
use Zak\Lists\Tests\Fixtures\Models\TestUser;

beforeEach(function () {
    $user = TestUser::factory()->create();
    Auth::login($user);
});

// ── Creation ──────────────────────────────────────────────────────────────────

it('создаётся из массива полей через fromArray', function () {
    $fields = [
        Text::make('Имя', 'name'),
        Text::make('Email', 'email'),
    ];

    $collection = FieldCollection::fromArray($fields);

    expect($collection)->toBeInstanceOf(FieldCollection::class);
    expect($collection)->toHaveCount(2);
});

it('фильтрует не-Field объекты при создании', function () {
    $fields = [
        Text::make('Имя', 'name'),
        'not_a_field',
        null,
        42,
    ];

    $collection = FieldCollection::fromArray($fields);

    expect($collection)->toHaveCount(1);
});

it('создаётся пустой коллекцией из пустого массива', function () {
    $collection = FieldCollection::fromArray([]);

    expect($collection)->toHaveCount(0);
    expect($collection->isEmpty())->toBeTrue();
});

// ── Visibility Filters ────────────────────────────────────────────────────────

it('visibleForIndex возвращает только поля с show_in_index=true', function () {
    $fields = [
        Text::make('Имя', 'name')->showOnIndex(),
        Text::make('Email', 'email')->hideOnIndex(),
        Boolean::make('Активный', 'active')->showOnIndex(),
    ];

    $collection = FieldCollection::fromArray($fields);
    $visible = $collection->visibleForIndex();

    expect($visible)->toHaveCount(2);
    expect($visible->attributes())->toContain('name', 'active');
    expect($visible->attributes())->not->toContain('email');
});

it('visibleForDetail возвращает только поля с show_in_detail=true', function () {
    $fields = [
        Text::make('Имя', 'name')->showOnDetail(),
        Text::make('Email', 'email')->hideOnDetail(),
    ];

    $collection = FieldCollection::fromArray($fields);
    $visible = $collection->visibleForDetail();

    expect($visible)->toHaveCount(1);
    expect($visible->attributes())->toContain('name');
});

it('visibleForCreate возвращает только поля с show_on_add=true', function () {
    $fields = [
        ID::make('ID', 'id')->hideOnForms(),
        Text::make('Имя', 'name')->showOnAdd(),
        Text::make('Email', 'email')->showOnAdd(),
    ];

    $collection = FieldCollection::fromArray($fields);
    $visible = $collection->visibleForCreate();

    expect($visible)->toHaveCount(2);
    expect($visible->attributes())->not->toContain('id');
});

it('visibleForUpdate возвращает только поля с show_on_update=true', function () {
    $fields = [
        ID::make('ID', 'id')->hideOnForms(),
        Text::make('Имя', 'name')->showOnUpdate(),
    ];

    $collection = FieldCollection::fromArray($fields);
    $visible = $collection->visibleForUpdate();

    expect($visible)->toHaveCount(1);
    expect($visible->attributes())->toContain('name');
});

// ── Property Filters ──────────────────────────────────────────────────────────

it('filterable возвращает только поля с filterable=true', function () {
    $fields = [
        Text::make('Имя', 'name')->filterable(true),
        Text::make('Email', 'email')->filterable(false),
        Boolean::make('Активный', 'active')->filterable(true),
    ];

    $collection = FieldCollection::fromArray($fields);
    $filterable = $collection->filterable();

    expect($filterable)->toHaveCount(2);
    expect($filterable->attributes())->not->toContain('email');
});

it('searchable возвращает только поля с searchable=true', function () {
    $fields = [
        Text::make('Имя', 'name')->searchable(true),
        Text::make('Email', 'email')->searchable(false),
    ];

    $collection = FieldCollection::fromArray($fields);
    $searchable = $collection->searchable();

    expect($searchable)->toHaveCount(1);
    expect($searchable->attributes())->toContain('name');
});

it('sortable возвращает только поля с sortable=true', function () {
    $fields = [
        Text::make('Имя', 'name')->sortable(true),
        Text::make('Email', 'email')->sortable(false),
    ];

    $collection = FieldCollection::fromArray($fields);
    $sortable = $collection->sortable();

    expect($sortable)->toHaveCount(1);
});

it('exportable возвращает поля без hide_on_export', function () {
    $fields = [
        Text::make('Имя', 'name'),
        Text::make('Email', 'email')->hideOnExport(),
    ];

    $collection = FieldCollection::fromArray($fields);
    $exportable = $collection->exportable();

    expect($exportable)->toHaveCount(1);
    expect($exportable->attributes())->toContain('name');
});

// ── Attribute Helpers ─────────────────────────────────────────────────────────

it('attributes возвращает массив имён атрибутов', function () {
    $fields = [
        Text::make('Имя', 'name'),
        Text::make('Email', 'email'),
        Boolean::make('Активный', 'active'),
    ];

    $collection = FieldCollection::fromArray($fields);

    expect($collection->attributes())->toBe(['name', 'email', 'active']);
});

// ── Sort Order ────────────────────────────────────────────────────────────────

it('sortByUserPreference переставляет поля согласно заданному порядку', function () {
    $fields = [
        Text::make('Имя', 'name'),
        Text::make('Email', 'email'),
        Boolean::make('Активный', 'active'),
    ];

    $collection = FieldCollection::fromArray($fields);
    $sorted = $collection->sortByUserPreference(['active', 'name', 'email']);

    expect($sorted->attributes())->toBe(['active', 'name', 'email']);
});

it('sortByUserPreference добавляет поля не из saved sort в конец', function () {
    $fields = [
        Text::make('Имя', 'name'),
        Text::make('Email', 'email'),
        Boolean::make('Активный', 'active'),
    ];

    $collection = FieldCollection::fromArray($fields);
    $sorted = $collection->sortByUserPreference(['email']);

    expect($sorted->first()->attribute)->toBe('email');
    expect($sorted)->toHaveCount(3);
});

it('sortByUserPreference возвращает оригинальный порядок при пустом saved sort', function () {
    $fields = [
        Text::make('Имя', 'name'),
        Text::make('Email', 'email'),
    ];

    $collection = FieldCollection::fromArray($fields);
    $sorted = $collection->sortByUserPreference([]);

    expect($sorted->attributes())->toBe(['name', 'email']);
});

// ── Column Filter ─────────────────────────────────────────────────────────────

it('withColumnFilter фильтрует по списку разрешённых колонок', function () {
    $fields = [
        Text::make('Имя', 'name'),
        Text::make('Email', 'email'),
        Boolean::make('Активный', 'active'),
    ];

    $collection = FieldCollection::fromArray($fields);
    $filtered = $collection->withColumnFilter(['name', 'active']);

    expect($filtered)->toHaveCount(2);
    expect($filtered->attributes())->not->toContain('email');
});

it('withColumnFilter возвращает все поля при пустом фильтре', function () {
    $fields = [
        Text::make('Имя', 'name'),
        Text::make('Email', 'email'),
    ];

    $collection = FieldCollection::fromArray($fields);
    $filtered = $collection->withColumnFilter([]);

    expect($filtered)->toHaveCount(2);
});
