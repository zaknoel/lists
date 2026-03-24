<?php

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Zak\Lists\Component;
use Zak\Lists\Fields\Text;
use Zak\Lists\Resources\ListCollectionResource;
use Zak\Lists\Tests\Fixtures\Models\TestUser;

beforeEach(function () {
    $this->user = TestUser::factory()->create();
    Auth::login($this->user);

    $this->component = new Component(
        model: TestUser::class,
        label: 'Пользователи',
        singleLabel: 'пользователь',
        fields: [
            Text::make('Имя', 'name'),
            Text::make('Email', 'email'),
        ],
        canView: fn ($i) => true,
        canEdit: fn ($i) => true,
        canDelete: fn ($i) => true,
        canViewAny: fn () => true,
        canAdd: fn () => true,
    );
});

// ── Коллекция ─────────────────────────────────────────────────────────────────

it('сериализует коллекцию элементов в data', function () {
    TestUser::factory()->count(3)->create();
    $items = TestUser::query()->get();
    $request = Request::create('/');

    $result = (new ListCollectionResource($items, $this->component))->toArray($request);

    expect($result)->toHaveKey('data');
    expect($result['data'])->toHaveCount(4); // 1 из beforeEach + 3 созданных
});

it('каждый элемент содержит id и attributes', function () {
    $items = TestUser::query()->get();
    $request = Request::create('/');

    $result = (new ListCollectionResource($items, $this->component))->toArray($request);

    expect($result['data'][0])->toHaveKey('id');
    expect($result['data'][0])->toHaveKey('attributes');
    expect($result['data'][0])->toHaveKey('meta');
});

it('включает метаданные компонента', function () {
    $items = TestUser::query()->get();
    $request = Request::create('/');

    $result = (new ListCollectionResource($items, $this->component))->toArray($request);

    expect($result['meta']['component']['label'])->toBe('Пользователи');
    expect($result['meta']['component']['model'])->toBe(TestUser::class);
});

// ── Пагинация ─────────────────────────────────────────────────────────────────

it('включает мета-пагинацию для LengthAwarePaginator', function () {
    TestUser::factory()->count(5)->create();
    $paginator = new LengthAwarePaginator(
        TestUser::query()->take(3)->get(),
        6,
        3,
        1
    );
    $request = Request::create('/');

    $result = (new ListCollectionResource($paginator, $this->component))->toArray($request);

    expect($result['meta'])->toHaveKey('pagination');
    expect($result['meta']['pagination']['total'])->toBe(6);
    expect($result['meta']['pagination']['per_page'])->toBe(3);
    expect($result['meta']['pagination']['current_page'])->toBe(1);
    expect($result['meta']['pagination']['last_page'])->toBe(2);
});

it('не включает пагинацию для обычной коллекции', function () {
    $items = TestUser::query()->get();
    $request = Request::create('/');

    $result = (new ListCollectionResource($items, $this->component))->toArray($request);

    expect($result['meta'])->not->toHaveKey('pagination');
});

it('использует переданные поля вместо полей компонента', function () {
    TestUser::factory()->create(['name' => 'Петр', 'email' => 'petr@test.com']);
    $items = TestUser::query()->get();
    $nameField = Text::make('Имя', 'name');
    $request = Request::create('/');

    $result = (new ListCollectionResource($items, $this->component, [$nameField]))->toArray($request);

    expect($result['data'][0]['attributes'])->toHaveKey('name');
    expect($result['data'][0]['attributes'])->not->toHaveKey('email');
});

it('возвращает пустой data для пустой коллекции', function () {
    $items = new Collection([]);
    $request = Request::create('/');

    $result = (new ListCollectionResource($items, $this->component))->toArray($request);

    expect($result['data'])->toBeEmpty();
});
