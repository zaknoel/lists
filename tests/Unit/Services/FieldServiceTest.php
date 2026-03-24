<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Unique;
use Zak\Lists\Component;
use Zak\Lists\Fields\Boolean;
use Zak\Lists\Fields\Text;
use Zak\Lists\Services\FieldService;
use Zak\Lists\Tests\Fixtures\Models\TestUser;

beforeEach(function () {
    $user = TestUser::factory()->create();
    Auth::login($user);

    $this->fieldService = new FieldService;

    $this->component = new Component(
        model: TestUser::class,
        label: 'Test',
        singleLabel: 'test',
        fields: [],
        canViewAny: fn () => true,
        canView: fn ($i) => true,
        canAdd: fn () => true,
        canEdit: fn ($i) => true,
        canDelete: fn ($i) => true,
    );
});

it('собирает правила валидации из полей', function () {
    $fields = [
        Text::make('Имя', 'name')->required(),
        Text::make('Email', 'email'),
    ];

    $rules = $this->fieldService->buildValidationRules($fields);

    expect($rules)->toHaveKey('name');
    expect($rules)->toHaveKey('email');
    expect($rules['name'])->toContain('required');
    expect($rules['email'])->toContain('nullable');
});

it('собирает правила с учётом уникальности для существующей записи', function () {
    $item = TestUser::factory()->create();

    $fields = [
        Text::make('Email', 'email')->addRule('unique:test_users', 'Email уже занят'),
    ];

    $rules = $this->fieldService->buildValidationRules($fields, $item);

    expect($rules['email'])->toBeArray();
    // Правило unique должно игнорировать текущий элемент
    $hasUniqueRule = collect($rules['email'])->some(fn ($r) => $r instanceof Unique);
    expect($hasUniqueRule)->toBeTrue();
});

it('собирает сообщения валидации из полей', function () {
    $fields = [
        Text::make('Имя', 'name')->required(),
    ];

    $messages = $this->fieldService->buildValidationMessages($fields);

    expect($messages)->toHaveKey('name.required');
});

it('заполняет поля значениями из модели', function () {
    $item = TestUser::factory()->create(['name' => 'Test User', 'active' => false]);

    $fields = [
        Text::make('Имя', 'name'),
        Boolean::make('Активность', 'active'),
    ];

    $filledFields = $this->fieldService->fillForForm($fields, $item);

    expect($filledFields[0]->item->id)->toBe($item->id);
    expect($filledFields[1]->item->id)->toBe($item->id);
});

it('сохраняет новый элемент через saveFields', function () {
    $fields = [
        Text::make('Имя', 'name')->required(),
        Text::make('Email', 'email')->required(),
    ];

    $request = Request::create('/lists/test-users', 'POST', [
        'name' => 'Новый пользователь',
        'email' => 'new@example.com',
        'active' => true,
    ]);

    $savedItem = $this->fieldService->saveFields(null, $fields, $request, $this->component);

    expect($savedItem->id)->toBeGreaterThan(0);
    expect($savedItem->name)->toBe('Новый пользователь');
    $this->assertDatabaseHas('test_users', ['email' => 'new@example.com']);
});

it('обновляет существующий элемент через saveFields', function () {
    $item = TestUser::factory()->create(['name' => 'Старое имя']);

    $fields = [
        Text::make('Имя', 'name')->required(),
    ];

    $request = Request::create('/lists/test-users/'.$item->id, 'PUT', [
        'name' => 'Новое имя',
    ]);

    $updated = $this->fieldService->saveFields($item, $fields, $request, $this->component);

    expect($updated->id)->toBe($item->id);
    $this->assertDatabaseHas('test_users', ['id' => $item->id, 'name' => 'Новое имя']);
});

it('вызывает onBeforeSave и onAfterSave колбэки компонента', function () {
    $beforeSaveCalled = false;
    $afterSaveCalled = false;

    $component = new Component(
        model: TestUser::class,
        label: 'Test',
        singleLabel: 'test',
        fields: [],
        canViewAny: fn () => true,
        canAdd: fn () => true,
        OnBeforeSave: function ($item) use (&$beforeSaveCalled) {
            $beforeSaveCalled = true;
        },
        OnAfterSave: function ($item) use (&$afterSaveCalled) {
            $afterSaveCalled = true;
        },
    );

    $fields = [
        Text::make('Имя', 'name'),
        Text::make('Email', 'email'),
    ];
    $request = Request::create('/', 'POST', ['name' => 'Test', 'email' => 'cb@example.com']);

    $this->fieldService->saveFields(null, $fields, $request, $component);

    expect($beforeSaveCalled)->toBeTrue();
    expect($afterSaveCalled)->toBeTrue();
});
