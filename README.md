# Zak/Lists

[![Latest Version](https://img.shields.io/badge/version-2.0.0-blue.svg?style=flat-square)](CHANGELOG.md)
[![Tests](https://img.shields.io/badge/tests-450%20passing-brightgreen.svg?style=flat-square)](TESTING.md)
[![PHPStan Level](https://img.shields.io/badge/PHPStan-level%206-blueviolet.svg?style=flat-square)](phpstan.neon.dist)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4.svg?style=flat-square)](composer.json)

Zak/Lists — Laravel CRUD-пакет для административных списков и data grids.

Пакет даёт:

- декларативные списки через `app/Lists/*.php`
- поля `Text`, `Email`, `Number`, `Boolean`, `Date`, `Relation`, `BelongToMany`, `File`, `Image`, `Location` и др.
- готовые CRUD-маршруты и Blade UI
- DataTables AJAX flow
- фильтрацию, сортировку и пользовательские настройки колонок
- bulk actions (sync и async через очередь)
- Excel export с лимитом строк и async offload через очередь
- Laravel policy-based authorization
- PHPStan level 6 static analysis
- Pest test suite с **450 passing tests / 707 assertions**

## Требования

- PHP 8.2+
- Laravel 11 или 12
- `yajra/laravel-datatables`
- `maatwebsite/excel`

## Установка

```bash
composer require zak/lists
php artisan vendor:publish --tag="lists-migrations"
php artisan migrate
php artisan vendor:publish --tag="lists-config"
```

При необходимости опубликуйте assets, views и переводы:

```bash
php artisan vendor:publish --tag="lists-assets"
php artisan vendor:publish --tag="lists-views"
php artisan vendor:publish --tag="lists-translations"
```

## Конфиг

Файл `config/lists.php`:

```php
<?php

return [
	'path' => app_path('Lists/'),
	'layout' => 'layouts.app',
	'middleware' => ['web', 'auth'],
];
```

## Быстрый старт

Создайте файл `app/Lists/users.php`:

```php
<?php

use App\Models\User;
use Zak\Lists\Action;
use Zak\Lists\Component;
use Zak\Lists\Fields\Boolean;
use Zak\Lists\Fields\ID;
use Zak\Lists\Fields\Text;

return new Component(
	model: User::class,
	label: 'Пользователи',
	singleLabel: 'пользователь',
	fields: [
		ID::make('ID', 'id')
			->hideOnForms()
			->sortable()
			->showOnIndex(),

		Boolean::make('Активность', 'active')
			->sortable()
			->filterable()
			->default(true),

		Text::make('Имя', 'name')
			->required()
			->searchable()
			->sortable()
			->defaultAction(),

		Text::make('Email', 'email')
			->required()
			->addRule('email', 'lists.fields.validation.email')
			->addRule('unique:users', 'Email already exists'),
	],
	actions: [
		Action::make(__('lists.actions.view'))->showAction()->default(),
		Action::make(__('lists.actions.edit'))->editAction(),
		Action::make(__('lists.actions.delete'))->deleteAction(),
	],
);
```

После этого список будет доступен по адресу:

```text
/lists/users
```

## Основные маршруты

- `GET /lists/{list}` — index
- `GET /lists/{list}/add` — create form
- `POST /lists/{list}/add` — store
- `GET /lists/{list}/{item}` — detail
- `GET /lists/{list}/{item}/edit` — edit form
- `POST|PUT /lists/{list}/{item}/edit` — update
- `DELETE /lists/{list}/{item}` — destroy
- `POST /lists/{list}/option` — save options
- `POST /lists/{list}/action` — bulk action
- `GET /lists/{list}/{item}/{page}` — custom page

## Artisan-команды

Создать заготовку компонента:

```bash
php artisan zak:component users --model=User
```

Создать кастомное поле:

```bash
php artisan zak:make-field StatusField
```

## Что читать дальше

- `GETTING_STARTED.md` — пошаговая настройка первого списка
- `FIELDS.md` — все типы полей и fluent API
- `CUSTOMIZATION.md` — callbacks, views, pages, custom fields, bulk actions
- `API.md` — reference по классам и контрактам
- `ARCHITECTURE.md` — обзор архитектуры v2
- `MIGRATION.md` — перенос со старого API на v2
- `TESTING.md` — структура и запуск тестов
- `TROUBLESHOOTING.md` — типовые ошибки и решения

## Тестирование

```bash
./vendor/bin/pest --compact
./vendor/bin/pint --dirty --format agent
./vendor/bin/phpstan analyse
```

## Статус проекта

Текущий статус refactoring-проекта отражён в `PROJECT-STATUS.md`.

Ключевые документы refactoring-фазы:

- `README-REFACTORING.md`
- `.github/ARCHITECTURE.md`
- `.github/MIGRATION_GUIDE.md`
- `plan-zakLists.prompt.md`

