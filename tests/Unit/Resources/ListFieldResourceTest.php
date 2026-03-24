<?php

use Illuminate\Http\Request;
use Zak\Lists\Fields\Boolean;
use Zak\Lists\Fields\ID;
use Zak\Lists\Fields\Text;
use Zak\Lists\Resources\ListFieldResource;

// ── Базовые атрибуты ──────────────────────────────────────────────────────────

it('сериализует attribute и label поля', function () {
    $field = Text::make('Имя пользователя', 'name');
    $request = Request::create('/');

    $result = (new ListFieldResource($field))->toArray($request);

    expect($result['attribute'])->toBe('name');
    expect($result['label'])->toBe('Имя пользователя');
});

it('сериализует type и component поля', function () {
    $field = Text::make('Имя', 'name');
    $request = Request::create('/');

    $result = (new ListFieldResource($field))->toArray($request);

    expect($result['type'])->toBe($field->type());
    expect($result['component'])->toBe($field->componentName());
});

// ── Флаги отображения ──────────────────────────────────────────────────────────

it('сериализует флаги sortable, filterable, searchable, required, multiple', function () {
    $field = Text::make('Имя', 'name')
        ->sortable()
        ->required();
    $request = Request::create('/');

    $result = (new ListFieldResource($field))->toArray($request);

    expect($result['sortable'])->toBeTrue();
    expect($result['required'])->toBeTrue();
    expect($result['multiple'])->toBeFalse();
    expect($result['searchable'])->toBeTrue(); // default
});

it('сериализует флаги видимости show_in_index, show_in_detail, show_on_add, show_on_update', function () {
    $field = ID::make('ID', 'id')
        ->hideOnForms()
        ->showOnIndex();
    $request = Request::create('/');

    $result = (new ListFieldResource($field))->toArray($request);

    expect($result['show_in_index'])->toBeTrue();
    expect($result['show_on_add'])->toBeFalse();
    expect($result['show_on_update'])->toBeFalse();
});

it('сериализует width поля', function () {
    $field = Text::make('Имя', 'name')->width(4);
    $request = Request::create('/');

    $result = (new ListFieldResource($field))->toArray($request);

    expect($result['width'])->toBe(4);
});

it('сериализует hide_on_export поля', function () {
    $field = Text::make('Имя', 'name')->hideOnExport();
    $request = Request::create('/');

    $result = (new ListFieldResource($field))->toArray($request);

    expect($result['hide_on_export'])->toBeTrue();
});

it('hide_on_export по умолчанию false', function () {
    $field = Text::make('Имя', 'name');
    $request = Request::create('/');

    $result = (new ListFieldResource($field))->toArray($request);

    expect($result['hide_on_export'])->toBeFalse();
});

it('filterable по умолчанию true', function () {
    $field = Text::make('Email', 'email');
    $request = Request::create('/');

    $result = (new ListFieldResource($field))->toArray($request);

    expect($result['filterable'])->toBeTrue();
});

it('сериализует Boolean поле с правильным компонентом', function () {
    $field = Boolean::make('Активность', 'active');
    $request = Request::create('/');

    $result = (new ListFieldResource($field))->toArray($request);

    expect($result['component'])->toBe('checkbox');
});
