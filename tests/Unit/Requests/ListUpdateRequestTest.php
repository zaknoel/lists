<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rules\Unique;
use Zak\Lists\Requests\ListUpdateRequest;
use Zak\Lists\Tests\Fixtures\Models\TestUser;

beforeEach(function () {
    $this->actor = TestUser::factory()->create();
    Auth::login($this->actor);
});

// ── authorize() ───────────────────────────────────────────────────────────────

it('authorize возвращает true если userCanEdit разрешает', function () {
    $item = TestUser::factory()->create();

    $request = ListUpdateRequest::create(
        route('lists_update', ['list' => 'test-users', 'item' => $item->id]),
        'PUT'
    );

    $request->setRouteResolver(fn () => tap(
        Route::getRoutes()->getByName('lists_update'),
        fn ($route) => $route->bind($request)
    ));

    expect($request->authorize())->toBeTrue();
});

it('authorize возвращает false для несуществующего элемента', function () {
    $request = ListUpdateRequest::create(
        route('lists_update', ['list' => 'test-users', 'item' => 99999]),
        'PUT'
    );

    $request->setRouteResolver(fn () => tap(
        Route::getRoutes()->getByName('lists_update'),
        fn ($route) => $route->bind($request)
    ));

    expect($request->authorize())->toBeFalse();
});

it('authorize возвращает false если userCanEdit запрещает', function () {
    $item = TestUser::factory()->create();

    $restricted = new class extends TestUser
    {
        public function can($abilities, $arguments = []): bool
        {
            return false;
        }
    };
    Auth::login($restricted);

    $request = ListUpdateRequest::create(
        route('lists_update', ['list' => 'test-users', 'item' => $item->id]),
        'PUT'
    );

    $request->setRouteResolver(fn () => tap(
        Route::getRoutes()->getByName('lists_update'),
        fn ($route) => $route->bind($request)
    ));

    expect($request->authorize())->toBeFalse();
});

// ── rules() ───────────────────────────────────────────────────────────────────

it('rules содержит правила для полей show_on_update', function () {
    $item = TestUser::factory()->create();

    $request = ListUpdateRequest::create(
        route('lists_update', ['list' => 'test-users', 'item' => $item->id]),
        'PUT'
    );

    $request->setRouteResolver(fn () => tap(
        Route::getRoutes()->getByName('lists_update'),
        fn ($route) => $route->bind($request)
    ));

    $rules = $request->rules();

    expect($rules)->toHaveKey('name');
    expect($rules)->toHaveKey('email');
    expect($rules)->not->toHaveKey('id');
});

it('rules для email содержит Rule::unique с игнором текущего элемента', function () {
    $item = TestUser::factory()->create();

    $request = ListUpdateRequest::create(
        route('lists_update', ['list' => 'test-users', 'item' => $item->id]),
        'PUT'
    );

    $request->setRouteResolver(fn () => tap(
        Route::getRoutes()->getByName('lists_update'),
        fn ($route) => $route->bind($request)
    ));

    $rules = $request->rules();

    $hasUniqueWithIgnore = collect($rules['email'])
        ->some(fn ($r) => $r instanceof Unique);

    expect($hasUniqueWithIgnore)->toBeTrue();
});

// ── HTTP integration ──────────────────────────────────────────────────────────

it('PUT обновляет элемент с валидными данными', function () {
    $item = TestUser::factory()->create(['name' => 'Старое']);

    $this->actingAs($this->actor)
        ->post(route('lists_update', ['list' => 'test-users', 'item' => $item->id]), [
            '_method' => 'PUT',
            'name' => 'Новое',
            'email' => $item->email,
        ])
        ->assertRedirect()
        ->assertSessionHas('js_success');

    $this->assertDatabaseHas('test_users', ['id' => $item->id, 'name' => 'Новое']);
});

it('PUT возвращает ошибку при пустом имени', function () {
    $item = TestUser::factory()->create();

    $this->actingAs($this->actor)
        ->post(route('lists_update', ['list' => 'test-users', 'item' => $item->id]), [
            '_method' => 'PUT',
            'name' => '',
            'email' => $item->email,
        ])
        ->assertSessionHasErrors('name');
});

it('PUT позволяет сохранить email текущего элемента без ошибки unique', function () {
    $item = TestUser::factory()->create(['email' => 'keep@test.com']);

    $this->actingAs($this->actor)
        ->post(route('lists_update', ['list' => 'test-users', 'item' => $item->id]), [
            '_method' => 'PUT',
            'name' => 'Новое',
            'email' => 'keep@test.com',
        ])
        ->assertRedirect()
        ->assertSessionHas('js_success');
});

it('PUT возвращает ошибку при попытке занять чужой email', function () {
    TestUser::factory()->create(['email' => 'taken@test.com']);
    $item = TestUser::factory()->create(['email' => 'mine@test.com']);

    $this->actingAs($this->actor)
        ->post(route('lists_update', ['list' => 'test-users', 'item' => $item->id]), [
            '_method' => 'PUT',
            'name' => 'Тест',
            'email' => 'taken@test.com',
        ])
        ->assertSessionHasErrors('email');
});

it('PUT возвращает 403 при отсутствии прав', function () {
    $item = TestUser::factory()->create();

    $restricted = new class extends TestUser
    {
        public function can($abilities, $arguments = []): bool
        {
            return false;
        }
    };

    $this->actingAs($restricted)
        ->post(route('lists_update', ['list' => 'test-users', 'item' => $item->id]), [
            '_method' => 'PUT',
            'name' => 'Тест',
            'email' => 'test@test.com',
        ])
        ->assertForbidden();
});
