<?php

use Illuminate\Support\Facades\Auth;
use Zak\Lists\Tests\Fixtures\Models\TestUser;

beforeEach(function () {
    $this->actor = TestUser::factory()->create();
    $this->actingAs($this->actor);
});

// ── Выполнение bulk action ────────────────────────────────────────────────────

it('deactivate bulk action деактивирует выбранных пользователей', function () {
    $items = TestUser::factory()->count(3)->create(['active' => true]);
    $ids = $items->pluck('id')->toArray();

    $this->post(route('lists_action', 'bulk-test'), [
        'action' => 'deactivate',
        'items' => $ids,
    ])
        ->assertRedirect()
        ->assertSessionHas('js_success');

    expect(TestUser::whereIn('id', $ids)->where('active', false)->count())->toBe(3);
});

it('activate bulk action активирует выбранных пользователей', function () {
    $items = TestUser::factory()->count(2)->create(['active' => false]);
    $ids = $items->pluck('id')->toArray();

    $this->post(route('lists_action', 'bulk-test'), [
        'action' => 'activate',
        'items' => $ids,
    ])
        ->assertRedirect()
        ->assertSessionHas('js_success');

    expect(TestUser::whereIn('id', $ids)->where('active', true)->count())->toBe(2);
});

it('bulk action применяется только к переданным элементам', function () {
    $targets = TestUser::factory()->count(2)->create(['active' => true]);
    $other = TestUser::factory()->create(['active' => true]);

    $this->post(route('lists_action', 'bulk-test'), [
        'action' => 'deactivate',
        'items' => $targets->pluck('id')->toArray(),
    ]);

    expect((bool) TestUser::find($other->id)->active)->toBeTrue();
});

// ── Ошибки ────────────────────────────────────────────────────────────────────

it('незарегистрированное action возвращает js_error', function () {
    $this->post(route('lists_action', 'bulk-test'), [
        'action' => 'unknown-action',
        'items' => [1],
    ])
        ->assertRedirect()
        ->assertSessionHas('js_error');
});

it('action с exception возвращает js_error', function () {
    $item = TestUser::factory()->create();

    $this->post(route('lists_action', 'bulk-test'), [
        'action' => 'throw-error',
        'items' => [$item->id],
    ])
        ->assertRedirect()
        ->assertSessionHas('js_error');
});

// ── Валидация ─────────────────────────────────────────────────────────────────

it('bulk action без action поля возвращает ошибку валидации', function () {
    $this->post(route('lists_action', 'bulk-test'), [
        'items' => [1],
    ])->assertSessionHasErrors('action');
});

it('bulk action без items поля возвращает ошибку валидации', function () {
    $this->post(route('lists_action', 'bulk-test'), [
        'action' => 'deactivate',
    ])->assertSessionHasErrors('items');
});

// ── Авторизация ───────────────────────────────────────────────────────────────

it('возвращает 403 если нет прав', function () {
    $restricted = new class extends TestUser
    {
        public function can($abilities, $arguments = []): bool
        {
            return false;
        }
    };
    Auth::login($restricted);

    $this->post(route('lists_action', 'bulk-test'), [
        'action' => 'deactivate',
        'items' => [1],
    ])->assertForbidden();
});
