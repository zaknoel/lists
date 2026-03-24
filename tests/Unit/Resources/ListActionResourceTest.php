<?php

use Illuminate\Http\Request;
use Zak\Lists\Action;
use Zak\Lists\Resources\ListActionResource;

// ── Базовые атрибуты ──────────────────────────────────────────────────────────

it('сериализует name действия', function () {
    $action = Action::make('Просмотр')->showAction();
    $request = Request::create('/');

    $result = (new ListActionResource($action))->toArray($request);

    expect($result['name'])->toBe('Просмотр');
});

it('сериализует type действия', function () {
    $action = Action::make('Просмотр')->showAction();
    $request = Request::create('/');

    $result = (new ListActionResource($action))->toArray($request);

    expect($result['type'])->toBe('action');
});

it('сериализует action для show', function () {
    $action = Action::make('Просмотр')->showAction();
    $request = Request::create('/');

    $result = (new ListActionResource($action))->toArray($request);

    expect($result['action'])->toBe('show');
});

it('сериализует action для edit', function () {
    $action = Action::make('Редактировать')->editAction();
    $request = Request::create('/');

    $result = (new ListActionResource($action))->toArray($request);

    expect($result['action'])->toBe('edit');
});

it('сериализует action для delete', function () {
    $action = Action::make('Удалить')->deleteAction();
    $request = Request::create('/');

    $result = (new ListActionResource($action))->toArray($request);

    expect($result['action'])->toBe('delete');
});

// ── Флаги ──────────────────────────────────────────────────────────────────────

it('blank по умолчанию false', function () {
    $action = Action::make('Просмотр')->showAction();
    $request = Request::create('/');

    $result = (new ListActionResource($action))->toArray($request);

    expect($result['blank'])->toBeFalse();
});

it('blank равен true при вызове blank()', function () {
    $action = Action::make('Открыть')->showAction()->blank();
    $request = Request::create('/');

    $result = (new ListActionResource($action))->toArray($request);

    expect($result['blank'])->toBeTrue();
});

it('default по умолчанию false', function () {
    $action = Action::make('Просмотр')->showAction();
    $request = Request::create('/');

    $result = (new ListActionResource($action))->toArray($request);

    expect($result['default'])->toBeFalse();
});

it('default равен true при вызове default()', function () {
    $action = Action::make('Просмотр')->showAction()->default();
    $request = Request::create('/');

    $result = (new ListActionResource($action))->toArray($request);

    expect($result['default'])->toBeTrue();
});

// ── Специальные типы действий ─────────────────────────────────────────────────

it('сериализует link-действие с type link', function () {
    $action = Action::make('Перейти')->setLinkAction('/custom/path');
    $request = Request::create('/');

    $result = (new ListActionResource($action))->toArray($request);

    expect($result['type'])->toBe('link');
    expect($result['action'])->toBe('/custom/path');
});

it('сериализует js-действие с type js', function () {
    $action = Action::make('Копировать')->setJsAction('copy(item_id)');
    $request = Request::create('/');

    $result = (new ListActionResource($action))->toArray($request);

    expect($result['type'])->toBe('js');
    expect($result['action'])->toBe('copy(item_id)');
});

it('содержит все обязательные ключи', function () {
    $action = Action::make('Просмотр')->showAction();
    $request = Request::create('/');

    $result = (new ListActionResource($action))->toArray($request);

    expect($result)->toHaveKeys(['name', 'type', 'action', 'blank', 'default']);
});
