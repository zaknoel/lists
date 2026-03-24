<?php

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Zak\Lists\Actions\UpdateAction;
use Zak\Lists\Tests\Fixtures\Models\TestUser;

beforeEach(function () {
    $this->user = TestUser::factory()->create();
    Auth::login($this->user);
    $this->action = app(UpdateAction::class);
});

it('handle() обновляет элемент и возвращает редирект', function () {
    $item = TestUser::factory()->create(['name' => 'Старое']);
    $request = Request::create(
        route('lists_update', ['list' => 'test-users', 'item' => $item->id]),
        'PUT',
        ['name' => 'Новое', 'email' => $item->email]
    );

    $result = $this->action->handle($request, 'test-users', $item->id);

    expect($result)->toBeInstanceOf(RedirectResponse::class);
    $this->assertDatabaseHas('test_users', ['id' => $item->id, 'name' => 'Новое']);
});

it('редирект содержит js_success flash', function () {
    $item = TestUser::factory()->create();
    $request = Request::create(
        route('lists_update', ['list' => 'test-users', 'item' => $item->id]),
        'PUT',
        ['name' => 'Updated', 'email' => $item->email]
    );

    $result = $this->action->handle($request, 'test-users', $item->id);

    expect($result->getSession()->get('js_success'))->not->toBeEmpty();
});

it('вызывает ValidationException при невалидных данных', function () {
    $item = TestUser::factory()->create();
    $request = Request::create(
        route('lists_update', ['list' => 'test-users', 'item' => $item->id]),
        'PUT',
        ['name' => '', 'email' => $item->email]
    );

    expect(fn () => $this->action->handle($request, 'test-users', $item->id))
        ->toThrow(ValidationException::class);
});

it('возвращает 404 для несуществующего элемента', function () {
    $request = Request::create(
        route('lists_update', ['list' => 'test-users', 'item' => 99999]),
        'PUT',
        ['name' => 'Test', 'email' => 'test@test.com']
    );

    expect(fn () => $this->action->handle($request, 'test-users', 99999))
        ->toThrow(ModelNotFoundException::class);
});

it('возвращает 403 если нет прав на обновление', function () {
    $item = TestUser::factory()->create();
    $restricted = new class extends TestUser
    {
        public function can($abilities, $arguments = []): bool
        {
            return false;
        }
    };
    Auth::login($restricted);

    $request = Request::create(
        route('lists_update', ['list' => 'test-users', 'item' => $item->id]),
        'PUT',
        ['name' => 'Test', 'email' => $item->email]
    );

    expect(fn () => $this->action->handle($request, 'test-users', $item->id))
        ->toThrow(HttpException::class);
});

it('frame=1 возвращает View вместо редиректа', function () {
    $item = TestUser::factory()->create();
    $request = Request::create(
        route('lists_update', ['list' => 'test-users', 'item' => $item->id]).'?frame=1',
        'PUT',
        ['name' => 'Frame', 'email' => $item->email, 'frame' => 1]
    );

    $result = $this->action->handle($request, 'test-users', $item->id);

    expect($result)->toBeInstanceOf(View::class);
});

it('данные обновляются корректно в базе', function () {
    $item = TestUser::factory()->create(['name' => 'Первое', 'active' => false]);
    $request = Request::create(
        route('lists_update', ['list' => 'test-users', 'item' => $item->id]),
        'PUT',
        ['name' => 'Второе', 'email' => $item->email, 'active' => true]
    );

    $this->action->handle($request, 'test-users', $item->id);

    $updated = TestUser::find($item->id);
    expect($updated->name)->toBe('Второе');
});
