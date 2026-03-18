<?php

use Illuminate\Support\Facades\Auth;
use Zak\Lists\Fields\Email;
use Zak\Lists\Tests\Fixtures\Models\TestUser;

beforeEach(function () {
    $user = TestUser::factory()->create();
    Auth::login($user);
});

it('создаётся через make()', function () {
    $field = Email::make('Email', 'email');

    expect($field)->toBeInstanceOf(Email::class);
    expect($field->attribute)->toBe('email');
});

it('type() возвращает email', function () {
    $field = Email::make('Email', 'email');

    expect($field->type())->toBe('email');
});

it('getRules содержит email правило', function () {
    $field = Email::make('Email', 'email');
    $rules = $field->getRules();

    expect($rules['email'])->toContain('email');
});

it('getRuleParams содержит сообщение для email', function () {
    $field = Email::make('Email', 'email');
    $params = $field->getRuleParams();

    expect($params)->toHaveKey('email.email');
});

it('handleFill устанавливает email из модели', function () {
    $user = TestUser::factory()->create(['email' => 'test@example.com']);

    $field = Email::make('Email', 'email');
    $field->item($user);
    $field->handleFill();

    expect($field->value)->toBe('test@example.com');
});

it('saveHandler сохраняет email в модель', function () {
    $user = TestUser::factory()->create(['email' => 'old@example.com']);

    $field = Email::make('Email', 'email');
    $field->saveHandler($user, ['email' => 'new@example.com']);

    expect($user->email)->toBe('new@example.com');
});

it('showIndex возвращает email значение', function () {
    $user = TestUser::factory()->create(['email' => 'show@example.com']);

    $field = Email::make('Email', 'email');
    $field->item($user);

    $result = $field->showIndex($user, 'test-users');

    expect($result)->toBe('show@example.com');
});
