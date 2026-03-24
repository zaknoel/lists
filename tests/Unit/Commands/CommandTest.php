<?php

use Illuminate\Support\Facades\Auth;
use Zak\Lists\Tests\Fixtures\Models\TestUser;

beforeEach(function () {
    TestUser::factory()->create();
    Auth::login(TestUser::first());
});

// ── zak:make-component ────────────────────────────────────────────────────────

it('zak:make-component создаёт файл компонента в lists path', function () {
    $listsPath = config('lists.path');
    $targetFile = rtrim($listsPath, '/').'/'.'TestProducts.php';

    // Убедимся что файл не существует перед тестом
    @unlink($targetFile);
    expect(file_exists($targetFile))->toBeFalse();

    $this->artisan('zak:make-component', ['name' => 'TestProducts', '--model' => 'Product'])
        ->assertExitCode(0);

    expect(file_exists($targetFile))->toBeTrue();

    $contents = file_get_contents($targetFile);
    expect($contents)->toContain('<?php');
    expect($contents)->toContain('Product::class');
    expect($contents)->toContain('new Component(');

    // Файл должен быть синтаксически корректным PHP
    $result = shell_exec('php -l '.escapeshellarg($targetFile).' 2>&1');
    expect($result)->toContain('No syntax errors');

    @unlink($targetFile);
});

it('zak:make-component возвращает FAILURE если компонент уже существует', function () {
    $this->artisan('zak:make-component', ['name' => 'test-users'])
        ->assertExitCode(1);
});

it('zak:make-component подставляет правильные значения из стаба', function () {
    $listsPath = config('lists.path');
    $targetFile = rtrim($listsPath, '/').'/'.'TestOrders.php';

    @unlink($targetFile);

    $this->artisan('zak:make-component', ['name' => 'TestOrders', '--model' => 'Order'])
        ->assertExitCode(0);

    $contents = file_get_contents($targetFile);

    // Заменённые плейсхолдеры
    expect($contents)->not->toContain('{{ model }}');
    expect($contents)->not->toContain('{{ label }}');
    expect($contents)->not->toContain('{{ singular }}');
    expect($contents)->toContain('Order');

    @unlink($targetFile);
});

// ── zak:make-field ────────────────────────────────────────────────────────────

it('zak:make-field создаёт класс поля в App\\Lists\\Custom', function () {
    $fieldClass = 'TestStatusField'.uniqid('', true);
    $expectedPath = app_path('Lists/Custom/'.$fieldClass.'.php');

    @unlink($expectedPath);

    $this->artisan('zak:make-field', ['name' => $fieldClass])
        ->assertExitCode(0);

    expect(file_exists($expectedPath))->toBeTrue();

    $contents = file_get_contents($expectedPath);
    expect($contents)->toContain('<?php');
    expect($contents)->toContain($fieldClass);
    expect($contents)->toContain('extends Field');

    @unlink($expectedPath);
});
