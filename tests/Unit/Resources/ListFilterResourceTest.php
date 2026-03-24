<?php

use Illuminate\Http\Request;
use Zak\Lists\Fields\Boolean;
use Zak\Lists\Fields\ID;
use Zak\Lists\Fields\Select;
use Zak\Lists\Fields\Text;
use Zak\Lists\Resources\ListFilterResource;

// ── Базовые атрибуты ──────────────────────────────────────────────────────────

it('сериализует attribute и label фильтра', function () {
    $field = Text::make('Имя', 'name')->filterable();
    $request = Request::create('/');

    $result = (new ListFilterResource($field))->toArray($request);

    expect($result['attribute'])->toBe('name');
    expect($result['label'])->toBe('Имя');
});

it('сериализует type как componentName поля', function () {
    $field = Text::make('Email', 'email');
    $request = Request::create('/');

    $result = (new ListFilterResource($field))->toArray($request);

    expect($result['type'])->toBe('text');
});

it('сериализует filter_view поля', function () {
    $field = Text::make('Имя', 'name')->filterView('custom.filter.view');
    $request = Request::create('/');

    $result = (new ListFilterResource($field))->toArray($request);

    expect($result['filter_view'])->toBe('custom.filter.view');
});

it('filter_view пустая строка по умолчанию', function () {
    $field = Text::make('Имя', 'name');
    $request = Request::create('/');

    $result = (new ListFilterResource($field))->toArray($request);

    expect($result['filter_view'])->toBe('');
});

// ── Опции ─────────────────────────────────────────────────────────────────────

it('options равен null для текстового поля', function () {
    $field = Text::make('Имя', 'name');
    $request = Request::create('/');

    $result = (new ListFilterResource($field))->toArray($request);

    expect($result['options'])->toBeNull();
});

it('options равен null для ID поля', function () {
    $field = ID::make('ID', 'id');
    $request = Request::create('/');

    $result = (new ListFilterResource($field))->toArray($request);

    expect($result['options'])->toBeNull();
});

it('options содержит enum для Select поля', function () {
    $field = Select::make('Статус', 'status')->enum(['active' => 'Активный', 'inactive' => 'Неактивный']);
    $request = Request::create('/');

    $result = (new ListFilterResource($field))->toArray($request);

    expect($result['options'])->toBe(['active' => 'Активный', 'inactive' => 'Неактивный']);
});

it('options содержит пустой массив для Select без enum', function () {
    $field = Select::make('Тип', 'type');
    $request = Request::create('/');

    $result = (new ListFilterResource($field))->toArray($request);

    expect($result['options'])->toBeArray()->toBeEmpty();
});

it('options содержит Да/Нет для Boolean поля', function () {
    $field = Boolean::make('Активность', 'active');
    $request = Request::create('/');

    $result = (new ListFilterResource($field))->toArray($request);

    expect($result['options'])->toBeArray();
    expect($result['options'])->toHaveKey('1');
    expect($result['options'])->toHaveKey('0');
});

it('Boolean поле имеет type checkbox', function () {
    $field = Boolean::make('Активность', 'active');
    $request = Request::create('/');

    $result = (new ListFilterResource($field))->toArray($request);

    expect($result['type'])->toBe('checkbox');
});
