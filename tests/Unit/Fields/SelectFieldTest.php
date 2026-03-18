<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Zak\Lists\Fields\Select;
use Zak\Lists\Tests\Fixtures\Models\TestUser;

beforeEach(function () {
    $this->actor = TestUser::factory()->create();
    Auth::login($this->actor);
});

// ── Construction ──────────────────────────────────────────────────────────────

it('создаётся через make()', function () {
    $field = Select::make('Статус', 'status');

    expect($field)->toBeInstanceOf(Select::class);
    expect($field->attribute)->toBe('status');
});

it('componentName() возвращает select', function () {
    $field = Select::make('Статус', 'status');

    expect($field->componentName())->toBe('select');
});

it('enum() устанавливает список значений', function () {
    $field = Select::make('Статус', 'status')->enum(['active' => 'Активный', 'inactive' => 'Неактивный']);

    expect($field->enum)->toBe(['active' => 'Активный', 'inactive' => 'Неактивный']);
});

// ── handleFill ────────────────────────────────────────────────────────────────

it('handleFill устанавливает selected из строкового значения модели', function () {
    $user = new TestUser(['active' => 'active']);
    Auth::login($this->actor);

    $field = Select::make('Статус', 'active')->enum(['active' => 'Активный', 'inactive' => 'Неактивный']);
    $field->item($user);
    $field->handleFill();

    expect($field->selected)->toContain('active');
});

it('handleFill использует default если значение null', function () {
    $user = new TestUser(['active' => null]);

    $field = Select::make('Статус', 'active')
        ->enum(['active' => 'Активный'])
        ->default('active');
    $field->item($user);
    $field->handleFill();

    expect($field->selected)->toContain('active');
});

// ── indexHandler ──────────────────────────────────────────────────────────────

it('indexHandler маппит строковое значение через enum', function () {
    $user = new TestUser(['active' => 'active']);

    $field = Select::make('Статус', 'active')->enum(['active' => 'Активный', 'inactive' => 'Неактивный']);
    $field->item($user);
    $field->indexHandler();

    expect($field->value)->toBe('Активный');
});

it('indexHandler возвращает пустую строку для неизвестного значения', function () {
    $user = new TestUser(['active' => 'unknown']);

    $field = Select::make('Статус', 'active')->enum(['active' => 'Активный']);
    $field->item($user);
    $field->indexHandler();

    expect($field->value)->toBe('');
});

it('indexHandler маппит массив значений через enum', function () {
    $user = new TestUser(['active' => ['active', 'inactive']]);

    $field = Select::make('Статус', 'active')
        ->enum(['active' => 'Активный', 'inactive' => 'Неактивный'])
        ->multiple();
    $field->item($user);
    $field->indexHandler();

    expect($field->value)->toContain('Активный');
    expect($field->value)->toContain('Неактивный');
});

// ── detailHandler ─────────────────────────────────────────────────────────────

it('detailHandler работает аналогично indexHandler', function () {
    $user = new TestUser(['active' => 'inactive']);

    $field = Select::make('Статус', 'active')->enum(['active' => 'Активный', 'inactive' => 'Неактивный']);
    $field->item($user);
    $field->detailHandler();

    expect($field->value)->toBe('Неактивный');
});

// ── generateFilter ────────────────────────────────────────────────────────────

it('generateFilter без запроса не применяет фильтр', function () {
    // Используем только пользователей, созданных в этом тесте
    $query = TestUser::query()->whereIn('id', []);
    TestUser::factory()->count(2)->create();

    $field = Select::make('Статус', 'active')->enum([1 => 'Да', 0 => 'Нет']);
    // Без параметра в запросе фильтр не должен изменять исходный queryi
    $unfilteredQuery = TestUser::query();
    $countBefore = $unfilteredQuery->count();

    $field->generateFilter($unfilteredQuery);

    expect($unfilteredQuery->count())->toBe($countBefore);
});

it('generateFilter фильтрует по выбранному enum-значению', function () {
    // Удаляем все записи и создаём свежие для чистого теста
    TestUser::query()->delete();
    TestUser::factory()->create(['active' => true, 'email' => 'active1@test.com']);
    TestUser::factory()->create(['active' => false, 'email' => 'inactive1@test.com']);

    $request = Request::create('/', 'GET', ['active' => '1']);
    app()->instance('request', $request);

    $field = Select::make('Активный', 'active')->enum([1 => 'Да', 0 => 'Нет']);
    $query = TestUser::query();
    $field->generateFilter($query);

    expect($query->count())->toBe(1);
});

// ── filteredValue ─────────────────────────────────────────────────────────────

it('filteredValue возвращает метку выбранного значения', function () {
    $field = Select::make('Статус', 'active')->enum([1 => 'Да', 0 => 'Нет']);
    $field->filter_value = [1 => 'Да'];

    expect($field->filteredValue())->toContain('Да');
});
