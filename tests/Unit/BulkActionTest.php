<?php

use Illuminate\Support\Facades\Auth;
use Zak\Lists\BulkAction;
use Zak\Lists\Tests\Fixtures\Models\TestUser;

beforeEach(function () {
    $user = TestUser::factory()->create();
    Auth::login($user);
});

// ── Фабрика ───────────────────────────────────────────────────────────────────

it('создаётся через make()', function () {
    $action = BulkAction::make('Деактивировать', 'deactivate', fn ($items) => null);

    expect($action)->toBeInstanceOf(BulkAction::class);
    expect($action->name)->toBe('Деактивировать');
    expect($action->key)->toBe('deactivate');
});

it('callback сохраняется и вызываем', function () {
    $called = false;
    $action = BulkAction::make('Test', 'test', function ($items) use (&$called) {
        $called = true;
    });

    call_user_func($action->callback, collect());

    expect($called)->toBeTrue();
});

// ── Методы отображения ────────────────────────────────────────────────────────

it('label() возвращает name', function () {
    $action = BulkAction::make('Удалить', 'delete', fn ($items) => null);

    expect($action->label())->toBe('Удалить');
});

it('по умолчанию icon пустая строка', function () {
    $action = BulkAction::make('Test', 'test', fn ($items) => null);

    expect($action->icon)->toBe('');
});

it('setIcon() устанавливает иконку', function () {
    $action = BulkAction::make('Test', 'test', fn ($items) => null)->setIcon('trash');

    expect($action->icon)->toBe('trash');
});

// ── Сообщения ─────────────────────────────────────────────────────────────────

it('getSuccessMessage() возвращает дефолтный перевод', function () {
    $action = BulkAction::make('Test', 'test', fn ($items) => null);

    expect($action->getSuccessMessage())->toBe(__('lists.messages.bulk_success'));
});

it('setSuccessMessage() устанавливает кастомное сообщение', function () {
    $action = BulkAction::make('Test', 'test', fn ($items) => null)
        ->setSuccessMessage('Всё готово!');

    expect($action->getSuccessMessage())->toBe('Всё готово!');
});

it('confirmText() возвращает дефолтный перевод', function () {
    $action = BulkAction::make('Test', 'test', fn ($items) => null);

    expect($action->confirmText())->toBe(__('lists.messages.bulk_confirm'));
});

it('setConfirmText() устанавливает кастомный текст', function () {
    $action = BulkAction::make('Test', 'test', fn ($items) => null)
        ->setConfirmText('Уверены?');

    expect($action->confirmText())->toBe('Уверены?');
});

it('дефолтные confirmText и successMessage из переводов', function () {
    $action = BulkAction::make('Test', 'test', fn ($items) => null);

    expect($action->confirmText)->toBe(__('lists.messages.bulk_confirm'));
    expect($action->successMessage)->toBe(__('lists.messages.bulk_success'));
});
