<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Zak\Lists\Requests\ListStoreRequest;
use Zak\Lists\Tests\Fixtures\Models\TestUser;

beforeEach(function () {
    $this->actor = TestUser::factory()->create();
    Auth::login($this->actor);
});

// ── authorize() ───────────────────────────────────────────────────────────────

it('authorize возвращает true если userCanAdd разрешает', function () {
    $request = ListStoreRequest::create(
        route('lists_store', 'test-users'),
        'POST',
        ['name' => 'Test', 'email' => 'test@test.com']
    );

    $request->setRouteResolver(fn () => tap(
        Route::getRoutes()->getByName('lists_store'),
        fn ($route) => $route->bind($request)
    ));

    expect($request->authorize())->toBeTrue();
});

it('authorize возвращает false если userCanAdd запрещает', function () {
    $restricted = new class extends TestUser
    {
        public function can($abilities, $arguments = []): bool
        {
            return false;
        }
    };
    Auth::login($restricted);

    $request = ListStoreRequest::create(
        route('lists_store', 'test-users'),
        'POST'
    );

    $request->setRouteResolver(fn () => tap(
        Route::getRoutes()->getByName('lists_store'),
        fn ($route) => $route->bind($request)
    ));

    expect($request->authorize())->toBeFalse();
});

// ── rules() ───────────────────────────────────────────────────────────────────

it('rules содержит правила для полей show_on_add', function () {
    $request = ListStoreRequest::create(
        route('lists_store', 'test-users'),
        'POST'
    );

    $request->setRouteResolver(fn () => tap(
        Route::getRoutes()->getByName('lists_store'),
        fn ($route) => $route->bind($request)
    ));

    $rules = $request->rules();

    // name и email — поля с show_on_add=true; id — скрыт (hideOnForms)
    expect($rules)->toHaveKey('name');
    expect($rules)->toHaveKey('email');
    expect($rules)->not->toHaveKey('id');
});

it('rules содержит required для обязательных полей', function () {
    $request = ListStoreRequest::create(
        route('lists_store', 'test-users'),
        'POST'
    );

    $request->setRouteResolver(fn () => tap(
        Route::getRoutes()->getByName('lists_store'),
        fn ($route) => $route->bind($request)
    ));

    $rules = $request->rules();

    expect($rules['name'])->toContain('required');
    expect($rules['email'])->toContain('required');
});

// ── messages() ────────────────────────────────────────────────────────────────

it('messages содержит сообщения для обязательных полей', function () {
    $request = ListStoreRequest::create(
        route('lists_store', 'test-users'),
        'POST'
    );

    $request->setRouteResolver(fn () => tap(
        Route::getRoutes()->getByName('lists_store'),
        fn ($route) => $route->bind($request)
    ));

    $messages = $request->messages();

    expect($messages)->toHaveKey('name.required');
    expect($messages)->toHaveKey('email.required');
});

// ── HTTP integration ──────────────────────────────────────────────────────────

it('POST создаёт элемент с валидными данными', function () {
    $this->actingAs($this->actor)
        ->post(route('lists_store', 'test-users'), [
            'name' => 'Новый',
            'email' => 'new@test.com',
            'active' => true,
        ])
        ->assertRedirect()
        ->assertSessionHas('js_success');

    $this->assertDatabaseHas('test_users', ['email' => 'new@test.com']);
});

it('POST возвращает ошибки при пустом имени', function () {
    $this->actingAs($this->actor)
        ->post(route('lists_store', 'test-users'), [
            'name' => '',
            'email' => 'valid@test.com',
        ])
        ->assertSessionHasErrors('name');
});

it('POST возвращает ошибки при невалидном email (пустой)', function () {
    $this->actingAs($this->actor)
        ->post(route('lists_store', 'test-users'), [
            'name' => 'Тест',
            'email' => '',
        ])
        ->assertSessionHasErrors('email');
});

it('POST возвращает ошибку при дублирующемся email', function () {
    TestUser::factory()->create(['email' => 'dup@test.com']);

    $this->actingAs($this->actor)
        ->post(route('lists_store', 'test-users'), [
            'name' => 'Другой',
            'email' => 'dup@test.com',
        ])
        ->assertSessionHasErrors('email');
});

it('POST возвращает 403 при отсутствии прав', function () {
    $restricted = new class extends TestUser
    {
        public function can($abilities, $arguments = []): bool
        {
            return false;
        }
    };

    $this->actingAs($restricted)
        ->post(route('lists_store', 'test-users'), [
            'name' => 'Test',
            'email' => 'test@test.com',
        ])
        ->assertForbidden();
});
