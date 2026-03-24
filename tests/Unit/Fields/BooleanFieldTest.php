<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Zak\Lists\Fields\Boolean;
use Zak\Lists\Tests\Fixtures\Models\TestUser;

beforeEach(function () {
    $this->actor = TestUser::factory()->create();
    Auth::login($this->actor);
});

// ── Construction ──────────────────────────────────────────────────────────────

it('создаётся через make()', function () {
    $field = Boolean::make('Активный', 'active');

    expect($field)->toBeInstanceOf(Boolean::class);
    expect($field->attribute)->toBe('active');
});

it('componentName() возвращает checkbox', function () {
    $field = Boolean::make('Активный', 'active');

    expect($field->componentName())->toBe('checkbox');
});

it('по умолчанию searchable=false', function () {
    $field = Boolean::make('Активный', 'active');

    expect($field->searchable)->toBeFalse();
});

it('содержит правило boolean по умолчанию', function () {
    $field = Boolean::make('Активный', 'active');

    expect(array_keys($field->rules))->toContain('boolean');
});

// ── indexHandler ──────────────────────────────────────────────────────────────

it('indexHandler возвращает badge Да для true', function () {
    $user = TestUser::factory()->create(['active' => true]);
    $field = Boolean::make('Активный', 'active');
    $field->item($user);
    $field->indexHandler();

    expect($field->value)->toContain(__('lists.filter.yes'));
    expect($field->value)->toContain('text-bg-success');
});

it('indexHandler возвращает badge Нет для false', function () {
    $user = TestUser::factory()->create(['active' => false]);
    $field = Boolean::make('Активный', 'active');
    $field->item($user);
    $field->indexHandler();

    expect($field->value)->toContain(__('lists.filter.no'));
    expect($field->value)->toContain('text-bg-danger');
});

it('detailHandler совпадает с indexHandler', function () {
    $user = TestUser::factory()->create(['active' => true]);
    $field = Boolean::make('Активный', 'active');
    $field->item($user);

    $field->indexHandler();
    $indexValue = $field->value;

    $field->value = null;
    $field->detailHandler();

    expect($field->value)->toBe($indexValue);
});

// ── generateFilter ────────────────────────────────────────────────────────────

it('generateFilter фильтрует по значению 1', function () {
    TestUser::query()->delete();
    TestUser::factory()->create(['active' => true, 'email' => 'a1@test.com']);
    TestUser::factory()->create(['active' => false, 'email' => 'a2@test.com']);

    $request = Request::create('/', 'GET', ['active' => '1']);
    app()->instance('request', $request);

    $query = TestUser::query();
    $field = Boolean::make('Активный', 'active');
    $field->generateFilter($query);

    $results = $query->get();
    expect($results)->toHaveCount(1);
    expect((bool) $results->first()->active)->toBeTrue();
});

it('generateFilter фильтрует по значению 0', function () {
    TestUser::query()->delete();
    TestUser::factory()->create(['active' => true, 'email' => 'b1@test.com']);
    TestUser::factory()->create(['active' => false, 'email' => 'b2@test.com']);

    $request = Request::create('/', 'GET', ['active' => '0']);
    app()->instance('request', $request);

    $query = TestUser::query();
    $field = Boolean::make('Активный', 'active');
    $field->generateFilter($query);

    $results = $query->get();
    expect($results)->toHaveCount(1);
    expect((bool) $results->first()->active)->toBeFalse();
});

it('generateFilter устанавлиет filter_value с Да и Нет', function () {
    $request = Request::create('/', 'GET', ['active' => '1⚬0']);
    app()->instance('request', $request);

    $field = Boolean::make('Активный', 'active');
    $field->generateFilter(false);

    expect($field->filter_value)->toHaveKey(1);
    expect($field->filter_value)->toHaveKey(0);
    expect($field->filter_value[1])->toBe(__('lists.filter.yes'));
    expect($field->filter_value[0])->toBe(__('lists.filter.no'));
});

it('generateFilter без параметра в запросе не применяет фильтр', function () {
    TestUser::query()->delete();
    TestUser::factory()->count(3)->create();

    $field = Boolean::make('Активный', 'active');
    $query = TestUser::query();
    $field->generateFilter($query);

    expect($query->count())->toBe(3);
});

it('filteredValue возвращает строку из filter_value', function () {
    $field = Boolean::make('Активный', 'active');
    $field->filter_value = [1 => __('lists.filter.yes')];

    expect($field->filteredValue())->toContain(__('lists.filter.yes'));
});

// ── Validation ────────────────────────────────────────────────────────────────

it('getRules включает boolean правило', function () {
    $field = Boolean::make('Активный', 'active');
    $rules = $field->getRules();

    expect($rules['active'])->toContain('boolean');
});
