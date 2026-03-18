<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Zak\Lists\Fields\Casts\StringCast;
use Zak\Lists\Fields\Text;
use Zak\Lists\Tests\Fixtures\Models\TestUser;

beforeEach(function () {
    $this->user = TestUser::factory()->create();
    Auth::login($this->user);
});

// ── Construction ──────────────────────────────────────────────────────────────

it('создаётся через make() с именем', function () {
    $field = Text::make('Имя');

    expect($field)->toBeInstanceOf(Text::class);
    expect($field->name)->toBe('Имя');
    expect($field->attribute)->toBe('имя');
});

it('создаётся с явным атрибутом', function () {
    $field = Text::make('Имя пользователя', 'name');

    expect($field->attribute)->toBe('name');
});

it('type() возвращает text', function () {
    $field = Text::make('Имя', 'name');

    expect($field->type())->toBe('text');
});

it('componentName() возвращает text', function () {
    $field = Text::make('Имя', 'name');

    expect($field->componentName())->toBe('text');
});

// ── Visibility ────────────────────────────────────────────────────────────────

it('по умолчанию отображается везде', function () {
    $field = Text::make('Имя', 'name');

    expect($field->show_in_index)->toBeTrue();
    expect($field->show_in_detail)->toBeTrue();
    expect($field->show_on_add)->toBeTrue();
    expect($field->show_on_update)->toBeTrue();
});

it('hideOnForms скрывает поле на формах создания и редактирования', function () {
    $field = Text::make('ID', 'id')->hideOnForms();

    expect($field->show_on_add)->toBeFalse();
    expect($field->show_on_update)->toBeFalse();
});

it('hideOnIndex скрывает поле в таблице', function () {
    $field = Text::make('Пароль', 'password')->hideOnIndex();

    expect($field->show_in_index)->toBeFalse();
});

// ── Validation ────────────────────────────────────────────────────────────────

it('getRules для обязательного поля содержит required', function () {
    $field = Text::make('Имя', 'name')->required();

    $rules = $field->getRules();

    expect($rules)->toHaveKey('name');
    expect($rules['name'])->toContain('required');
    expect($rules['name'])->not->toContain('nullable');
});

it('getRules для необязательного поля содержит nullable', function () {
    $field = Text::make('Имя', 'name');

    $rules = $field->getRules();

    expect($rules['name'])->toContain('nullable');
});

it('getRules для поля с multiple содержит array', function () {
    $field = Text::make('Теги', 'tags')->multiple();

    $rules = $field->getRules();

    expect($rules['tags'])->toContain('array');
});

it('addRule добавляет кастомные правила', function () {
    $field = Text::make('Email', 'email')
        ->addRule('email', 'Неверный email')
        ->addRule('max:255', 'Слишком длинный');

    $rules = $field->getRules();

    expect($rules['email'])->toContain('email');
    expect($rules['email'])->toContain('max:255');
});

it('getRuleParams возвращает сообщения required', function () {
    $field = Text::make('Имя', 'name')->required();

    $params = $field->getRuleParams();

    expect($params)->toHaveKey('name.required');
});

it('getRuleParams возвращает кастомные сообщения', function () {
    $field = Text::make('Email', 'email')
        ->addRule('email', 'Неверный email');

    $params = $field->getRuleParams();

    expect($params)->toHaveKey('email.email');
    expect($params['email.email'])->toBe('Неверный email');
});

// ── handleFill ────────────────────────────────────────────────────────────────

it('handleFill устанавливает значение из модели', function () {
    $user = TestUser::factory()->create(['name' => 'Тест']);
    $field = Text::make('Имя', 'name');
    $field->item($user);
    $field->handleFill();

    expect($field->value)->toBe('Тест');
});

it('handleFill с multiple разбивает строку по разделителю', function () {
    $user = TestUser::factory()->create(['name' => 'one|two|three']);
    $field = Text::make('Теги', 'name')->multiple();
    $field->item($user);
    $field->handleFill();

    expect($field->value)->toBeArray();
    expect($field->value)->toContain('one', 'two', 'three');
});

// ── saveHandler ───────────────────────────────────────────────────────────────

it('saveHandler сохраняет значение в модель', function () {
    $user = TestUser::factory()->create(['name' => 'Старое']);
    $field = Text::make('Имя', 'name');

    $field->saveHandler($user, ['name' => 'Новое']);

    expect($user->name)->toBe('Новое');
});

// ── Filter ────────────────────────────────────────────────────────────────────

it('generateFilter без запроса инициализирует filter_value', function () {
    $field = Text::make('Имя', 'name');
    $field->generateFilter(false);

    expect($field->filter_value)->toBeArray();
});

it('generateFilter с like-оператором применяет LIKE-фильтр', function () {
    $user1 = TestUser::factory()->create(['name' => 'Иван Иванов']);
    $user2 = TestUser::factory()->create(['name' => 'Пётр Петров']);

    $request = Request::create('/', 'GET', ['name' => 'like⚬Иван']);
    app()->instance('request', $request);

    $query = TestUser::query();
    $field = Text::make('Имя', 'name');
    $field->generateFilter($query);

    $results = $query->get();
    expect($results)->toHaveCount(1);
    expect($results->first()->name)->toBe('Иван Иванов');
});

it('generateFilter с equal-оператором применяет = фильтр', function () {
    $user = TestUser::factory()->create(['name' => 'Точный']);
    TestUser::factory()->create(['name' => 'Другой']);

    $request = Request::create('/', 'GET', ['name' => '=⚬Точный']);
    app()->instance('request', $request);

    $query = TestUser::query();
    $field = Text::make('Имя', 'name');
    $field->generateFilter($query);

    $results = $query->get();
    expect($results)->toHaveCount(1);
});

it('filteredValue возвращает строку с активными фильтрами', function () {
    $field = Text::make('Имя', 'name');
    $field->filter_value = ['operator' => '=', 'value' => 'Тест'];

    expect($field->filteredValue())->toContain('=');
});

// ── Display ───────────────────────────────────────────────────────────────────

it('showIndex возвращает значение поля', function () {
    $user = TestUser::factory()->create(['name' => 'Имя Отображение']);
    $field = Text::make('Имя', 'name');
    $field->item($user);

    $result = $field->showIndex($user, 'test-users');

    expect($result)->toBe('Имя Отображение');
});

it('showDetail возвращает значение поля', function () {
    $user = TestUser::factory()->create(['name' => 'Детально']);
    $field = Text::make('Имя', 'name');
    $field->item($user);

    $result = $field->showDetail();

    expect($result)->toBe('Детально');
});

// ── Rows ──────────────────────────────────────────────────────────────────────

it('rows устанавливает количество строк для textarea', function () {
    $field = Text::make('Описание', 'description')->rows(5);

    expect($field->rows)->toBe(5);
});

// ── Cast ─────────────────────────────────────────────────────────────────────

it('withCast устанавливает cast и getCast возвращает его', function () {
    $cast = new StringCast;
    $field = Text::make('Имя', 'name')->withCast($cast);

    expect($field->getCast())->toBe($cast);
});

it('getCast возвращает null если cast не задан', function () {
    $field = Text::make('Имя', 'name');

    expect($field->getCast())->toBeNull();
});
