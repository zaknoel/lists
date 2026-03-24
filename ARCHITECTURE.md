# Архитектура Zak/Lists v2

Zak/Lists v2 — это Laravel CRUD-пакет с конфигурацией списков через PHP-файлы, тонким HTTP-контроллером и вынесенной бизнес-логикой в Action/Service-слой.

> Для подробного design-document уровня refactoring см. `.github/ARCHITECTURE.md`.

## Основные принципы

- Конфигурация списка живёт в `app/Lists/*.php`
- `Component` описывает модель, поля, действия, страницы, права и callbacks
- Контроллер `ListController` только маршрутизирует запросы в Action-классы
- CRUD-операции реализованы через `src/Actions/*`
- Логика работы с полями, запросами, правами и экспортом вынесена в `src/Services/*`
- Валидация идёт через `FormRequest`-классы из `src/Requests/*`
- Метаданные и JSON-структуры оформлены через `src/Resources/*`

## Поток запроса

### HTML / CRUD

1. Пользователь открывает маршрут `/lists/{list}` или `/lists/{list}/{item}`
2. `routes/lists.php` направляет запрос в `ListController`
3. `ListController` делегирует запрос в соответствующий Action:
   - `IndexAction`
   - `CreateAction`
   - `StoreAction`
   - `EditAction`
   - `UpdateAction`
   - `ShowAction`
   - `DestroyAction`
   - `SaveOptionsAction`
   - `BulkActionRunner`
   - `PageAction`
4. Action загружает компонент через `ComponentLoaderContract`
5. Action проверяет права через `AuthorizationContract`
6. При необходимости Action обращается к `QueryService`, `FieldService`, `ExportService`
7. Возвращается Blade view, redirect или экспорт

### Валидация

- `ListStoreRequest`, `ListUpdateRequest`, `ListDestroyRequest`, `ListOptionsRequest`, `ListBulkActionRequest`
- Request резолвит компонент по route parameter `{list}`
- Rules/messages собираются из полей компонента
- В Actions уже приходит валидированный запрос

### Поля

Поле в v2 — это конфигурационный объект, который умеет:

- описывать правила валидации
- хранить параметры видимости
- участвовать в фильтрации
- подготавливать значения для индекса / detail / форм
- запускать field-level callbacks

Ключевые классы:

- `Zak\Lists\Fields\Field`
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

## Слои системы

### 1. Configuration Layer

- `Component`
- `Action`
- `BulkAction`
- `Fields/*`

Это декларативный слой. Здесь описывается, **что** должен делать список.

### 2. HTTP Layer

- `routes/lists.php`
- `Http/Controllers/ListController.php`
- `Requests/*`

Это слой приёма HTTP-запроса, авторизации и валидации.

### 3. Action Layer

- `CreateAction`
- `StoreAction`
- `ShowAction`
- `EditAction`
- `UpdateAction`
- `DestroyAction`
- `IndexAction`
- `SaveOptionsAction`
- `BulkActionRunner`
- `PageAction`

Action-классы управляют сценарием конкретной операции.

### 4. Service Layer

- `AuthorizationService`
- `ComponentLoader`
- `FieldService`
- `QueryService`
- `ExportService`

Services инкапсулируют повторяемую логику и используются из Actions и Requests.

### 5. Presentation / Resource Layer

- Blade views в `resources/views`
- JSON resources в `src/Resources/*`

## Права доступа

Права задаются на уровне `Component`:

- `canViewAny`
- `canView`
- `canAdd`
- `canEdit`
- `canDelete`

Если callbacks не переданы, `Component` пытается использовать Laravel policies через `auth()->user()->can(...)`.

## Пользовательские настройки таблицы

Пакет хранит настройки пользователя в `Models/UserOption`:

- видимые колонки
- активные фильтры
- порядок колонок
- текущая сортировка
- длина страницы

Эти настройки используются в `Component`, `QueryService` и `IndexAction`.

## DataTables и экспорт

`IndexAction` поддерживает три режима:

- обычный HTML index view
- AJAX-данные для DataTables
- Excel export через `maatwebsite/excel`

Экспорт выполняет `ExportService`, который использует `ListImport`.

## Legacy-слой

В пакете всё ещё присутствует `src/ListComponent.php`. Он считается legacy-слоем и нужен только для обратной совместимости и миграции старых интеграций. Для новой разработки используйте v2 API:

- `Component`
- `Actions/*`
- `Services/*`
- `Requests/*`
- `Resources/*`

## Связанные документы

- `README.md` — быстрый вход
- `GETTING_STARTED.md` — первый рабочий список
- `FIELDS.md` — каталог полей и fluent API
- `CUSTOMIZATION.md` — callbacks, custom fields, views, pages
- `API.md` — reference по публичным классам
- `MIGRATION.md` — переход со старых конфигов на v2
- `TESTING.md` — как тестировать пакет и кастомные списки
- `TROUBLESHOOTING.md` — типовые проблемы и решения

