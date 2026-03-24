<?php

use Illuminate\Support\Facades\Auth;
use Zak\Lists\Tests\Fixtures\Models\TestUser;

beforeEach(function () {
    $this->actor = TestUser::factory()->create();
    $this->actingAs($this->actor);
});

// ── INDEX ──────────────────────────────────────────────────────────────────

it('показывает страницу списка', function () {
    TestUser::factory()->count(3)->create();

    $this->get(route('lists', 'test-users'))
        ->assertOk();
});

it('возвращает 403 если нет прав на просмотр списка', function () {
    // Подменяем пользователя без прав
    $restricted = new class extends TestUser
    {
        public function can($abilities, $arguments = []): bool
        {
            return false;
        }
    };
    Auth::login($restricted);

    $this->get(route('lists', 'test-users'))
        ->assertForbidden();
});

// ── SHOW (DETAIL) ──────────────────────────────────────────────────────────

it('показывает детальную страницу элемента', function () {
    $item = TestUser::factory()->create();

    $this->get(route('lists_detail', ['list' => 'test-users', 'item' => $item->id]))
        ->assertOk();
});

it('возвращает 404 для несуществующего элемента на детальной странице', function () {
    $this->get(route('lists_detail', ['list' => 'test-users', 'item' => 99999]))
        ->assertNotFound();
});

// ── CREATE ─────────────────────────────────────────────────────────────────

it('показывает форму создания', function () {
    $this->get(route('lists_add', 'test-users'))
        ->assertOk();
});

// ── STORE ──────────────────────────────────────────────────────────────────

it('создаёт новый элемент', function () {
    $this->post(route('lists_store', 'test-users'), [
        'name' => 'Новый пользователь',
        'email' => 'new@example.com',
        'active' => true,
    ])
        ->assertRedirect()
        ->assertSessionHas('js_success');

    $this->assertDatabaseHas('test_users', [
        'name' => 'Новый пользователь',
        'email' => 'new@example.com',
    ]);
});

it('показывает ошибки валидации при создании с пустым именем', function () {
    $this->post(route('lists_store', 'test-users'), [
        'name' => '',
        'email' => 'test@example.com',
    ])
        ->assertSessionHasErrors('name');
});

it('не создаёт дублирующий email', function () {
    TestUser::factory()->create(['email' => 'existing@example.com']);

    $this->post(route('lists_store', 'test-users'), [
        'name' => 'Второй',
        'email' => 'existing@example.com',
    ])
        ->assertSessionHasErrors('email');
});

// ── EDIT ───────────────────────────────────────────────────────────────────

it('показывает форму редактирования', function () {
    $item = TestUser::factory()->create();

    $this->get(route('lists_edit', ['list' => 'test-users', 'item' => $item->id]))
        ->assertOk();
});

// ── UPDATE ─────────────────────────────────────────────────────────────────

it('обновляет существующий элемент', function () {
    $item = TestUser::factory()->create(['name' => 'Старое']);

    $this->post(route('lists_update', ['list' => 'test-users', 'item' => $item->id]), [
        '_method' => 'PUT',
        'name' => 'Новое',
        'email' => $item->email,
    ])
        ->assertRedirect()
        ->assertSessionHas('js_success');

    $this->assertDatabaseHas('test_users', ['id' => $item->id, 'name' => 'Новое']);
});

// ── DESTROY ────────────────────────────────────────────────────────────────

it('удаляет элемент', function () {
    $item = TestUser::factory()->create();

    $this->delete(route('lists_delete', ['list' => 'test-users', 'item' => $item->id]))
        ->assertRedirect()
        ->assertSessionHas('js_success');

    $this->assertDatabaseMissing('test_users', ['id' => $item->id]);
});

it('возвращает 404 при удалении несуществующего элемента', function () {
    $this->delete(route('lists_delete', ['list' => 'test-users', 'item' => 99999]))
        ->assertNotFound();
});

it('удаляет элемент через POST с _method=DELETE (HTML-форма)', function () {
    $item = TestUser::factory()->create();

    $this->post(
        route('lists_delete', ['list' => 'test-users', 'item' => $item->id]),
        ['_method' => 'DELETE']
    )
        ->assertRedirect()
        ->assertSessionHas('js_success');

    $this->assertDatabaseMissing('test_users', ['id' => $item->id]);
});
