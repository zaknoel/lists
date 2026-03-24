<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Zak\Lists\Actions\IndexAction;
use Zak\Lists\Contracts\ComponentLoaderContract;
use Zak\Lists\Models\UserOption;
use Zak\Lists\Tests\Fixtures\Models\TestUser;

beforeEach(function () {
    $this->user = TestUser::factory()->create();
    Auth::login($this->user);
    $this->action = app(IndexAction::class);
    $this->loader = app(ComponentLoaderContract::class);
});

it('renderIndexView использует ограниченный max_length из сохранённых настроек', function () {
    config()->set('lists.default_length', 25);
    config()->set('lists.max_length', 100);

    $component = $this->loader->resolve('test-users', true);
    $options = $component->options->value;
    $options['length'] = 9999;
    $component->options->value = $options;
    $component->options->save();

    $request = Request::create(route('lists', 'test-users'));
    $view = $this->action->handle($request, 'test-users');

    expect($view->getData()['length'])->toBe(100);
});

it('renderIndexView откатывается к default_length если сохранённая длина невалидна', function () {
    config()->set('lists.default_length', 30);
    config()->set('lists.max_length', 100);

    $component = $this->loader->resolve('test-users', true);
    $options = $component->options->value;
    $options['length'] = 0;
    $component->options->value = $options;
    $component->options->save();

    $request = Request::create(route('lists', 'test-users'));
    $view = $this->action->handle($request, 'test-users');

    expect($view->getData()['length'])->toBe(30);
});

it('updateLengthPreference сохраняет ограниченную длину страницы', function () {
    config()->set('lists.default_length', 25);
    config()->set('lists.max_length', 80);

    $component = $this->loader->resolve('test-users', true);
    $request = Request::create(route('lists', 'test-users'), 'GET', ['length' => 999]);

    $method = new ReflectionMethod(IndexAction::class, 'updateLengthPreference');
    $method->invoke($this->action, $request, $component);

    expect($component->options->fresh()->value['length'])->toBe(80);
});

it('updateLengthPreference не сохраняет опции если длина не изменилась', function () {
    config()->set('lists.default_length', 25);
    config()->set('lists.max_length', 80);

    $component = $this->loader->resolve('test-users', true);

    $options = new class extends UserOption
    {
        public int $saveCalls = 0;

        public function save(array $options = []): bool
        {
            $this->saveCalls++;

            return true;
        }
    };
    $options->value = ['length' => 80];
    $component->options = $options;

    $request = Request::create(route('lists', 'test-users'), 'GET', ['length' => 999]);

    $method = new ReflectionMethod(IndexAction::class, 'updateLengthPreference');
    $method->invoke($this->action, $request, $component);

    expect($component->options->value['length'])->toBe(80);
    expect($component->options->saveCalls)->toBe(0);
});

it('updateSortPreference не сохраняет опции если сортировка не изменилась', function () {
    $component = $this->loader->resolve('test-users', true);

    $options = new class extends UserOption
    {
        public int $saveCalls = 0;

        public function save(array $options = []): bool
        {
            $this->saveCalls++;

            return true;
        }
    };
    $options->value = ['curSort' => ['name', 'asc']];
    $component->options = $options;

    $request = Request::create(route('lists', 'test-users'), 'GET', [
        'order' => [
            ['column' => 0, 'dir' => 'asc'],
        ],
        'columns' => [
            ['name' => 'name'],
        ],
    ]);

    $method = new ReflectionMethod(IndexAction::class, 'updateSortPreference');
    $method->invoke($this->action, $request, $component);

    expect($component->options->value['curSort'])->toBe(['name', 'asc']);
    expect($component->options->saveCalls)->toBe(0);
});
