<?php

use Zak\Lists\Tests\Fixtures\Models\TestUser;

beforeEach(function () {
    $this->actor = TestUser::factory()->create();
    $this->actingAs($this->actor);
});

// ── SAVE OPTIONS ──────────────────────────────────────────────────────────

it('сохраняет настройки колонок пользователя', function () {
    $this->post(route('lists_option', 'test-users'), [
        'columns' => ['name' => 'on', 'email' => 'on'],
        'filters' => [],
        'sort' => ['name', 'email'],
    ])
        ->assertRedirect()
        ->assertSessionHas('js_success');
});

// ── BULK ACTIONS ──────────────────────────────────────────────────────────

it('выполняет групповое действие', function () {
    $items = TestUser::factory()->count(3)->create(['active' => true]);
    $ids = $items->pluck('id')->toArray();

    // Используем список с bulk-action из отдельного fixture
    // (для тестирования bulk-action создадим компонент программно в TestCase)
    // Здесь проверяем только валидацию endpoint'а
    $response = $this->post(route('lists_action', 'test-users'), [
        'action' => 'deactivate',
        'items' => $ids,
    ]);

    // Без зарегистрированного bulk action должен вернуть ошибку
    $response->assertRedirect();
    $response->assertSessionHas('js_error');
});

it('валидирует обязательные поля для группового действия', function () {
    $this->post(route('lists_action', 'test-users'), [
        // Без action и items
    ])->assertSessionHasErrors(['action', 'items']);
});

// ── AUTHORIZATION (через ComponentLoader) ─────────────────────────────────

it('возвращает 404 для несуществующего компонента', function () {
    $this->get(route('lists_add', 'component-does-not-exist'))
        ->assertNotFound();
});
