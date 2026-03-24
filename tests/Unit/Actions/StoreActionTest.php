<?php

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Zak\Lists\Actions\StoreAction;
use Zak\Lists\Tests\Fixtures\Models\TestUser;

beforeEach(function () {
    $this->user = TestUser::factory()->create();
    Auth::login($this->user);
    $this->action = app(StoreAction::class);
});

// ── Сохранение ────────────────────────────────────────────────────────────────

it('handle() создаёт новый элемент и возвращает редирект', function () {
    $request = Request::create(route('lists_store', 'test-users'), 'POST', [
        'name' => 'Новый юзер',
        'email' => 'store@test.com',
        'active' => true,
    ]);

    $result = $this->action->handle($request, 'test-users');

    expect($result)->toBeInstanceOf(RedirectResponse::class);
    $this->assertDatabaseHas('test_users', ['email' => 'store@test.com']);
});

it('редирект содержит js_success flash', function () {
    $request = Request::create(route('lists_store', 'test-users'), 'POST', [
        'name' => 'Flash Test',
        'email' => 'flash@test.com',
    ]);

    $result = $this->action->handle($request, 'test-users');

    expect($result->getSession()->get('js_success'))->not->toBeEmpty();
});

it('редирект ведёт на страницу списка', function () {
    $request = Request::create(route('lists_store', 'test-users'), 'POST', [
        'name' => 'Redirect Test',
        'email' => 'redirect@test.com',
    ]);

    $result = $this->action->handle($request, 'test-users');

    expect($result->getTargetUrl())->toContain('test-users');
});

it('вызывает ValidationException при невалидных данных', function () {
    $request = Request::create(route('lists_store', 'test-users'), 'POST', [
        'name' => '',   // required
        'email' => 'bad-email',
    ]);

    expect(fn () => $this->action->handle($request, 'test-users'))
        ->toThrow(ValidationException::class);
});

it('возвращает 403 если нет прав на создание', function () {
    $restricted = new class extends TestUser
    {
        public function can($abilities, $arguments = []): bool
        {
            return false;
        }
    };
    Auth::login($restricted);

    $request = Request::create(route('lists_store', 'test-users'), 'POST', [
        'name' => 'Test',
        'email' => 'test@test.com',
    ]);

    expect(fn () => $this->action->handle($request, 'test-users'))
        ->toThrow(HttpException::class);
});

it('frame=1 возвращает View вместо редиректа', function () {
    $request = Request::create(route('lists_store', 'test-users').'?frame=1', 'POST', [
        'name' => 'Frame User',
        'email' => 'frame@test.com',
        'frame' => 1,
    ]);

    $result = $this->action->handle($request, 'test-users');

    expect($result)->toBeInstanceOf(View::class);
});

it('OnBeforeSave колбэк вызывается перед сохранением', function () {
    // тест через HTTP feature - этот факт покрыт FieldServiceTest
    expect(true)->toBeTrue();
})->skip('Покрыто в FieldServiceTest (eventOnBeforeSave)');
