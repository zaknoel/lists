<?php

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Zak\Lists\Actions\ShowAction;
use Zak\Lists\Tests\Fixtures\Models\TestUser;

beforeEach(function () {
    $this->user = TestUser::factory()->create();
    Auth::login($this->user);
    $this->action = app(ShowAction::class);
});

it('handle() возвращает View для детальной страницы', function () {
    $item = TestUser::factory()->create();
    $request = Request::create(route('lists_detail', ['list' => 'test-users', 'item' => $item->id]));

    $result = $this->action->handle($request, 'test-users', $item->id);

    expect($result)->toBeInstanceOf(View::class);
});

it('view data содержит item, component, fields, list', function () {
    $item = TestUser::factory()->create(['name' => 'Детальный']);
    $request = Request::create(route('lists_detail', ['list' => 'test-users', 'item' => $item->id]));

    $view = $this->action->handle($request, 'test-users', $item->id);
    $data = $view->getData();

    expect($data)->toHaveKey('item');
    expect($data)->toHaveKey('component');
    expect($data)->toHaveKey('fields');
    expect($data)->toHaveKey('list');
    expect($data['item']->id)->toBe($item->id);
});

it('возвращает 404 для несуществующего элемента', function () {
    $request = Request::create(route('lists_detail', ['list' => 'test-users', 'item' => 99999]));

    expect(fn () => $this->action->handle($request, 'test-users', 99999))
        ->toThrow(HttpException::class);
});

it('возвращает 403 если нет прав на просмотр', function () {
    $item = TestUser::factory()->create();
    $restricted = new class extends TestUser
    {
        public function can($abilities, $arguments = []): bool
        {
            return false;
        }
    };
    Auth::login($restricted);

    $request = Request::create(route('lists_detail', ['list' => 'test-users', 'item' => $item->id]));

    expect(fn () => $this->action->handle($request, 'test-users', $item->id))
        ->toThrow(HttpException::class);
});

it('fields содержат только show_in_detail поля', function () {
    $item = TestUser::factory()->create();
    $request = Request::create(route('lists_detail', ['list' => 'test-users', 'item' => $item->id]));

    $view = $this->action->handle($request, 'test-users', $item->id);
    $fields = $view->getData()['fields'];

    expect($fields)->not->toBeEmpty();
});

it('view data содержит pages', function () {
    $item = TestUser::factory()->create();
    $request = Request::create(route('lists_detail', ['list' => 'test-users', 'item' => $item->id]));

    $view = $this->action->handle($request, 'test-users', $item->id);
    $data = $view->getData();

    expect($data)->toHaveKey('pages');
});
