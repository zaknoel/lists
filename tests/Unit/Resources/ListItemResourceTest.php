<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Zak\Lists\Action;
use Zak\Lists\Component;
use Zak\Lists\Fields\Text;
use Zak\Lists\Resources\ListItemResource;
use Zak\Lists\Tests\Fixtures\Models\TestUser;

beforeEach(function () {
    $this->user = TestUser::factory()->create();
    Auth::login($this->user);
});

// ── Атрибуты ─────────────────────────────────────────────────────────────────

it('сериализует id элемента', function () {
    $item = TestUser::factory()->create(['name' => 'Иван', 'email' => 'ivan@test.com']);
    $component = makeItemComponent();
    $request = Request::create('/');

    $result = (new ListItemResource($item, $component))->toArray($request);

    expect($result['id'])->toBe($item->id);
});

it('сериализует атрибуты из полей компонента', function () {
    $item = TestUser::factory()->create(['name' => 'Иван', 'email' => 'ivan@test.com']);
    $component = makeItemComponent();
    $request = Request::create('/');

    $result = (new ListItemResource($item, $component))->toArray($request);

    expect($result['attributes'])->toHaveKey('name');
    expect($result['attributes']['name'])->toBe('Иван');
    expect($result['attributes'])->toHaveKey('email');
    expect($result['attributes']['email'])->toBe('ivan@test.com');
});

it('использует переданные поля вместо полей компонента', function () {
    $item = TestUser::factory()->create(['name' => 'Иван', 'email' => 'ivan@test.com']);
    $component = makeItemComponent();
    $nameField = Text::make('Имя', 'name');
    $request = Request::create('/');

    $result = (new ListItemResource($item, $component, [$nameField]))->toArray($request);

    expect($result['attributes'])->toHaveKey('name');
    expect($result['attributes'])->not->toHaveKey('email');
});

// ── Мета-права ───────────────────────────────────────────────────────────────

it('включает мета-права can_view, can_edit, can_delete', function () {
    $item = TestUser::factory()->create();
    $component = makeItemComponent(canView: fn ($i) => true, canEdit: fn ($i) => true, canDelete: fn ($i) => false);
    $request = Request::create('/');

    $result = (new ListItemResource($item, $component))->toArray($request);

    expect($result['meta']['permissions']['can_view'])->toBeTrue();
    expect($result['meta']['permissions']['can_edit'])->toBeTrue();
    expect($result['meta']['permissions']['can_delete'])->toBeFalse();
});

it('мета-права отражают реальные права компонента', function () {
    $item = TestUser::factory()->create();
    $component = makeItemComponent(canView: fn ($i) => false, canEdit: fn ($i) => false, canDelete: fn ($i) => false);
    $request = Request::create('/');

    $result = (new ListItemResource($item, $component))->toArray($request);

    expect($result['meta']['permissions']['can_view'])->toBeFalse();
    expect($result['meta']['permissions']['can_edit'])->toBeFalse();
    expect($result['meta']['permissions']['can_delete'])->toBeFalse();
});

// ── Мета-действия ─────────────────────────────────────────────────────────────

it('включает список действий в мета', function () {
    $item = TestUser::factory()->create();
    $component = makeItemComponent(canView: fn ($i) => true, canEdit: fn ($i) => true, canDelete: fn ($i) => true);
    $request = Request::create('/');

    $result = (new ListItemResource($item, $component))->toArray($request);

    expect($result['meta']['actions'])->toContain('show');
    expect($result['meta']['actions'])->toContain('edit');
    expect($result['meta']['actions'])->toContain('delete');
});

it('исключает действия, которые не видны пользователю', function () {
    $item = TestUser::factory()->create();
    $component = makeItemComponent(canView: fn ($i) => false, canEdit: fn ($i) => false, canDelete: fn ($i) => true);
    $request = Request::create('/');

    $result = (new ListItemResource($item, $component))->toArray($request);

    expect($result['meta']['actions'])->not->toContain('show');
    expect($result['meta']['actions'])->not->toContain('edit');
    expect($result['meta']['actions'])->toContain('delete');
});

// ── Вспомогательная функция ───────────────────────────────────────────────────

function makeItemComponent(
    ?Closure $canView = null,
    ?Closure $canEdit = null,
    ?Closure $canDelete = null,
): Component {
    return new Component(
        model: TestUser::class,
        label: 'Тест',
        singleLabel: 'тест',
        fields: [
            Text::make('Имя', 'name'),
            Text::make('Email', 'email'),
        ],
        actions: [
            Action::make('Просмотр')->showAction(),
            Action::make('Редактировать')->editAction(),
            Action::make('Удалить')->deleteAction(),
        ],
        canView: $canView ?? fn ($i) => true,
        canEdit: $canEdit ?? fn ($i) => true,
        canDelete: $canDelete ?? fn ($i) => true,
        canViewAny: fn () => true,
        canAdd: fn () => true,
    );
}
