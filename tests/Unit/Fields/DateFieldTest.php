<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Zak\Lists\Fields\Date;
use Zak\Lists\Tests\Fixtures\Models\TestUser;

beforeEach(function () {
    $user = TestUser::factory()->create();
    Auth::login($user);
});

// ── Construction ──────────────────────────────────────────────────────────────

it('создаётся через make()', function () {
    $field = Date::make('Дата создания', 'created_at');

    expect($field)->toBeInstanceOf(Date::class);
    expect($field->attribute)->toBe('created_at');
});

it('type() возвращает date по умолчанию', function () {
    $field = Date::make('Дата', 'date');

    expect($field->type())->toBe('date');
});

it('withTime() меняет type на datetime-local', function () {
    $field = Date::make('Дата и время', 'datetime')->withTime();

    expect($field->time)->toBeTrue();
    expect($field->type())->toBe('datetime-local');
});

it('содержит правило date по умолчанию', function () {
    $field = Date::make('Дата', 'date');

    expect(array_keys($field->rules))->toContain('date');
});

// ── saveHandler ───────────────────────────────────────────────────────────────

it('saveHandler конвертирует строку даты в Carbon', function () {
    $user = TestUser::factory()->create();
    $field = Date::make('Дата', 'created_at');

    $field->saveHandler($user, ['created_at' => '2025-01-15']);

    expect($user->created_at)->toBeInstanceOf(Carbon::class);
    expect($user->created_at->format('Y-m-d'))->toBe('2025-01-15');
});

it('saveHandler не изменяет значение при пустом вводе', function () {
    $user = TestUser::factory()->create();
    $originalDate = $user->created_at;

    $field = Date::make('Дата', 'created_at');
    $field->saveHandler($user, ['created_at' => '']);

    expect($user->created_at)->toEqual($originalDate);
});

// ── handleFill ────────────────────────────────────────────────────────────────

it('handleFill форматирует Carbon как Y-m-d', function () {
    $user = TestUser::factory()->create();
    $date = Carbon::parse('2025-06-15');
    $user->created_at = $date;

    $field = Date::make('Дата', 'created_at');
    $field->item($user);
    $field->handleFill();

    expect($field->value)->toBe('2025-06-15');
});

it('handleFill с withTime форматирует как Y-m-d H:i', function () {
    $user = TestUser::factory()->create();
    $user->created_at = Carbon::parse('2025-06-15 14:30:00');

    $field = Date::make('Дата', 'created_at')->withTime();
    $field->item($user);
    $field->handleFill();

    expect($field->value)->toBe('2025-06-15 14:30');
});

// ── indexHandler ──────────────────────────────────────────────────────────────

it('indexHandler форматирует Carbon в d.m.Y для отображения', function () {
    $user = TestUser::factory()->create();
    $user->created_at = Carbon::parse('2025-06-15');

    $field = Date::make('Дата', 'created_at');
    $field->item($user);
    $field->indexHandler();

    expect($field->value)->toContain('15.06.2025');
});

it('indexHandler возвращает пустую строку для null даты', function () {
    $user = new TestUser(['created_at' => null]);

    $field = Date::make('Дата', 'created_at');
    $field->item($user);
    $field->indexHandler();

    expect($field->value)->toBe('');
});

// ── generateFilter ────────────────────────────────────────────────────────────

it('generateFilter применяет фильтр по диапазону дат', function () {
    // Создаём пользователей с явно заданными датами через forceCreate/query
    TestUser::query()->delete();
    $user1 = TestUser::factory()->create(['email' => 'u1@d.com']);
    $user2 = TestUser::factory()->create(['email' => 'u2@d.com']);

    // Устанавливаем created_at вручную через DB
    DB::table('test_users')
        ->where('id', $user1->id)
        ->update(['created_at' => '2025-01-01 00:00:00']);
    DB::table('test_users')
        ->where('id', $user2->id)
        ->update(['created_at' => '2025-12-31 00:00:00']);

    $request = Request::create('/', 'GET', ['created_at' => 'f2025-06-01⚬t2026-01-01']);
    app()->instance('request', $request);

    $query = TestUser::query();
    $field = Date::make('Дата', 'created_at');
    $field->generateFilter($query);

    $results = $query->get();
    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($user2->id);
});
