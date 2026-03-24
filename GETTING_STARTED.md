# Getting Started

Этот документ показывает минимальный путь от установки пакета до первого рабочего CRUD-списка.

## 1. Установка

```bash
composer require zak/lists
php artisan vendor:publish --tag="lists-migrations"
php artisan migrate
php artisan vendor:publish --tag="lists-config"
```

При необходимости также опубликуйте views/assets/translations:

```bash
php artisan vendor:publish --tag="lists-views"
php artisan vendor:publish --tag="lists-assets"
php artisan vendor:publish --tag="lists-translations"
```

## 2. Проверьте конфиг

Файл `config/lists.php`:

```php
return [
    'path' => app_path('Lists/'),
    'layout' => 'layouts.app',
    'middleware' => ['web', 'auth'],
];
```

Что важно:

- `path` — где пакет ищет конфиги списков
- `layout` — layout для Blade views пакета
- `middleware` — middleware, которыми оборачиваются все маршруты пакета

## 3. Создайте первый список

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

        Boolean::make('Активен', 'active')
            ->filterable()
            ->sortable()
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

## 4. Откройте список

После создания файла список будет доступен по маршруту:

```text
/lists/users
```

Базовые маршруты пакета:

- `GET /lists/{list}` — index
- `GET /lists/{list}/add` — create form
- `POST /lists/{list}/add` — store
- `GET /lists/{list}/{item}` — detail
- `GET /lists/{list}/{item}/edit` — edit form
- `POST|PUT /lists/{list}/{item}/edit` — update
- `DELETE /lists/{list}/{item}` — destroy
- `POST /lists/{list}/option` — save user options
- `POST /lists/{list}/action` — bulk actions
- `GET /lists/{list}/{item}/{page}` — custom page/tab

## 5. Быстрый генератор компонента

Пакет включает Artisan-команду:

```bash
php artisan zak:component users --model=User
```

Она создаёт базовый файл компонента в директории, указанной в `config('lists.path')`.

Для кастомного поля:

```bash
php artisan zak:make-field StatusField
```

Команда создаст класс в `App\Lists\Custom`.

## 6. Что обязательно указать в `Component`

Минимальный рабочий набор:

- `model` — класс Eloquent-модели
- `label` — название списка во множественном числе
- `singleLabel` — сущность в единственном числе
- `fields` — массив полей

Дополнительно можно передать:

- `actions`
- `pages`
- `customViews`
- `customLabels`
- `bulkActions`
- callbacks `OnQuery`, `OnBeforeSave`, `canView`, `canEdit` и т.д.

## 7. Видимость полей

У каждого поля есть четыре ключевых флага видимости:

- `show_in_index`
- `show_in_detail`
- `show_on_add`
- `show_on_update`

Практически удобнее использовать fluent-методы:

```php
Text::make('Пароль', 'password')
    ->hideOnIndex()
    ->hideOnDetail();

ID::make('ID', 'id')
    ->hideOnForms();
```

## 8. Валидация

Пакет сам собирает rules/messages из полей через `FormRequest`-классы.

Пример:

```php
Text::make('Имя', 'name')
    ->required();

Text::make('Email', 'email')
    ->required()
    ->addRule('email', 'lists.fields.validation.email')
    ->addRule('unique:users', 'Email already exists');
```

## 9. Права доступа

Можно передать callbacks в `Component`:

```php
return new Component(
    model: User::class,
    label: 'Пользователи',
    singleLabel: 'пользователь',
    fields: [...],
    canViewAny: fn () => auth()->user()?->can('viewAny', User::class) ?? false,
    canView: fn ($item) => auth()->user()?->can('view', $item) ?? false,
    canAdd: fn () => auth()->user()?->can('create', User::class) ?? false,
    canEdit: fn ($item) => auth()->user()?->can('update', $item) ?? false,
    canDelete: fn ($item) => auth()->user()?->can('delete', $item) ?? false,
);
```

Если callbacks не переданы, `Component` пытается использовать default policy calls автоматически.

## 10. Bulk actions

Пример группового действия:

```php
use Zak\Lists\BulkAction;

bulkActions: [
    BulkAction::make('Деактивировать', 'deactivate', function ($items) {
        foreach ($items as $item) {
            $item->active = false;
            $item->save();
        }
    }),
],
```

## 11. Кастомные страницы

Можно добавить вкладки/подстраницы:

```php
pages: [
    [
        'page' => 'stats',
        'title' => 'Статистика',
        'view' => 'admin.users.stats',
    ],
],
```

Маршрут:

```text
/lists/users/{item}/stats
```

## 12. Что читать дальше

- `FIELDS.md` — полный каталог полей
- `CUSTOMIZATION.md` — callbacks, custom views, pages, custom fields
- `API.md` — reference по основным классам
- `TESTING.md` — как тестировать свои списки
- `TROUBLESHOOTING.md` — частые проблемы

