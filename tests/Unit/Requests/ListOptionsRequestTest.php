<?php

use Illuminate\Support\Facades\Auth;
use Zak\Lists\Tests\Fixtures\Models\TestUser;

beforeEach(function () {
    $this->actor = TestUser::factory()->create();
    Auth::login($this->actor);
});

// ── rules ─────────────────────────────────────────────────────────────────────

it('правила содержат columns, sort, filters', function () {
    $response = $this->actingAs($this->actor)
        ->post(route('lists_option', 'test-users'), [
            'columns' => ['name' => 'on'],
            'filters' => [],
            'sort' => ['name'],
        ]);

    $response->assertRedirect()->assertSessionHas('js_success');
});

it('columns принимает null', function () {
    $this->actingAs($this->actor)
        ->post(route('lists_option', 'test-users'), [
            'sort' => [],
            'filters' => [],
        ])
        ->assertRedirect()
        ->assertSessionHas('js_success');
});

it('columns должен быть массивом или null', function () {
    $this->actingAs($this->actor)
        ->post(route('lists_option', 'test-users'), [
            'columns' => 'not-an-array',
        ])
        ->assertSessionHasErrors('columns');
});

it('sort должен быть массивом или null', function () {
    $this->actingAs($this->actor)
        ->post(route('lists_option', 'test-users'), [
            'sort' => 'not-an-array',
        ])
        ->assertSessionHasErrors('sort');
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
        ->post(route('lists_option', 'test-users'), [
            'columns' => [],
            'sort' => [],
            'filters' => [],
        ])
        ->assertForbidden();
});
