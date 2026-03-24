# API Reference

Этот документ описывает публичный API Zak/Lists v2.

## Stable public API

### Core classes

- `Zak\Lists\Component`
- `Zak\Lists\Action`
- `Zak\Lists\BulkAction`

### Fields

- `Zak\Lists\Fields\Field`
- `Zak\Lists\Fields\FieldCollection`
- `Zak\Lists\Fields\Text`
- `Zak\Lists\Fields\Email`
- `Zak\Lists\Fields\Number`
- `Zak\Lists\Fields\Boolean`
- `Zak\Lists\Fields\Date`
- `Zak\Lists\Fields\Password`
- `Zak\Lists\Fields\Select`
- `Zak\Lists\Fields\Relation`
- `Zak\Lists\Fields\BelongToMany`
- `Zak\Lists\Fields\File`
- `Zak\Lists\Fields\Image`
- `Zak\Lists\Fields\Location`
- `Zak\Lists\Fields\CustomField`

### Contracts

- `AuthorizationContract`
- `ComponentLoaderContract`
- `FieldServiceContract`
- `QueryContract`

### Actions

- `IndexAction`
- `CreateAction`
- `StoreAction`
- `ShowAction`
- `EditAction`
- `UpdateAction`
- `DestroyAction`
- `SaveOptionsAction`
- `BulkActionRunner`
- `PageAction`

### Requests

- `BaseListRequest`
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

## Component

```php
new Component(
    model: User::class,
    label: 'Пользователи',
    singleLabel: 'пользователь',
    fields: [...],
    actions: [...],
    pages: [...],
    customScript: '',
    OnQuery: fn ($query) => $query,
    OnIndexQuery: fn ($query) => $query,
    OnDetailQuery: fn ($query) => $query,
    OnEditQuery: fn ($query) => $query,
    OnBeforeSave: fn ($item) => $item,
    OnAfterSave: fn ($item) => $item,
    OnBeforeDelete: fn ($item) => $item,
    OnAfterDelete: fn ($item) => $item,
    canView: fn ($item) => true,
    canViewAny: fn () => true,
    canAdd: fn () => true,
    canEdit: fn ($item) => true,
    canDelete: fn ($item) => true,
    customButtons: '',
    callCustomDetailButtons: null,
    bShowEditButtonOnDetail: true,
    customViews: [],
    customAddPage: null,
    customEditPage: null,
    customDetailPage: null,
    customDeletePage: null,
    customLabels: [],
    bulkActions: [],
)
```

### Полезные методы

- `getModel(): string`
- `getLabel(): string`
- `getSingleLabel(): string`
- `getActions(): array`
- `getPages(): array`
- `getFields(): array`
- `fieldCollection(): FieldCollection`
- `setFields(array $fields): static`
- `getFilteredFields(Closure $callback): array`
- `getQuery(): Builder`
- `getFilteredActions(mixed $item): array`
- `getSortInt(): int`
- `userCanViewAny(): bool`
- `userCanView(mixed $item): bool`
- `userCanAdd(): bool`
- `userCanEdit(mixed $item): bool`
- `userCanDelete(mixed $item): bool`
- `getRoute(string $route, string $list, mixed $context = null): string`
- `scripts(): string`

## Action

```php
Action::make('Просмотр')->showAction()->default();
Action::make('Редактировать')->editAction();
Action::make('Удалить')->deleteAction();
Action::make('Внешняя ссылка')->setLinkAction('/url');
Action::make('JS')->setJsAction('alert(item_id)');
```

### Методы

- `editAction(): static`
- `showAction(): static`
- `deleteAction(): static`
- `setLinkAction(string $link): static`
- `setJsAction(string $code): static`
- `default(): static`
- `blank(): static`
- `show($func): static`
- `isShown(Component $component, Model $item): bool`
- `getLink(Model $item, string $list, string $name = '', string $class = ''): string`

## BulkAction

```php
BulkAction::make('Деактивировать', 'deactivate', function ($items) {
    ...
});
```

### Методы

