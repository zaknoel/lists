<?php

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Zak\Lists\Actions\SaveOptionsAction;
use Zak\Lists\Models\UserOption;
use Zak\Lists\Tests\Fixtures\Models\TestUser;

beforeEach(function () {
    $this->user = TestUser::factory()->create();
    Auth::login($this->user);
    $this->action = app(SaveOptionsAction::class);
});

// ── Сохранение настроек ───────────────────────────────────────────────────────

it('handle() возвращает редирект', function () {
    $request = Request::create(route('lists_option', 'test-users'), 'POST', [
        'columns' => ['name' => 'on', 'email' => 'on'],
        'filters' => [],
        'sort' => [],
    ]);

    $result = $this->action->handle($request, 'test-users');

    expect($result)->toBeInstanceOf(RedirectResponse::class);
});

it('сохраняет колонки в UserOption', function () {
    $request = Request::create(route('lists_option', 'test-users'), 'POST', [
        'columns' => ['name' => 'on', 'email' => 'on'],
        'filters' => [],
        'sort' => [],
    ]);

    $this->action->handle($request, 'test-users');

    $option = UserOption::where('user_id', $this->user->id)
        ->where('name', TestUser::class)
        ->first();

    expect($option->value['columns'])->toContain('name');
    expect($option->value['columns'])->toContain('email');
});

it('сохраняет фильтры в UserOption', function () {
    $request = Request::create(route('lists_option', 'test-users'), 'POST', [
        'columns' => [],
        'filters' => ['active' => 'on'],
        'sort' => [],
    ]);

    $this->action->handle($request, 'test-users');

    $option = UserOption::where('user_id', $this->user->id)
        ->where('name', TestUser::class)
        ->first();

    expect($option->value['filters'])->toContain('active');
});

it('сохраняет порядок сортировки в UserOption', function () {
    $request = Request::create(route('lists_option', 'test-users'), 'POST', [
        'columns' => [],
        'filters' => [],
        'sort' => ['name', 'email'],
    ]);

    $this->action->handle($request, 'test-users');

    $option = UserOption::where('user_id', $this->user->id)
        ->where('name', TestUser::class)
        ->first();

    expect($option->value['sort'])->toBe(['name', 'email']);
});

it('редирект содержит js_success flash', function () {
    $request = Request::create(route('lists_option', 'test-users'), 'POST', [
        'columns' => [],
        'filters' => [],
        'sort' => [],
    ]);

    $result = $this->action->handle($request, 'test-users');

    expect($result->getSession()->get('js_success'))->not->toBeEmpty();
});

it('пустые columns сохраняются как пустой массив', function () {
    $request = Request::create(route('lists_option', 'test-users'), 'POST', [
        'columns' => [],
        'filters' => [],
        'sort' => [],
    ]);

    $this->action->handle($request, 'test-users');

    $option = UserOption::where('user_id', $this->user->id)
        ->where('name', TestUser::class)
        ->first();

    expect($option->value['columns'])->toBeArray()->toBeEmpty();
});

it('сохранение настроек не влияет на данные другого пользователя', function () {
    $otherUser = TestUser::factory()->create();

    // Сохраняем для текущего пользователя
    $request = Request::create(route('lists_option', 'test-users'), 'POST', [
        'columns' => ['name' => 'on'],
        'filters' => [],
        'sort' => [],
    ]);
    $this->action->handle($request, 'test-users');

    // Логинимся как другой пользователь и проверяем
    Auth::login($otherUser);
    $option = UserOption::where('user_id', $otherUser->id)
        ->where('name', TestUser::class)
        ->first();

    // У другого пользователя нет сохранённых колонок (кроме дефолтных)
    expect($option?->value['columns'] ?? [])->toBeEmpty();
});
