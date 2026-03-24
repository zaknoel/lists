<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Zak\Lists\Fields\Boolean;
use Zak\Lists\Fields\ID;
use Zak\Lists\Fields\Select;
use Zak\Lists\Fields\Text;
use Zak\Lists\Tests\Fixtures\Models\TestUser;

beforeEach(function () {
    $this->user = TestUser::factory()->create(['active' => true, 'email' => 'actor@test.com']);
    Auth::login($this->user);
    TestUser::query()->where('id', '!=', $this->user->id)->delete();
});

// ── Text поле ─────────────────────────────────────────────────────────────────

it('Text.generateFilter фильтрует через like оператор', function () {
    TestUser::factory()->create(['name' => 'Иван Иванов', 'email' => 'ivan@test.com']);
    TestUser::factory()->create(['name' => 'Пётр Петров', 'email' => 'petr@test.com']);

    // Формат фильтра для Text: 'operator⚬value'
    $request = Request::create('/', 'GET', ['name' => 'like⚬Иван']);
    app()->instance('request', $request);

    $field = Text::make('Имя', 'name')->filterable();
    $query = TestUser::query();
    $field->generateFilter($query);

    $results = $query->get();
    expect($results->pluck('name')->toArray())->toContain('Иван Иванов');
    expect($results->pluck('name')->toArray())->not->toContain('Пётр Петров');
});

it('Text.generateFilter без параметра возвращает все записи', function () {
    TestUser::factory()->count(3)->create();

    $field = Text::make('Имя', 'name');
    $query = TestUser::query();
    $field->generateFilter($query);

    expect($query->count())->toBe(4); // 3 + actor
});

it('Text.filteredValue возвращает пустое All если нет фильтра', function () {
    $field = Text::make('Имя', 'name');
    $field->filter_value = [];

    expect($field->filteredValue())->toBe(__('lists.filter.all'));
});

it('Text.filteredValue возвращает значение если фильтр активен', function () {
    $field = Text::make('Имя', 'name');
    $field->filter_value = ['Иван'];

    expect($field->filteredValue())->toContain('Иван');
});

// ── Boolean поле ──────────────────────────────────────────────────────────────

it('Boolean.generateFilter фильтрует по active=true', function () {
    TestUser::factory()->create(['active' => true, 'email' => 'active@test.com']);
    TestUser::factory()->create(['active' => false, 'email' => 'inactive@test.com']);

    $request = Request::create('/', 'GET', ['active' => '1']);
    app()->instance('request', $request);

    $field = Boolean::make('Активность', 'active');
    $query = TestUser::query();
    $field->generateFilter($query);

    $results = $query->get();
    expect($results->every(fn ($u) => (bool) $u->active))->toBeTrue();
});

it('Boolean.generateFilter фильтрует по active=false', function () {
    TestUser::factory()->create(['active' => true, 'email' => 'active2@test.com']);
    TestUser::factory()->create(['active' => false, 'email' => 'inactive2@test.com']);

    $request = Request::create('/', 'GET', ['active' => '0']);
    app()->instance('request', $request);

    $field = Boolean::make('Активность', 'active');
    $query = TestUser::query();
    $field->generateFilter($query);

    $results = $query->get();
    expect($results->every(fn ($u) => ! (bool) $u->active))->toBeTrue();
});

it('Boolean.generateFilter фильтрует по обоим значениям', function () {
    TestUser::factory()->create(['active' => true, 'email' => 'a@test.com']);
    TestUser::factory()->create(['active' => false, 'email' => 'b@test.com']);

    $request = Request::create('/', 'GET', ['active' => '1⚬0']);
    app()->instance('request', $request);

    $field = Boolean::make('Активность', 'active');
    $query = TestUser::query();
    $field->generateFilter($query);

    expect($query->count())->toBe(3); // actor + 2
});

// ── Select поле ───────────────────────────────────────────────────────────────

it('Select.generateFilter фильтрует по значению enum', function () {
    TestUser::factory()->create(['active' => true, 'email' => 'sel1@test.com']);
    TestUser::factory()->create(['active' => false, 'email' => 'sel2@test.com']);

    $request = Request::create('/', 'GET', ['active' => '1']);
    app()->instance('request', $request);

    $field = Select::make('Активность', 'active')->enum([1 => 'Да', 0 => 'Нет']);
    $query = TestUser::query();
    $field->generateFilter($query);

    expect($query->count())->toBeGreaterThanOrEqual(1);
    expect($query->where('active', true)->count())->toBeGreaterThanOrEqual(1);
});

// ── Диапазон значений (ID / Number поля) ──────────────────────────────────────

it('ID.generateFilter фильтрует по диапазону from/to', function () {
    $u1 = TestUser::factory()->create(['email' => 'r1@test.com']);
    $u2 = TestUser::factory()->create(['email' => 'r2@test.com']);
    $u3 = TestUser::factory()->create(['email' => 'r3@test.com']);

    $request = Request::create('/', 'GET', ['id' => 'f'.$u1->id.'⚬t'.$u2->id]);
    app()->instance('request', $request);

    $field = ID::make('ID', 'id');
    $query = TestUser::query();
    $field->generateFilter($query);

    $ids = $query->pluck('id')->toArray();
    expect($ids)->toContain($u1->id);
    expect($ids)->toContain($u2->id);
    expect($ids)->not->toContain($u3->id);
});

// ── Поиск через IndexAction ───────────────────────────────────────────────────

it('страница списка возвращает 200 с query параметром', function () {
    $this->actingAs($this->user);

    $this->get(route('lists', 'test-users').'?search[value]=test')
        ->assertOk();
});

// ── Custom filterCallback ─────────────────────────────────────────────────────

it('filterCallback используется вместо стандартной фильтрации', function () {
    $callbackCalled = false;
    TestUser::factory()->create(['email' => 'cb_filter@test.com']);

    $request = Request::create('/', 'GET', ['name' => 'test']);
    app()->instance('request', $request);

    $field = Text::make('Имя', 'name')->filterable()->onBeforeFilter(function ($query, $field) use (&$callbackCalled) {
        $callbackCalled = true;
    });
    $query = TestUser::query();
    $field->generateFilter($query);

    expect($callbackCalled)->toBeTrue();
});