- `setSuccessMessage(string $text): static`
- `setConfirmText(string $text): static`
- `setIcon(string $icon): static`
- `getSuccessMessage(): string`
- `confirmText(): string`
- `label(): string`

## Requests

### BaseListRequest

- резолвит компонент по route param `{list}`
- кэширует его внутри request

### ListStoreRequest / ListUpdateRequest

- авторизация через `Component::userCanAdd()` / `userCanEdit()`
- rules/messages собираются из полей компонента

### ListDestroyRequest

- только авторизация

### ListOptionsRequest

- валидация `columns`, `filters`, `sort`

### ListBulkActionRequest

- валидация `action`, `items`, `items.*`

## Services

### AuthorizationService

Методы:

- `ensureCanViewAny(Component $component): void`
- `ensureCanView(Component $component, Model $item): void`
- `ensureCanCreate(Component $component): void`
- `ensureCanUpdate(Component $component, Model $item): void`
- `ensureCanDelete(Component $component, Model $item): void`

### ComponentLoader

Методы:

- `resolve(string $list, bool $applySortOrder = false): Component`

Загружает файл компонента из `config('lists.path')`.

### FieldService

Методы:

- `saveFields(?Model $item, array $fields, Request $request, Component $component): Model`
- `fillForForm(array $fields, Model $item): array`
- `buildValidationRules(array $fields, ?Model $item = null): array`
- `buildValidationMessages(array $fields): array`

### QueryService

Методы:

- `buildIndexQuery(Component $component, Request $request): Builder`
- `buildDetailQuery(Component $component): Builder`
- `buildEditQuery(Component $component): Builder`
- `findOrAbort(Component $component, Builder $query, int $id): Model`
- `resolveEagerRelations(Component $component, array $fields): array`

### ExportService

Методы:

- `download(array $rows, array $fields, string $filename): BinaryFileResponse`
- `downloadSafe(array $rows, array $fields, string $filename, string $rawSql = ''): BinaryFileResponse`

## Actions

### IndexAction

Режимы работы:

- HTML list page
- DataTables AJAX response
- Excel export

### CreateAction

- рендерит форму создания
- поддерживает `copy_from`

### StoreAction

- сохраняет новый элемент
- при `frame=1` возвращает success view

### ShowAction

- detail page для элемента

### EditAction

- рендерит форму редактирования

### UpdateAction

- обновляет запись
- при `frame=1` возвращает success view

### DestroyAction

- удаляет запись

### SaveOptionsAction

- сохраняет `columns`, `filters`, `sort` в `UserOption`

### BulkActionRunner

- выполняет зарегистрированный `BulkAction`

### PageAction

- рендерит custom page для элемента

## Resources

### ListItemResource

Возвращает структуру:

```json
{
  "id": 1,
  "attributes": {
    "name": "John"
  },
  "meta": {
    "permissions": {
      "can_view": true,
      "can_edit": true,
      "can_delete": false
    },
    "actions": ["show", "edit"]
  }
}
```

### ListCollectionResource

```json
{
  "data": [],
  "meta": {
    "component": {
      "label": "Users",
      "model": "App\\Models\\User"
    },
    "pagination": {
      "total": 100,
      "per_page": 25,
      "current_page": 1,
      "last_page": 4
    }
  }
}
```

### ListFieldResource

Метаданные поля для frontend/UI.

### ListFilterResource

Метаданные фильтра + options для boolean/select.

### ListActionResource

Метаданные action (`name`, `type`, `action`, `blank`, `default`).

## Helper functions

### `getCurPageParams($add = [], $remove = [])`

Формирует URL с текущими query parameters.

### `isReportable($e)`

Проверяет, должен ли exception логироваться через Laravel exception handler.

## Internal / legacy API

Следующие части не рекомендуются как основа новой интеграции:

- старые конфиги/подходы из legacy слоя
- неявные шаблонные HTML-хаки старого UI

## Связанные документы

- `ARCHITECTURE.md`
- `GETTING_STARTED.md`
- `FIELDS.md`
- `CUSTOMIZATION.md`
- `TESTING.md`

