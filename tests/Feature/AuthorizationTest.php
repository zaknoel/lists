<?php

use Illuminate\Support\Facades\Auth;
use Zak\Lists\Tests\Fixtures\Models\TestUser;

beforeEach(function () {
    $this->actor = TestUser::factory()->create();
    $this->actingAs($this->actor);
});

// ── INDEX — 403 ───────────────────────────────────────────────────────────────

it('index возвращает 403 если canViewAny запрещает', function () {
    $restricted = new class extends TestUser
    {
        public function can($abilities, $arguments = []): bool
        {
            return false;
        }
    };
    Auth::login($restricted);

    $this->get(route('lists', 'test-users'))->assertForbidden();
});

// ── SHOW — 403 / 404 ──────────────────────────────────────────────────────────

it('detail возвращает 404 для несуществующего элемента', function () {
    $this->get(route('lists_detail', ['list' => 'test-users', 'item' => 99999]))
        ->assertNotFound();
});

it('detail возвращает 403 если canView запрещает', function () {
    $item = TestUser::factory()->create();
    $restricted = new class extends TestUser
    {
        public function can($abilities, $arguments = []): bool
        {
            return false;
        }
    };
    Auth::login($restricted);

    $this->get(route('lists_detail', ['list' => 'test-users', 'item' => $item->id]))
        ->assertForbidden();
});

// ── CREATE — 403 ──────────────────────────────────────────────────────────────

it('add форма возвращает 403 если canAdd запрещает', function () {
    $restricted = new class extends TestUser
    {
        public function can($abilities, $arguments = []): bool
        {
            return false;
        }
    };
    Auth::login($restricted);

    $this->get(route('lists_add', 'test-users'))->assertForbidden();
});

it('store возвращает 403 если canAdd запрещает', function () {
    $restricted = new class extends TestUser
    {
        public function can($abilities, $arguments = []): bool
        {
            return false;
        }
    };
    Auth::login($restricted);

    $this->post(route('lists_store', 'test-users'), [
        'name' => 'Test',
        'email' => 'test@test.com',
    ])->assertForbidden();
});

// ── EDIT / UPDATE — 403 / 404 ─────────────────────────────────────────────────

it('edit форма возвращает 404 для несуществующего элемента', function () {
    $this->get(route('lists_edit', ['list' => 'test-users', 'item' => 99999]))
        ->assertNotFound();
});

it('edit форма возвращает 403 если canEdit запрещает', function () {
    $item = TestUser::factory()->create();
    $restricted = new class extends TestUser
    {
        public function can($abilities, $arguments = []): bool
        {
            return false;
        }
    };
    Auth::login($restricted);

    $this->get(route('lists_edit', ['list' => 'test-users', 'item' => $item->id]))
        ->assertForbidden();
});

it('update возвращает 403 если canEdit запрещает', function () {
    $item = TestUser::factory()->create();
    $restricted = new class extends TestUser
    {
        public function can($abilities, $arguments = []): bool
        {
            return false;
        }
    };
    Auth::login($restricted);

    $this->post(route('lists_update', ['list' => 'test-users', 'item' => $item->id]), [
        '_method' => 'PUT',
        'name' => 'New',
        'email' => $item->email,
    ])->assertForbidden();
});

// ── DELETE — 403 / 404 ────────────────────────────────────────────────────────

it('delete возвращает 404 для несуществующего элемента', function () {
    $this->delete(route('lists_delete', ['list' => 'test-users', 'item' => 99999]))
        ->assertNotFound();
});

it('delete возвращает 403 если canDelete запрещает', function () {
    $item = TestUser::factory()->create();
    $restricted = new class extends TestUser
    {
        public function can($abilities, $arguments = []): bool
        {
            return false;
        }
    };
    Auth::login($restricted);

    $this->delete(route('lists_delete', ['list' => 'test-users', 'item' => $item->id]))
        ->assertForbidden();
});

// ── COMPONENT NOT FOUND — 404 ─────────────────────────────────────────────────

it('возвращает 404 для несуществующего компонента на index', function () {
    $this->get(route('lists', 'no-such-component'))->assertNotFound();
});

it('возвращает 404 для несуществующего компонента на add', function () {
    $this->get(route('lists_add', 'no-such-component'))->assertNotFound();
});

it('возвращает 404 для несуществующего компонента на store', function () {
    $this->post(route('lists_store', 'no-such-component'), [])->assertNotFound();
});

it('возвращает 404 для несуществующего компонента на delete', function () {
    $this->delete(route('lists_delete', ['list' => 'no-such-component', 'item' => 1]))
        ->assertNotFound();
});
