<?php

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Zak\Lists\Component;
use Zak\Lists\Fields\Text;
use Zak\Lists\Services\QueryService;
use Zak\Lists\Tests\Fixtures\Models\TestUser;

beforeEach(function () {
    $this->user = TestUser::factory()->create();
    Auth::login($this->user);

    $this->queryService = new QueryService;

    $this->component = new Component(
        model: TestUser::class,
        label: 'Test',
        singleLabel: 'test',
        fields: [],
        canViewAny: fn () => true,
        canView: fn ($i) => true,
        canAdd: fn () => true,
        canEdit: fn ($i) => true,
        canDelete: fn ($i) => true,
    );
});

it('строит запрос для индексной страницы', function () {
    $request = Request::create('/lists/test-users');
    $query = $this->queryService->buildIndexQuery($this->component, $request);

    expect($query)->toBeInstanceOf(Builder::class);
    expect($query->getModel())->toBeInstanceOf(TestUser::class);
});

it('применяет OnIndexQuery колбэк компонента', function () {
    $callbackCalled = false;

    $component = new Component(
        model: TestUser::class,
        label: 'Test',
        singleLabel: 'test',
        fields: [],
        canViewAny: fn () => true,
        OnIndexQuery: function ($query) use (&$callbackCalled) {
            $callbackCalled = true;
        },
    );

    $request = Request::create('/');
    $this->queryService->buildIndexQuery($component, $request);

    expect($callbackCalled)->toBeTrue();
});

it('находит элемент по ID', function () {
    $item = TestUser::factory()->create();

    $query = $this->queryService->buildEditQuery($this->component);
    $found = $this->queryService->findOrAbort($this->component, $query, $item->id);

    expect($found->id)->toBe($item->id);
});

it('возвращает 404 если элемент не найден', function () {
    $query = $this->queryService->buildEditQuery($this->component);
    $this->queryService->findOrAbort($this->component, $query, 99999);
})->throws(NotFoundHttpException::class);

it('определяет eager-load связи для индексной страницы', function () {
    $fields = [
        Text::make('Имя', 'name')->showOnIndex(),
    ];

    $relations = $this->queryService->resolveEagerRelations($this->component, $fields);

    expect($relations)->toBeArray();
});
