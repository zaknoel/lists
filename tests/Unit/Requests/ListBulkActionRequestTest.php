<?php

use Illuminate\Support\Facades\Auth;
use Zak\Lists\Tests\Fixtures\Models\TestUser;

beforeEach(function () {
    $this->actor = TestUser::factory()->create();
    Auth::login($this->actor);
});

// ── rules ─────────────────────────────────────────────────────────────────────

it('валидирует обязательность action', function () {
    $this->actingAs($this->actor)
        ->post(route('lists_action', 'test-users'), [
            'items' => [1, 2],
        ])
        ->assertSessionHasErrors('action');
});

it('валидирует обязательность items', function () {
    $this->actingAs($this->actor)
        ->post(route('lists_action', 'test-users'), [
            'action' => 'deactivate',
        ])
        ->assertSessionHasErrors('items');
});

it('items должен быть массивом', function () {
    $this->actingAs($this->actor)
        ->post(route('lists_action', 'test-users'), [
            'action' => 'deactivate',
            'items' => 'not-array',
        ])
        ->assertSessionHasErrors('items');
});

it('items.* должны быть целыми числами', function () {
    $this->actingAs($this->actor)
        ->post(route('lists_action', 'test-users'), [
            'action' => 'deactivate',
            'items' => ['abc', 'xyz'],
        ])
        ->assertSessionHasErrors('items.0');
});

it('возвращает js_error для неизвестного action', function () {
    $items = TestUser::factory()->count(2)->create();

    $this->actingAs($this->actor)
        ->post(route('lists_action', 'test-users'), [
            'action' => 'unknown_action',
            'items' => $items->pluck('id')->toArray(),
        ])
        ->assertRedirect()
        ->assertSessionHas('js_error');
});

it('возвращает 403 при отсутствии прав', function () {
    $restricted = new class extends TestUser
    {
        public function can($abilities, $arguments = []): bool
        {
            return false;
        }
    };

    $this->actingAs($restricted)
        ->post(route('lists_action', 'test-users'), [
            'action' => 'deactivate',
            'items' => [1],
        ])
        ->assertForbidden();
});
