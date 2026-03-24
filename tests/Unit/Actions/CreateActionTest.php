<?php

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Zak\Lists\Actions\CreateAction;
use Zak\Lists\Tests\Fixtures\Models\TestUser;

beforeEach(function () {
    $this->user = TestUser::factory()->create();
    Auth::login($this->user);
    $this->action = app(CreateAction::class);
});

// ── Рендеринг формы ───────────────────────────────────────────────────────────

it('handle() возвращает View для формы создания', function () {
    $request = Request::create(route('lists_add', 'test-users'));

    $result = $this->action->handle($request, 'test-users');

    expect($result)->toBeInstanceOf(View::class);
});

it('view data содержит component, fields, list', function () {
    $request = Request::create(route('lists_add', 'test-users'));

    $view = $this->action->handle($request, 'test-users');
    $data = $view->getData();

    expect($data)->toHaveKey('component');
    expect($data)->toHaveKey('fields');
    expect($data)->toHaveKey('list');
    expect($data['list'])->toBe('test-users');
});

it('view data содержит title', function () {
    $request = Request::create(route('lists_add', 'test-users'));

    $view = $this->action->handle($request, 'test-users');
    $data = $view->getData();

    expect($data)->toHaveKey('title');
    expect($data['title'])->not->toBeEmpty();
});

it('fields содержат только поля show_on_add=true', function () {
    $request = Request::create(route('lists_add', 'test-users'));

    $view = $this->action->handle($request, 'test-users');
    $fields = $view->getData()['fields'];

    // id поле имеет hideOnForms() — не должен быть в fields
    $attributes = array_map(fn ($f) => $f->attribute, $fields);
    expect($attributes)->not->toContain('id');
    expect($attributes)->toContain('name');
    expect($attributes)->toContain('email');
});

it('copy_from создаёт клон существующего элемента', function () {
    $original = TestUser::factory()->create(['name' => 'Оригинал', 'email' => 'orig@test.com']);
    $request = Request::create(route('lists_add', 'test-users').'?copy_from='.$original->id);

    $view = $this->action->handle($request, 'test-users');
    $item = $view->getData()['item'];

    expect($item->name)->toBe('Оригинал');
    expect($item->exists)->toBeFalse();
    expect(isset($item->id))->toBeFalse();
});

it('copy_from с несуществующим ID создаёт пустой элемент', function () {
    $request = Request::create(route('lists_add', 'test-users').'?copy_from=99999');

    $view = $this->action->handle($request, 'test-users');
    $item = $view->getData()['item'];

    expect($item->exists)->toBeFalse();
    expect($item->name)->toBeNull();
});

it('возвращает 403 если нет прав на создание', function () {
    $restricted = new class extends TestUser
    {
        public function can($abilities, $arguments = []): bool
        {
            return false;
        }
    };
    Auth::login($restricted);

    $request = Request::create(route('lists_add', 'test-users'));

    expect(fn () => $this->action->handle($request, 'test-users'))
        ->toThrow(HttpException::class);
});

it('frame=1 данные содержат frame=1', function () {
    $request = Request::create(route('lists_add', 'test-users').'?frame=1');

    $view = $this->action->handle($request, 'test-users');
    $data = $view->getData();

    expect($data['frame'])->toBe(1);
});
