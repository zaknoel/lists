# Testing Guide

Zak/Lists использует Pest + Orchestra Testbench.

## Стек тестирования

- `pestphp/pest`
- `pestphp/pest-plugin-laravel`
- `orchestra/testbench`
- `RefreshDatabase`
- SQLite in-memory database

Базовый тестовый класс: `tests/TestCase.php`.

## Текущее покрытие

На момент завершения Step 7 пакет содержит:

- **399 passing tests**
- **622 assertions**
- **1 skipped**

Категории:

- unit tests для Services, Fields, Requests, Resources, Core classes, Actions
- feature tests для CRUD, filtering, authorization, bulk actions

## Быстрый запуск

```bash
composer test
```

Или напрямую:

```bash
./vendor/bin/pest
```

Компактный вывод:

```bash
./vendor/bin/pest --compact
```

Запуск конкретного файла:

```bash
./vendor/bin/pest tests/Unit/ComponentTest.php
./vendor/bin/pest tests/Feature/FilteringTest.php
```

## Полезные composer scripts

Из `composer.json`:

```bash
composer test
composer test-coverage
composer analyse
composer format
```

## Структура тестов

```text
tests/
├── Feature/
│   ├── CrudOperationsTest.php
│   ├── OptionsAndBulkTest.php
│   ├── AuthorizationTest.php
│   ├── BulkActionsFeatureTest.php
│   └── FilteringTest.php
├── Fixtures/
│   ├── Factories/
│   ├── Lists/
│   ├── Models/
│   └── views/
├── Unit/
│   ├── Actions/
│   ├── Fields/
│   ├── I18n/
│   ├── Requests/
│   ├── Resources/
│   └── Services/
└── TestCase.php
```

## Test fixtures

### Test model

Пакет использует тестовую модель:

- `Zak\Lists\Tests\Fixtures\Models\TestUser`

### Factory

- `Zak\Lists\Tests\Fixtures\Factories\TestUserFactory`

### Test list configs

- `tests/Fixtures/Lists/test-users.php`
- `tests/Fixtures/Lists/bulk-test.php`
- `tests/Fixtures/Lists/broken-component.php`

Это позволяет тестировать пакет изолированно от реального приложения.

## Как тестировать свой список

### 1. Создайте fixture list config

```php
<?php

use Zak\Lists\Component;
use Zak\Lists\Fields\ID;
use Zak\Lists\Fields\Text;
use Zak\Lists\Tests\Fixtures\Models\TestUser;

return new Component(
    model: TestUser::class,
    label: 'Тестовые пользователи',
    singleLabel: 'пользователь',
    fields: [
        ID::make('ID', 'id')->hideOnForms(),
        Text::make('Имя', 'name')->required(),
    ],
);
```

### 2. Напишите feature test

```php
it('создаёт нового пользователя', function () {
    $actor = TestUser::factory()->create();
    $this->actingAs($actor);

    $this->post(route('lists_store', 'test-users'), [
        'name' => 'Новый пользователь',
        'email' => 'new@example.com',
    ])
        ->assertRedirect()
        ->assertSessionHas('js_success');
});
```

### 3. Добавьте проверки базы

```php
$this->assertDatabaseHas('test_users', [
    'email' => 'new@example.com',
]);
```

## Что уже покрыто

### Core classes

- `Component`
- `Action`
- `BulkAction`

### Services

- `AuthorizationService`
- `ComponentLoader`
- `FieldService`
- `QueryService`

### Requests

- `ListStoreRequest`
- `ListUpdateRequest`
- `ListDestroyRequest`
- `ListOptionsRequest`
- `ListBulkActionRequest`

### Resources

- `ListItemResource`
- `ListCollectionResource`
- `ListFieldResource`
- `ListFilterResource`
- `ListActionResource`

### Actions

- `CreateAction`
- `StoreAction`
- `ShowAction`
- `EditAction`
- `UpdateAction`
- `DestroyAction`
- `SaveOptionsAction`
- `BulkActionRunner`

### Feature flows

- CRUD
- filtering
- authorization
- bulk actions
- user options
- i18n coverage

## Рекомендации по новым тестам

### Unit tests

Пишите unit-тесты, если проверяете:

- изолированную логику поля
- callback-поведение
- message/rules generation
- resource serialization
- helper functions

### Feature tests

Пишите feature-тесты, если проверяете:

- маршруты `/lists/*`
- redirects / session flashes
- database side effects
- authorization flow
- bulk action execution
- end-to-end CRUD

## Частые приёмы

### Авторизация

```php
$actor = TestUser::factory()->create();
$this->actingAs($actor);
```

### Пользователь без прав

```php
$restricted = new class extends TestUser {
    public function can($abilities, $arguments = []): bool
    {
        return false;
    }
};

Auth::login($restricted);
```

### Подмена request для field filter tests

```php
$request = Request::create('/', 'GET', ['name' => 'like⚬Иван']);
app()->instance('request', $request);
```

### Тестирование validation exception

```php
expect(fn () => $service->handle(...))
    ->toThrow(\Illuminate\Validation\ValidationException::class);
```

## Тестирование package views

В `tests/TestCase.php` подключён тестовый layout из `tests/Fixtures/views/layouts/app.blade.php`.

Это позволяет безопасно рендерить package views в feature/unit tests.

## Проверка переводов

Тестовая среда подгружает package translations, поэтому можно использовать:

```php
__('lists.messages.created')
__('lists.filter.yes')
```

## Качество кода перед merge

Рекомендуемый набор команд:

```bash
./vendor/bin/pest --compact
./vendor/bin/pint --dirty --format agent
./vendor/bin/phpstan analyse
```

## Связанные документы

- `README.md`
- `GETTING_STARTED.md`
- `API.md`
- `TROUBLESHOOTING.md`

