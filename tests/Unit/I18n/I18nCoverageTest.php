<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Zak\Lists\Fields\Boolean;
use Zak\Lists\Fields\Date;
use Zak\Lists\Fields\Email;
use Zak\Lists\Fields\Image;
use Zak\Lists\Fields\Number;
use Zak\Lists\Fields\Password;
use Zak\Lists\Fields\Select;
use Zak\Lists\Fields\Text;
use Zak\Lists\Tests\Fixtures\Models\TestUser;

beforeEach(function () {
    $user = TestUser::factory()->create();
    Auth::login($user);
});

// ── Lang file completeness ────────────────────────────────────────────────────

it('lang файл en содержит все required ключи fields.validation', function () {
    expect(__('lists.fields.validation.email'))->not->toBe('lists.fields.validation.email');
    expect(__('lists.fields.validation.boolean'))->not->toBe('lists.fields.validation.boolean');
    expect(__('lists.fields.validation.numeric'))->not->toBe('lists.fields.validation.numeric');
    expect(__('lists.fields.validation.file'))->not->toBe('lists.fields.validation.file');
    expect(__('lists.fields.validation.image'))->not->toBe('lists.fields.validation.image');
    expect(__('lists.fields.validation.date'))->not->toBe('lists.fields.validation.date');
    expect(__('lists.fields.validation.password_min'))->not->toBe('lists.fields.validation.password_min');
    expect(__('lists.fields.validation.required_array'))->not->toBe('lists.fields.validation.required_array');
});

it('lang файл содержит keys fields.file.download и fields.location.map', function () {
    expect(__('lists.fields.file.download'))->not->toBe('lists.fields.file.download');
    expect(__('lists.fields.location.map'))->not->toBe('lists.fields.location.map');
});

it('lang файл содержит filter.all, filter.yes, filter.no', function () {
    expect(__('lists.filter.all'))->not->toBe('lists.filter.all');
    expect(__('lists.filter.yes'))->not->toBe('lists.filter.yes');
    expect(__('lists.filter.no'))->not->toBe('lists.filter.no');
});

it('lang файл содержи errors для component_not_found и component_invalid', function () {
    expect(__('lists.errors.component_not_found', ['list' => 'test', 'file' => '/path']))->not->toContain('lists.errors');
    expect(__('lists.errors.component_invalid', ['list' => 'test']))->not->toContain('lists.errors');
});

// ── Field validation messages ─────────────────────────────────────────────────

it('Email.getRuleParams возвращает переведённое сообщение для email', function () {
    $field = Email::make('Email', 'email');
    $params = $field->getRuleParams();

    expect($params['email.email'])->toBe(__('lists.fields.validation.email'));
    expect($params['email.email'])->not->toStartWith('lists.');
});

it('Number.getRuleParams возвращает переведённое сообщение для numeric', function () {
    $field = Number::make('Количество', 'count');
    $params = $field->getRuleParams();

    expect($params['count.numeric'])->toBe(__('lists.fields.validation.numeric'));
});

it('Date.getRuleParams возвращает переведённое сообщение для date', function () {
    $field = Date::make('Дата', 'date');
    $params = $field->getRuleParams();

    expect($params['date.date'])->toBe(__('lists.fields.validation.date'));
});

it('Boolean.getRuleParams возвращает переведённое сообщение для boolean', function () {
    $field = Boolean::make('Активный', 'active');
    $params = $field->getRuleParams();

    expect($params['active.boolean'])->toBe(__('lists.fields.validation.boolean'));
});

it('Password.getRuleParams возвращает переведённое сообщение для min', function () {
    $field = Password::make('Пароль', 'password');
    $params = $field->getRuleParams();

    expect($params['password.min'])->toBe(__('lists.fields.validation.password_min'));
});

it('Image.getRuleParams возвращает переведённое сообщение для image', function () {
    $field = Image::make('Фото', 'photo');
    $params = $field->getRuleParams();

    expect($params['photo.image'])->toBe(__('lists.fields.validation.image'));
});

it('поле required возвращает переведённое сообщение через validation.required', function () {
    $field = Text::make('Имя', 'name')->required();
    $params = $field->getRuleParams();

    expect($params['name.required'])->toBe(__('lists.validation.required', ['attribute' => 'Имя']));
    expect($params['name.required'])->not->toContain('Поля');
});

it('поле multiple возвращает переведённое сообщение для array', function () {
    $field = Text::make('Теги', 'tags')->multiple();
    $params = $field->getRuleParams();

    expect($params['tags.array'])->toBe(__('lists.fields.validation.required_array'));
    expect($params['tags.array'])->not->toBe('Must be array');
});

// ── Field display strings ─────────────────────────────────────────────────────

it('Boolean.indexHandler использует переводы для true', function () {
    $user = TestUser::factory()->create(['active' => true]);
    $field = Boolean::make('Активный', 'active');
    $field->item($user);
    $field->indexHandler();

    expect($field->value)->toContain(__('lists.filter.yes'));
});

it('Boolean.indexHandler использует переводы для false', function () {
    $user = TestUser::factory()->create(['active' => false]);
    $field = Boolean::make('Активный', 'active');
    $field->item($user);
    $field->indexHandler();

    expect($field->value)->toContain(__('lists.filter.no'));
});

it('Boolean.generateFilter устанавливает переведённые значения', function () {
    $request = Request::create('/', 'GET', ['active' => '1⚬0']);
    app()->instance('request', $request);

    $field = Boolean::make('Активный', 'active');
    $field->generateFilter(false);

    expect($field->filter_value[1])->toBe(__('lists.filter.yes'));
    expect($field->filter_value[0])->toBe(__('lists.filter.no'));
});

it('Text.filteredValue возвращает переведённое All при пустом filter_value', function () {
    $field = Text::make('Имя', 'name');
    $field->filter_value = [];

    expect($field->filteredValue())->toBe(__('lists.filter.all'));
});

it('Select.filteredValue возвращает переведённое All при пустом filter_value', function () {
    $field = Select::make('Статус', 'status')->enum(['a' => 'A']);
    $field->filter_value = [];

    expect($field->filteredValue())->toBe(__('lists.filter.all'));
});

// ── Custom message backward compatibility ──────────────────────────────────────

it('кастомные сообщения валидации без префикса lists. передаются без изменений', function () {
    $field = Text::make('Имя', 'name');
    $field->addRule('min:3', 'Custom message without prefix');
    $params = $field->getRuleParams();

    expect($params['name.min'])->toBe('Custom message without prefix');
});
