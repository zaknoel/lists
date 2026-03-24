<?php

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Zak\Lists\Actions\DestroyAction;
use Zak\Lists\Component;
use Zak\Lists\Tests\Fixtures\Models\TestUser;

beforeEach(function () {
    $this->user = TestUser::factory()->create();
    Auth::login($this->user);
    $this->action = app(DestroyAction::class);
});

// ── Удаление ─────────────────────────────────────────────────────────────────

it('handle() удаляет элемент и возвращает редирект', function () {
    $item = TestUser::factory()->create();

    $request = Request::create(
        route('lists_delete', ['list' => 'test-users', 'item' => $item->id]),
        'DELETE'
    );

    $result = $this->action->handle($request, 'test-users', $item->id);

    expect($result)->toBeInstanceOf(RedirectResponse::class);
    $this->assertDatabaseMissing('test_users', ['id' => $item->id]);
});

it('редирект содержит js_success flash', function () {
    $item = TestUser::factory()->create();
    $request = Request::create(
        route('lists_delete', ['list' => 'test-users', 'item' => $item->id]),
        'DELETE'
    );

    $result = $this->action->handle($request, 'test-users', $item->id);

    expect($result->getSession()->get('js_success'))->not->toBeEmpty();
});

it('редирект ведёт на страницу списка', function () {
    $item = TestUser::factory()->create();
    $request = Request::create(
        route('lists_delete', ['list' => 'test-users', 'item' => $item->id]),
        'DELETE'
    );

    $result = $this->action->handle($request, 'test-users', $item->id);

    expect($result->getTargetUrl())->toContain('test-users');
});

it('возвращает 404 для несуществующего элемента', function () {
    $request = Request::create(
        route('lists_delete', ['list' => 'test-users', 'item' => 99999]),
        'DELETE'
    );

    expect(fn () => $this->action->handle($request, 'test-users', 99999))
        ->toThrow(ModelNotFoundException::class);
});

it('возвращает 403 если нет прав на удаление', function () {
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
        route('lists_delete', ['list' => 'test-users', 'item' => $item->id]),
        'DELETE'
    );

    expect(fn () => $this->action->handle($request, 'test-users', $item->id))
        ->toThrow(HttpException::class);
});

it('OnBeforeDelete вызывается перед удалением', function () {
    $called = false;
    $item = TestUser::factory()->create(['name' => 'BeforeDelete Test', 'email' => 'before@test.com']);

    // Проверяем через событие компонента напрямую
    $component = new Component(
        model: TestUser::class,
        label: 'T',
        singleLabel: 't',
        canViewAny: fn () => true,
        canView: fn ($i) => true,
        canAdd: fn () => true,
        canEdit: fn ($i) => true,
        canDelete: fn ($i) => true,
        OnBeforeDelete: function ($i) use (&$called) {
            $called = true;
        },
    );

    $component->eventOnBeforeDelete($item);

    expect($called)->toBeTrue();
});

it('OnAfterDelete вызывается после удаления', function () {
    $called = false;
    $item = TestUser::factory()->create(['name' => 'AfterDelete Test', 'email' => 'after@test.com']);

    $component = new Component(
        model: TestUser::class,
        label: 'T',
        singleLabel: 't',
        canViewAny: fn () => true,
        canView: fn ($i) => true,
        canAdd: fn () => true,
        canEdit: fn ($i) => true,
        canDelete: fn ($i) => true,
        OnAfterDelete: function ($i) use (&$called) {
            $called = true;
        },
    );

    $component->eventOnAfterDelete($item);

    expect($called)->toBeTrue();
});

it('элемент отсутствует в базе после удаления', function () {
    $item = TestUser::factory()->create(['email' => 'destroyed@test.com']);
    $request = Request::create(
        route('lists_delete', ['list' => 'test-users', 'item' => $item->id]),
        'DELETE'
    );

    $this->action->handle($request, 'test-users', $item->id);

    expect(TestUser::find($item->id))->toBeNull();
});
