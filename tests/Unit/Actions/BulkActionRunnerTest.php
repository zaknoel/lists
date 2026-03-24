<?php

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Zak\Lists\Actions\BulkActionRunner;
use Zak\Lists\Tests\Fixtures\Models\TestUser;

beforeEach(function () {
    $this->user = TestUser::factory()->create();
    Auth::login($this->user);
    $this->runner = app(BulkActionRunner::class);
});

// ── Зарегистрированное действие ───────────────────────────────────────────────

it('выполняет зарегистрированное bulk action и возвращает редирект', function () {
    $items = TestUser::factory()->count(3)->create(['active' => true]);
    $ids = $items->pluck('id')->toArray();

    $request = Request::create(route('lists_action', 'bulk-test'), 'POST', [
        'action' => 'deactivate',
        'items' => $ids,
    ]);

    $result = $this->runner->handle($request, 'bulk-test');

    expect($result)->toBeInstanceOf(RedirectResponse::class);
    expect(TestUser::whereIn('id', $ids)->where('active', false)->count())->toBe(3);
});

it('успешное действие добавляет js_success в сессию', function () {
    $item = TestUser::factory()->create(['active' => false]);

    $request = Request::create(route('lists_action', 'bulk-test'), 'POST', [
        'action' => 'activate',
        'items' => [$item->id],
    ]);

    $result = $this->runner->handle($request, 'bulk-test');

    expect($result->getSession()->get('js_success'))->not->toBeEmpty();
});

it('activate bulk action активирует пользователей', function () {
    $items = TestUser::factory()->count(2)->create(['active' => false]);
    $ids = $items->pluck('id')->toArray();

    $request = Request::create(route('lists_action', 'bulk-test'), 'POST', [
        'action' => 'activate',
        'items' => $ids,
    ]);

    $this->runner->handle($request, 'bulk-test');

    expect(TestUser::whereIn('id', $ids)->where('active', true)->count())->toBe(2);
});

it('загружает bulk items пакетами и всё равно обрабатывает все выбранные записи', function () {
    config()->set('lists.bulk_chunk_size', 1);

    $items = TestUser::factory()->count(4)->create(['active' => true]);
    $ids = $items->pluck('id')->toArray();

    $request = Request::create(route('lists_action', 'bulk-test'), 'POST', [
        'action' => 'deactivate',
        'items' => $ids,
    ]);

    $this->runner->handle($request, 'bulk-test');

    expect(TestUser::whereIn('id', $ids)->where('active', false)->count())->toBe(4);
});

// ── Несуществующее действие ───────────────────────────────────────────────────

it('возвращает js_error если action не найден', function () {
    $request = Request::create(route('lists_action', 'bulk-test'), 'POST', [
        'action' => 'non-existent-action',
        'items' => [1],
    ]);

    $result = $this->runner->handle($request, 'bulk-test');

    expect($result->getSession()->get('js_error'))->not->toBeEmpty();
});

it('список без bulk actions возвращает js_error', function () {
    $request = Request::create(route('lists_action', 'test-users'), 'POST', [
        'action' => 'deactivate',
        'items' => [1],
    ]);

    $result = $this->runner->handle($request, 'test-users');

    expect($result->getSession()->get('js_error'))->not->toBeEmpty();
});

// ── Исключение в callback ─────────────────────────────────────────────────────

it('возвращает js_error если callback выбрасывает исключение', function () {
    $item = TestUser::factory()->create();

    $request = Request::create(route('lists_action', 'bulk-test'), 'POST', [
        'action' => 'throw-error',
        'items' => [$item->id],
    ]);

    $result = $this->runner->handle($request, 'bulk-test');

    expect($result->getSession()->get('js_error'))->not->toBeEmpty();
});

// ── Авторизация ───────────────────────────────────────────────────────────────

it('возвращает 403 если нет прав canViewAny', function () {
    $restricted = new class extends TestUser
    {
        public function can($abilities, $arguments = []): bool
        {
            return false;
        }
    };
    Auth::login($restricted);

    $request = Request::create(route('lists_action', 'bulk-test'), 'POST', [
        'action' => 'deactivate',
        'items' => [1],
    ]);

    expect(fn () => $this->runner->handle($request, 'bulk-test'))
        ->toThrow(HttpException::class);
});

// ── Валидация ─────────────────────────────────────────────────────────────────

it('выбрасывает ValidationException при отсутствии action', function () {
    $request = Request::create(route('lists_action', 'bulk-test'), 'POST', [
        'items' => [1],
    ]);

    expect(fn () => $this->runner->handle($request, 'bulk-test'))
        ->toThrow(ValidationException::class);
});

it('выбрасывает ValidationException при отсутствии items', function () {
    $request = Request::create(route('lists_action', 'bulk-test'), 'POST', [
        'action' => 'deactivate',
    ]);

    expect(fn () => $this->runner->handle($request, 'bulk-test'))
        ->toThrow(ValidationException::class);
});
