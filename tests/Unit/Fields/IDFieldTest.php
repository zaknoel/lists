<?php

use Illuminate\Support\Facades\Auth;
use Zak\Lists\Fields\ID;
use Zak\Lists\Tests\Fixtures\Models\TestUser;

beforeEach(function () {
    $user = TestUser::factory()->create();
    Auth::login($user);
});

it('создаётся через make()', function () {
    $field = ID::make('ID', 'id');

    expect($field)->toBeInstanceOf(ID::class);
    expect($field->attribute)->toBe('id');
});

it('type() возвращает id', function () {
    $field = ID::make('ID', 'id');

    expect($field->type())->toBe('id');
});

it('componentName() возвращает text', function () {
    $field = ID::make('ID', 'id');

    expect($field->componentName())->toBe('text');
});

it('saveHandler не изменяет модель (ID нельзя изменить)', function () {
    $user = TestUser::factory()->create();
    $originalId = $user->id;

    $field = ID::make('ID', 'id');
    $field->saveHandler($user, ['id' => 9999]);

    expect($user->id)->toBe($originalId);
});

it('handleFill устанавливает значение id из модели', function () {
    $user = TestUser::factory()->create();

    $field = ID::make('ID', 'id');
    $field->item($user);
    $field->handleFill();

    expect($field->value)->toBe($user->id);
});

it('indexHandler возвращает ID значение', function () {
    $user = TestUser::factory()->create();

    $field = ID::make('ID', 'id');
    $field->item($user);
    $field->indexHandler();

    expect($field->value)->toBe($user->id);
});

it('detailHandler возвращает ID значение', function () {
    $user = TestUser::factory()->create();

    $field = ID::make('ID', 'id');
    $field->item($user);
    $field->detailHandler();

    expect($field->value)->toBe($user->id);
});

it('hideOnForms скрывает ID на обеих формах', function () {
    $field = ID::make('ID', 'id')->hideOnForms();

    expect($field->show_on_add)->toBeFalse();
    expect($field->show_on_update)->toBeFalse();
});
