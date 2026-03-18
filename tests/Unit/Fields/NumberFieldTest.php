<?php

use Illuminate\Support\Facades\Auth;
use Zak\Lists\Fields\Number;
use Zak\Lists\Tests\Fixtures\Models\TestUser;

beforeEach(function () {
    $user = TestUser::factory()->create();
    Auth::login($user);
});

it('создаётся через make()', function () {
    $field = Number::make('Возраст', 'age');

    expect($field)->toBeInstanceOf(Number::class);
    expect($field->attribute)->toBe('age');
});

it('type() возвращает number', function () {
    $field = Number::make('Возраст', 'age');

    expect($field->type())->toBe('number');
});

it('getRules включает правило numeric', function () {
    $field = Number::make('Возраст', 'age');
    $rules = $field->getRules();

    expect($rules['age'])->toContain('numeric');
});

it('getRuleParams содержит сообщение для numeric', function () {
    $field = Number::make('Возраст', 'age');
    $params = $field->getRuleParams();

    expect($params)->toHaveKey('age.numeric');
});

it('handleFill устанавливает числовое значение из модели', function () {
    $user = TestUser::factory()->create();
    $user->age = 25;
    $user->getAttributes(); // force attribute

    $field = Number::make('Возраст', 'age');
    $field->item($user);
    $field->handleFill();

    expect($field->value)->toBe(25);
});

it('saveHandler сохраняет числовое значение', function () {
    $user = TestUser::factory()->create();

    $field = Number::make('Возраст', 'age');
    $field->saveHandler($user, ['age' => 30]);

    expect($user->age)->toBe(30);
});
