<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

// ── 9D: Single-include memoization ───────────────────────────────────────────

it('sorted и base варианты одного компонента создают один экземпляр UserOption', function () {
    $loader = new ComponentLoader;

    // Загружаем base — вызывает firstOrCreate (1-2 запроса в зависимости от наличия записи)
    $base = $loader->resolve('test-users', false);

    // Замеряем только запросы во время sorted resolve — там не должно быть обращений к UserOption
    DB::enableQueryLog();
    $sorted = $loader->resolve('test-users', true);
    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    $userOptionQueries = array_filter($queries, fn ($q) => str_contains($q['query'], '_user_list_options'));

    // Sorted resolve использует clone — ноль дополнительных запросов к user options
    expect(count($userOptionQueries))->toBe(0);
    expect($base)->not->toBe($sorted); // разные экземпляры (clone)
});

it('resolve с applySortOrder=true возвращает другой объект нежели base', function () {
    $loader = new ComponentLoader;

    $base = $loader->resolve('test-users', false);
    $sorted = $loader->resolve('test-users', true);

    expect($base)->not->toBe($sorted);
    expect($base)->toBeInstanceOf(Component::class);
    expect($sorted)->toBeInstanceOf(Component::class);
});

it('повторный resolve с applySortOrder=true возвращает закэшированный sorted экземпляр', function () {
    $loader = new ComponentLoader;

    $first = $loader->resolve('test-users', true);
    $second = $loader->resolve('test-users', true);

    expect($first)->toBe($second);
});
