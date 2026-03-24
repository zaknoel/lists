<?php

use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Zak\Lists\Component;
use Zak\Lists\Services\AuthorizationService;
use Zak\Lists\Tests\Fixtures\Models\TestUser;

beforeEach(function () {
    $this->user = TestUser::factory()->create();
    Auth::login($this->user);

    $this->authService = new AuthorizationService;
});

it('разрешает просмотр списка если canViewAny возвращает true', function () {
    $component = makeComponent(canViewAny: fn () => true);

    expect(fn () => $this->authService->ensureCanViewAny($component))->not->toThrow(Exception::class);
});

it('запрещает просмотр списка если canViewAny возвращает false', function () {
    $component = makeComponent(canViewAny: fn () => false);

    expect(fn () => $this->authService->ensureCanViewAny($component))
        ->toThrow(HttpException::class);
});

it('разрешает просмотр элемента если canView возвращает true', function () {
    $item = TestUser::factory()->create();
    $component = makeComponent(canView: fn ($i) => true);

    expect(fn () => $this->authService->ensureCanView($component, $item))->not->toThrow(Exception::class);
});

it('запрещает просмотр элемента если canView возвращает false', function () {
    $item = TestUser::factory()->create();
    $component = makeComponent(canView: fn ($i) => false);

    expect(fn () => $this->authService->ensureCanView($component, $item))
        ->toThrow(HttpException::class);
});

it('разрешает создание если canAdd возвращает true', function () {
    $component = makeComponent(canAdd: fn () => true);

    expect(fn () => $this->authService->ensureCanCreate($component))->not->toThrow(Exception::class);
});

it('запрещает создание если canAdd возвращает false', function () {
    $component = makeComponent(canAdd: fn () => false);

    expect(fn () => $this->authService->ensureCanCreate($component))
        ->toThrow(HttpException::class);
});

it('разрешает редактирование если canEdit возвращает true', function () {
    $item = TestUser::factory()->create();
    $component = makeComponent(canEdit: fn ($i) => true);

    expect(fn () => $this->authService->ensureCanUpdate($component, $item))->not->toThrow(Exception::class);
});

it('запрещает редактирование если canEdit возвращает false', function () {
    $item = TestUser::factory()->create();
    $component = makeComponent(canEdit: fn ($i) => false);

    expect(fn () => $this->authService->ensureCanUpdate($component, $item))
        ->toThrow(HttpException::class);
});

it('разрешает удаление если canDelete возвращает true', function () {
    $item = TestUser::factory()->create();
    $component = makeComponent(canDelete: fn ($i) => true);

    expect(fn () => $this->authService->ensureCanDelete($component, $item))->not->toThrow(Exception::class);
});

it('запрещает удаление если canDelete возвращает false', function () {
    $item = TestUser::factory()->create();
    $component = makeComponent(canDelete: fn ($i) => false);

    expect(fn () => $this->authService->ensureCanDelete($component, $item))
        ->toThrow(HttpException::class);
});

// Хелпер для создания компонента с нужными замыканиями прав
function makeComponent(
    ?Closure $canViewAny = null,
    ?Closure $canView = null,
    ?Closure $canAdd = null,
    ?Closure $canEdit = null,
    ?Closure $canDelete = null,
): Component {
    return new Component(
        model: TestUser::class,
        label: 'Test',
        singleLabel: 'test',
        fields: [],
        canViewAny: $canViewAny ?? fn () => true,
        canView: $canView ?? fn ($i) => true,
        canAdd: $canAdd ?? fn () => true,
        canEdit: $canEdit ?? fn ($i) => true,
        canDelete: $canDelete ?? fn ($i) => true,
    );
}
