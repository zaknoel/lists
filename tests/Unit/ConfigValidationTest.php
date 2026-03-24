<?php

use Illuminate\Support\Facades\Auth;
use Zak\Lists\ListImport;
use Zak\Lists\ListsServiceProvider;
use Zak\Lists\Tests\Fixtures\Models\TestUser;

beforeEach(function () {
    TestUser::factory()->create();
    Auth::login(TestUser::first());
});

// ── Корректная конфигурация ───────────────────────────────────────────────────

it('не бросает исключение при корректном конфиге', function () {
    config([
        'lists.path' => __DIR__.'/../../Fixtures/Lists/',
        'lists.default_length' => 25,
        'lists.max_export_rows' => 50000,
    ]);

    // Вызываем метод через рефлексию, поскольку он private
    $provider = new ListsServiceProvider(app());
    $reflection = new ReflectionClass($provider);
    $method = $reflection->getMethod('validateConfig');

    expect(fn () => $method->invoke($provider))->not->toThrow(RuntimeException::class);
});

it('бросает RuntimeException если lists.path пустой', function () {
    config(['lists.path' => '']);

    $provider = new ListsServiceProvider(app());
    $reflection = new ReflectionClass($provider);
    $method = $reflection->getMethod('validateConfig');

    $method->invoke($provider);
})->throws(RuntimeException::class, 'lists.path');

it('бросает RuntimeException если default_length не является положительным целым', function () {
    config([
        'lists.path' => __DIR__.'/../../Fixtures/Lists/',
        'lists.default_length' => 0,
    ]);

    $provider = new ListsServiceProvider(app());
    $reflection = new ReflectionClass($provider);
    $method = $reflection->getMethod('validateConfig');

    $method->invoke($provider);
})->throws(RuntimeException::class, 'default_length');

it('бросает RuntimeException если max_export_rows отрицательный', function () {
    config([
        'lists.path' => __DIR__.'/../../Fixtures/Lists/',
        'lists.default_length' => 25,
        'lists.max_export_rows' => -1,
    ]);

    $provider = new ListsServiceProvider(app());
    $reflection = new ReflectionClass($provider);
    $method = $reflection->getMethod('validateConfig');

    $method->invoke($provider);
})->throws(RuntimeException::class, 'max_export_rows');

it('не бросает исключение при max_export_rows = 0 (лимит отключён)', function () {
    config([
        'lists.path' => __DIR__.'/../../Fixtures/Lists/',
        'lists.default_length' => 25,
        'lists.max_export_rows' => 0,
    ]);

    $provider = new ListsServiceProvider(app());
    $reflection = new ReflectionClass($provider);
    $method = $reflection->getMethod('validateConfig');

    expect(fn () => $method->invoke($provider))->not->toThrow(RuntimeException::class);
});

// ── import_class validation ───────────────────────────────────────────────────

it('бросает RuntimeException если import_class не существует', function () {
    config([
        'lists.path' => __DIR__.'/../../Fixtures/Lists/',
        'lists.default_length' => 25,
        'lists.max_export_rows' => 0,
        'lists.import_class' => 'App\\NonExistent\\ImportClass',
    ]);

    $provider = new ListsServiceProvider(app());
    $reflection = new ReflectionClass($provider);
    $method = $reflection->getMethod('validateConfig');

    $method->invoke($provider);
})->throws(RuntimeException::class, 'import_class');

it('не бросает исключение если import_class — существующий класс', function () {
    config([
        'lists.path' => __DIR__.'/../../Fixtures/Lists/',
        'lists.default_length' => 25,
        'lists.max_export_rows' => 0,
        'lists.import_class' => ListImport::class,
    ]);

    $provider = new ListsServiceProvider(app());
    $reflection = new ReflectionClass($provider);
    $method = $reflection->getMethod('validateConfig');

    expect(fn () => $method->invoke($provider))->not->toThrow(RuntimeException::class);
});

it('не бросает исключение если import_class пустой (используется дефолт)', function () {
    config([
        'lists.path' => __DIR__.'/../../Fixtures/Lists/',
        'lists.default_length' => 25,
        'lists.max_export_rows' => 0,
        'lists.import_class' => '',
    ]);

    $provider = new ListsServiceProvider(app());
    $reflection = new ReflectionClass($provider);
    $method = $reflection->getMethod('validateConfig');

    expect(fn () => $method->invoke($provider))->not->toThrow(RuntimeException::class);
});
