<?php

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Zak\Lists\Actions\EditAction;
use Zak\Lists\Tests\Fixtures\Models\TestUser;

beforeEach(function () {
    $this->user = TestUser::factory()->create();
    Auth::login($this->user);
    $this->action = app(EditAction::class);
});

it('handle() возвращает View формы редактирования', function () {
    $item = TestUser::factory()->create();
    $request = Request::create(route('lists_edit', ['list' => 'test-users', 'item' => $item->id]));

    $result = $this->action->handle($request, 'test-users', $item->id);

    expect($result)->toBeInstanceOf(View::class);
});

it('view data содержит item с правильным id', function () {
    $item = TestUser::factory()->create(['name' => 'Для редакции']);
    $request = Request::create(route('lists_edit', ['list' => 'test-users', 'item' => $item->id]));

    $view = $this->action->handle($request, 'test-users', $item->id);
    $data = $view->getData();

    expect($data['item']->id)->toBe($item->id);
});

it('fields содержат только show_on_update поля', function () {
    $item = TestUser::factory()->create();
    $request = Request::create(route('lists_edit', ['list' => 'test-users', 'item' => $item->id]));

    $view = $this->action->handle($request, 'test-users', $item->id);
    $fields = $view->getData()['fields'];

    // id поле скрыто на формах
    $attributes = array_map(fn ($f) => $f->attribute, $fields);
    expect($attributes)->not->toContain('id');
});

it('возвращает 404 для несуществующего элемента', function () {
    $request = Request::create(route('lists_edit', ['list' => 'test-users', 'item' => 99999]));

    expect(fn () => $this->action->handle($request, 'test-users', 99999))
        ->toThrow(HttpException::class);
});

it('возвращает 403 если нет прав на редактирование', function () {
    $item = TestUser::factory()->create();
    $restricted = new class extends TestUser
    {
        public function can($abilities, $arguments = []): bool
        {
            return false;
        }
    };
    Auth::login($restricted);

    $request = Request::create(route('lists_edit', ['list' => 'test-users', 'item' => $item->id]));

    expect(fn () => $this->action->handle($request, 'test-users', $item->id))
        ->toThrow(HttpException::class);
});

it('view data содержит title', function () {
    $item = TestUser::factory()->create();
    $request = Request::create(route('lists_edit', ['list' => 'test-users', 'item' => $item->id]));

    $view = $this->action->handle($request, 'test-users', $item->id);

    expect($view->getData()['title'])->not->toBeEmpty();
});

it('frame=1 передаётся во view data', function () {
    $item = TestUser::factory()->create();
    $request = Request::create(route('lists_edit', ['list' => 'test-users', 'item' => $item->id]).'?frame=1');

    $view = $this->action->handle($request, 'test-users', $item->id);

    expect($view->getData()['frame'])->toBe(1);
});
