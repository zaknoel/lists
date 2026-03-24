# Migration Guide: v1 → v2

Этот документ описывает практический переход со старого API Zak/Lists на v2.

> Для полного refactoring-level документа см. `.github/MIGRATION_GUIDE.md`.

## Что изменилось концептуально

### v1

- большая часть логики жила в `ListComponent.php`
- статические / крупные обработчики смешивали query, validation, display и save logic
- callbacks и rendering были тесно связаны с legacy flow

### v2

- `Component` — декларативная конфигурация
- `Actions/*` — сценарии CRUD / pages / bulk / options
- `Services/*` — переиспользуемая логика
- `Requests/*` — авторизация и валидация
- `Resources/*` — JSON metadata
- `ListController` — thin controller

## Что считать стабильным API в v2

Используйте для новых интеграций:

- `Zak\Lists\Component`
- `Zak\Lists\Action`
- `Zak\Lists\BulkAction`
- `Zak\Lists\Fields\*`
- `Zak\Lists\Actions\*`
- `Zak\Lists\Requests\*`
- `Zak\Lists\Resources\*`
- маршруты из `routes/lists.php`

## Legacy-слой

Legacy-слой удалён из пакета. Класса `src/ListComponent.php` больше нет.
Все интеграции должны использовать только v2 API (`Component`, `Actions`, `Services`, `Requests`, `Resources`).

## Шаг 1. Перенесите конфиг списка на `new Component(...)`

### Старый подход

```php
return Component::init([
    'model' => User::class,
    'label' => 'Пользователи',
]);
```

### Новый подход

```php
return new Component(
    model: User::class,
    label: 'Пользователи',
    singleLabel: 'пользователь',
    fields: [
        ID::make('ID', 'id')->hideOnForms(),
        Text::make('Имя', 'name')->required()->defaultAction(),
        Text::make('Email', 'email')->required(),
    ],
);
```

## Шаг 2. Приведите поля к v2 fluent API

### Было

- поля использовались смешанно с view/save/filter логикой
- валидация и сохранение часто завязывались на старые обработчики

### Стало

- поле — конфигурационный объект
- бизнес-логика исполнения вынесена в сервисы и actions

Рекомендуемый перенос:

- `Text`, `Email`, `Number`, `Boolean`, `Date`, `Password`
- `Relation` для FK
- `BelongToMany` для many-to-many
- `File`, `Image`, `Location` для специальных кейсов

## Шаг 3. Перенесите авторизацию в callbacks или policies

Если раньше права определялись неявно, в v2 лучше явно указать callbacks:

```php
canViewAny: fn () => auth()->user()?->can('viewAny', User::class) ?? false,
canView: fn ($item) => auth()->user()?->can('view', $item) ?? false,
canAdd: fn () => auth()->user()?->can('create', User::class) ?? false,
canEdit: fn ($item) => auth()->user()?->can('update', $item) ?? false,
canDelete: fn ($item) => auth()->user()?->can('delete', $item) ?? false,
```

Если callbacks не заданы, `Component` пробует использовать Laravel policy-подобные вызовы автоматически.

## Шаг 4. Перенесите query-логику в `OnQuery` / `OnIndexQuery` / `OnDetailQuery`

```php
OnIndexQuery: function ($query) {
    return $query->where('company_id', auth()->user()->company_id);
},
```

Подходящие сценарии:

- tenant scoping
- eager loading
- скрытие архивных записей
- сложная сортировка

## Шаг 5. Перенесите save/delete hooks

```php
OnBeforeSave: function ($item) {
    $item->updated_by = auth()->id();

    return $item;
},
OnAfterSave: function ($item) {
    activity()->performedOn($item)->log('saved');

    return $item;
},
```

## Шаг 6. Замените старые ad-hoc bulk сценарии на `BulkAction`

```php
bulkActions: [
    BulkAction::make('Деактивировать', 'deactivate', function ($items) {
        foreach ($items as $item) {
            $item->active = false;
            $item->save();
        }
    }),
],
```

## Шаг 7. Приведите переводы к `__('lists.*')`

В v2 уже используется i18n-слой.

### Было

```php
'Успешно обновлено!'
```

### Стало

```php
__('lists.messages.updated')
```

Для rules/messages в полях допускаются:

- translation keys: `lists.fields.validation.email`
- raw custom messages: `Email already exists`

## Шаг 8. Перенесите кастомные шаблоны во `customViews`

```php
customViews: [
    'form' => 'admin.users.form',
    'detail' => 'admin.users.detail',
    'success' => 'admin.shared.success',
],
```

## Шаг 9. Перенесите кастомные страницы в `pages`

```php
pages: [
    [
        'page' => 'stats',
        'title' => 'Статистика',
        'view' => 'admin.users.stats',
    ],
],
```

## Пример миграции списка

### До

```php
// Условный v1 / legacy стиль
return [
    'model' => User::class,
    'label' => 'Пользователи',
];
```

### После

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
    canViewAny: fn () => auth()->user()?->can('viewAny', User::class) ?? false,
    canView: fn ($item) => auth()->user()?->can('view', $item) ?? false,
    canAdd: fn () => auth()->user()?->can('create', User::class) ?? false,
    canEdit: fn ($item) => auth()->user()?->can('update', $item) ?? false,
    canDelete: fn ($item) => auth()->user()?->can('delete', $item) ?? false,
);
```

## Проверочный чеклист миграции

- [ ] список возвращает экземпляр `Component`
- [ ] все поля описаны через `Fields/*`
- [ ] `singleLabel` задан
- [ ] правила валидации перенесены в fields
- [ ] policies/callbacks настроены
- [ ] маршруты `/lists/...` открываются без 404
- [ ] create/update/delete проходят
- [ ] bulk actions работают
- [ ] пользовательские колонки/фильтры сохраняются
- [ ] переводы используют `__('lists.*')`
- [ ] тесты проходят

## Связанные документы

- `GETTING_STARTED.md`
- `FIELDS.md`
- `CUSTOMIZATION.md`
- `API.md`

