<?php

use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Zak\Lists\Component;
use Zak\Lists\Services\ComponentLoader;
use Zak\Lists\Tests\Fixtures\Models\TestUser;

beforeEach(function () {
    $user = TestUser::factory()->create();
    Auth::login($user);
});

it('загружает компонент из файла', function () {
    $loader = new ComponentLoader;
    $component = $loader->resolve('test-users');

    expect($component)->toBeInstanceOf(Component::class);
    expect($component->getLabel())->toBe('Тестовые пользователи');
    expect($component->getModel())->toBe(TestUser::class);
});

it('кэширует компонент при повторном вызове', function () {
    $loader = new ComponentLoader;

    $first = $loader->resolve('test-users');
    $second = $loader->resolve('test-users');

    expect($first)->toBe($second);
});

it('возвращает 404 если файл компонента не существует', function () {
    $loader = new ComponentLoader;
    $file = config('lists.path').'non-existent-list.php';

    expect(file_exists($file))->toBeFalse("Файл неожиданно существует: {$file}");

    expect(fn () => $loader->resolve('non-existent-list'))
        ->toThrow(NotFoundHttpException::class);
});

it('возвращает 404 если файл не возвращает Component', function () {
    $loader = new ComponentLoader;
    $path = config('lists.path').'broken-component.php';

    // Убеждаемся что файл существует и содержимое правильное
    expect(file_exists($path))->toBeTrue("Файл не найден: {$path}");
    expect(include $path)->not->toBeInstanceOf(Component::class);

    expect(fn () => $loader->resolve('broken-component'))
        ->toThrow(NotFoundHttpException::class);
});

it('компонент содержит правильные поля', function () {
    $loader = new ComponentLoader;
    $component = $loader->resolve('test-users');

    $fields = $component->getFields();

    expect($fields)->toHaveCount(4);
    expect(array_map(fn ($f) => $f->attribute, $fields))->toContain('id', 'active', 'name', 'email');
});

it('компонент содержит правильные actions', function () {
    $loader = new ComponentLoader;
    $component = $loader->resolve('test-users');

    $actions = $component->getActions();

    expect($actions)->toHaveCount(3);
});
