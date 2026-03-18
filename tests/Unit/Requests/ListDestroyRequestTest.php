<?php

use Illuminate\Support\Facades\Auth;
use Zak\Lists\Tests\Fixtures\Models\TestUser;

beforeEach(function () {
    $this->actor = TestUser::factory()->create();
    Auth::login($this->actor);
});

// ── authorize + HTTP integration ──────────────────────────────────────────────

it('DELETE удаляет элемент при наличии прав', function () {
    $item = TestUser::factory()->create();

    $this->actingAs($this->actor)
        ->delete(route('lists_delete', ['list' => 'test-users', 'item' => $item->id]))
        ->assertRedirect()
        ->assertSessionHas('js_success');

    $this->assertDatabaseMissing('test_users', ['id' => $item->id]);
});

it('DELETE возвращает 404 для несуществующего элемента', function () {
    $this->actingAs($this->actor)
        ->delete(route('lists_delete', ['list' => 'test-users', 'item' => 99999]))
        ->assertNotFound();
});

it('DELETE возвращает 403 при отсутствии прав', function () {
    $item = TestUser::factory()->create();

    $restricted = new class extends TestUser
    {
        public function can($abilities, $arguments = []): bool
        {
            return false;
        }
    };

    $this->actingAs($restricted)
        ->delete(route('lists_delete', ['list' => 'test-users', 'item' => $item->id]))
        ->assertForbidden();
});

it('DELETE через POST с _method=DELETE работает корректно', function () {
    $item = TestUser::factory()->create();

    $this->actingAs($this->actor)
        ->post(
            route('lists_delete', ['list' => 'test-users', 'item' => $item->id]),
            ['_method' => 'DELETE']
        )
        ->assertRedirect()
        ->assertSessionHas('js_success');

    $this->assertDatabaseMissing('test_users', ['id' => $item->id]);
});
