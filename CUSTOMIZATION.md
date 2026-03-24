# Customization Guide

Этот документ описывает основные точки расширения Zak/Lists v2.

## 1. Component callbacks

`Component` поддерживает несколько callbacks верхнего уровня.

### Query callbacks

```php
OnQuery: function ($query) {
    return $query;
},
OnIndexQuery: function ($query) {
    return $query;
},
OnDetailQuery: function ($query) {
    return $query;
},
OnEditQuery: function ($query) {
    return $query;
},
```

Используйте их для:

- доп. ограничений по tenant / scope
- eager loading
- сложной фильтрации
- кастомного ordering

### Save / delete callbacks

```php
OnBeforeSave: function ($item) {
    return $item;
},
OnAfterSave: function ($item) {
    return $item;
},
OnBeforeDelete: function ($item) {
    return $item;
},
OnAfterDelete: function ($item) {
    return $item;
},
```

Подходящие сценарии:

- вычисление derived fields
- аудит
- синхронизация связанных записей
- отправка доменных событий

## 2. Authorization callbacks

Права можно определить прямо в `Component`:

```php
canViewAny: fn () => auth()->user()?->can('viewAny', User::class) ?? false,
canView: fn ($item) => auth()->user()?->can('view', $item) ?? false,
canAdd: fn () => auth()->user()?->can('create', User::class) ?? false,
canEdit: fn ($item) => auth()->user()?->can('update', $item) ?? false,
canDelete: fn ($item) => auth()->user()?->can('delete', $item) ?? false,
```

Если callbacks не переданы, пакет использует дефолтные policy calls на основе `auth()->user()->can(...)`.

## 3. Custom labels

Можно переопределить стандартные заголовки страниц:

```php
customLabels: [
    'add' => 'Создать пользователя',
    'edit' => 'Редактирование пользователя',
],
```

Далее `CreateAction` и `EditAction` возьмут эти подписи вместо дефолтных.

## 4. Custom views

Можно переопределить Blade views для конкретного списка:

```php
customViews: [
    'form' => 'admin.users.form',
    'detail' => 'admin.users.detail',
    'success' => 'admin.shared.success',
],
```

Поддерживаемые ключи:

- `form`
- `detail`
- `success`

Если ключ не задан, пакет использует встроенные views из `resources/views`.

## 5. Custom pages / tabs

Компонент поддерживает дополнительные страницы для элемента:

```php
pages: [
    [
        'page' => 'stats',
        'title' => 'Статистика',
        'view' => 'admin.users.stats',
    ],
    [
        'page' => 'history',
        'title' => 'История',
        'view' => 'admin.users.history',
    ],
],
```

Маршрут: `/lists/{list}/{item}/{page}`.

## 6. Custom redirect pages

Можно заменить стандартные маршруты на кастомные страницы:

```php
customAddPage: fn () => route('admin.users.custom-create'),
customEditPage: fn ($item) => route('admin.users.custom-edit', $item),
customDetailPage: fn ($item) => route('admin.users.show', $item),
customDeletePage: fn ($item) => route('admin.users.confirm-delete', $item),
```

Это полезно, если вы хотите использовать пакет только как конфигурационный слой, а UI реализовать полностью самостоятельно.

## 7. Custom buttons / views

В `Component` есть дополнительные extension points:

- `customButtons`
- `callCustomDetailButtons`
- `customScript`
- `bShowEditButtonOnDetail`

Они пригодятся для legacy-интеграций и постепенной миграции.

## 8. Field-level callbacks

У каждого поля доступны callbacks:

```php
Text::make('Имя', 'name')
    ->onShowList(function ($field) {
        $field->value = mb_strtoupper((string) $field->value);
    })
    ->onShowDetail(function ($field) {
        $field->value = 'Detail: '.$field->value;
    })
    ->onFillForm(function ($field) {
        // подготовка value для формы
    })
    ->onSaveForm(function ($item, $data) {
        // вернуть false, чтобы остановить стандартное сохранение
        return $data;
    })
    ->onBeforeFilter(function ($query, $field) {
        // полностью кастомная фильтрация
    });
```

## 9. Custom field class

Создайте новый класс:

```bash
php artisan zak:make-field StatusField
```

Команда создаст класс в namespace `App\Lists\Custom`.

Базовый сценарий:

```php
namespace App\Lists\Custom;

use Zak\Lists\Fields\CustomField;

class StatusField extends CustomField
{
    public function indexHandler(): void
    {
        $this->value = match ($this->item->{$this->attribute}) {
            'draft' => 'Черновик',
            'active' => 'Активен',
            default => 'Неизвестно',
        };
    }

    public function detailHandler(): void
    {
        $this->indexHandler();
    }

    public function handleFill(): void
    {
        $this->value = $this->item->{$this->attribute};
    }

    public function saveHandler($item, $data): void
    {
        $item->{$this->attribute} = $data[$this->attribute] ?? null;
    }
}
```

## 10. Custom filter logic

Если стандартный `generateFilter()` не подходит, используйте:

```php
Text::make('Имя', 'name')
    ->onBeforeFilter(function ($query, $field) {
        $search = request('name');

        if ($search) {
            $query->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($search).'%']);
        }
    });
```

## 11. Custom field rendering

Можно подключить кастомный Blade template:

```php
Text::make('Имя', 'name')->view('admin.fields.user-name');
```

Или переопределить package views глобально через `vendor:publish`.

## 12. Relation / BelongToMany customization

### Relation

```php
Relation::make('Компания', 'company_id')
    ->model(App\Models\Company::class)
    ->field('name')
    ->relationName('company')
    ->list('companies')
    ->filter(['active', '=', 1]);
```

### BelongToMany

```php
BelongToMany::make('Теги', 'tags')
    ->model(App\Models\Tag::class)
    ->field('name')
    ->list('tags')
    ->filter(['active', '=', 1]);
```

## 13. Bulk actions

Пример реального группового действия:

```php
use Zak\Lists\BulkAction;

bulkActions: [
    BulkAction::make('Архивировать', 'archive', function ($items, $component, $request) {
        foreach ($items as $item) {
            $item->archived_at = now();
            $item->save();
        }
    })
        ->setConfirmText('Архивировать выбранные элементы?')
        ->setSuccessMessage('Элементы архивированы'),
],
```

## 14. Рекомендации по расширению

- Если меняется только вывод — используйте `onShowList()` / `onShowDetail()`
- Если меняется подготовка формы — используйте `onFillForm()`
- Если меняется логика записи — используйте `onSaveForm()` или верхнеуровневые `OnBeforeSave/OnAfterSave`
- Если меняется маршрут — используйте `custom*Page`
- Если нужна доменная логика — держите её в сервисах приложения, а не внутри анонимных функций длиной в 50 строк

## 15. Связанные документы

- `FIELDS.md`
- `API.md`
- `MIGRATION.md`
- `TROUBLESHOOTING.md`

