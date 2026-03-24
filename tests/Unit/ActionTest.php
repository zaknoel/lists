<?php

use Illuminate\Support\Facades\Auth;
use Zak\Lists\Action;
use Zak\Lists\Component;
use Zak\Lists\Tests\Fixtures\Models\TestUser;

beforeEach(function () {
    $this->user = TestUser::factory()->create();
    Auth::login($this->user);

    $this->component = new Component(
        model: TestUser::class,
        label: 'Test',
        singleLabel: 'test',
        fields: [],
        canView: fn ($i) => true,
        canEdit: fn ($i) => true,
        canDelete: fn ($i) => true,
        canViewAny: fn () => true,
        canAdd: fn () => true,
    );
});

// ── Фабрика ───────────────────────────────────────────────────────────────────

it('создаётся через make()', function () {
    $action = Action::make('Просмотр');

    expect($action)->toBeInstanceOf(Action::class);
    expect($action->name)->toBe('Просмотр');
});

// ── Типы действий ─────────────────────────────────────────────────────────────

it('showAction() устанавливает action=show и type=action', function () {
    $action = Action::make('Просмотр')->showAction();

    expect($action->action)->toBe('show');
    expect($action->type)->toBe('action');
});

it('editAction() устанавливает action=edit', function () {
    $action = Action::make('Редактировать')->editAction();

    expect($action->action)->toBe('edit');
});

it('deleteAction() устанавливает action=delete', function () {
    $action = Action::make('Удалить')->deleteAction();

    expect($action->action)->toBe('delete');
});

it('setLinkAction() устанавливает type=link и action как URL', function () {
    $action = Action::make('Перейти')->setLinkAction('/custom/path');

    expect($action->type)->toBe('link');
    expect($action->action)->toBe('/custom/path');
});

it('setJsAction() устанавливает type=js и action как JS код', function () {
    $action = Action::make('Скрипт')->setJsAction('alert(item_id)');

    expect($action->type)->toBe('js');
    expect($action->action)->toBe('alert(item_id)');
});

// ── Флаги ──────────────────────────────────────────────────────────────────────

it('default() устанавливает default=true', function () {
    $action = Action::make('Просмотр')->showAction()->default();

    expect($action->default)->toBeTrue();
});

it('blank() устанавливает blank=true', function () {
    $action = Action::make('Открыть')->blank();

    expect($action->blank)->toBeTrue();
});

it('default и blank по умолчанию false', function () {
    $action = Action::make('Просмотр');

    expect($action->default)->toBeFalse();
    expect($action->blank)->toBeFalse();
});

// ── isShown() ─────────────────────────────────────────────────────────────────

it('isShown() для show action зависит от canView', function () {
    $item = TestUser::factory()->create();

    $allowComponent = new Component(
        model: TestUser::class, label: 'T', singleLabel: 't', fields: [],
        canView: fn ($i) => true, canViewAny: fn () => true, canAdd: fn () => true,
        canEdit: fn ($i) => true, canDelete: fn ($i) => true,
    );
    $denyComponent = new Component(
        model: TestUser::class, label: 'T', singleLabel: 't', fields: [],
        canView: fn ($i) => false, canViewAny: fn () => true, canAdd: fn () => true,
        canEdit: fn ($i) => true, canDelete: fn ($i) => true,
    );

    $action = Action::make('Просмотр')->showAction();

    expect($action->isShown($allowComponent, $item))->toBeTrue();
    expect($action->isShown($denyComponent, $item))->toBeFalse();
});

it('isShown() для edit action зависит от canEdit', function () {
    $item = TestUser::factory()->create();

    $allowComponent = new Component(
        model: TestUser::class, label: 'T', singleLabel: 't', fields: [],
        canView: fn ($i) => true, canViewAny: fn () => true, canAdd: fn () => true,
        canEdit: fn ($i) => true, canDelete: fn ($i) => true,
    );
    $denyComponent = new Component(
        model: TestUser::class, label: 'T', singleLabel: 't', fields: [],
        canView: fn ($i) => true, canViewAny: fn () => true, canAdd: fn () => true,
        canEdit: fn ($i) => false, canDelete: fn ($i) => true,
    );

    $action = Action::make('Редактировать')->editAction();

    expect($action->isShown($allowComponent, $item))->toBeTrue();
    expect($action->isShown($denyComponent, $item))->toBeFalse();
});

it('isShown() с кастомным callback', function () {
    $item = TestUser::factory()->create();

    $action = Action::make('Кастомный')->showAction()->show(fn ($comp, $i) => false);

    expect($action->isShown($this->component, $item))->toBeFalse();
});

it('isShown() без show callback возвращает true для неизвестного типа', function () {
    $item = TestUser::factory()->create();
    $action = Action::make('Другое')->setLinkAction('/link');

    expect($action->isShown($this->component, $item))->toBeTrue();
});
