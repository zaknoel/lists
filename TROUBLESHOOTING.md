# Troubleshooting

Ниже собраны типовые проблемы при работе с Zak/Lists v2 и практические способы их устранения.

## 1. 404: Component not found

Сообщение обычно выглядит так:

```text
Component not found: users. Create file: /app/Lists/users.php
```

### Причины

- файла `app/Lists/users.php` нет
- имя списка в URL не совпадает с именем файла
- `config('lists.path')` указывает не туда

### Что проверить

- существует ли файл компонента
- возвращает ли файл именно `new Component(...)`
- совпадает ли `/lists/users` с именем `users.php`

## 2. 404: Component is misconfigured

### Причина

Файл списка существует, но возвращает не экземпляр `Zak\Lists\Component`.

### Правильно

```php
return new Component(...);
```

### Неправильно

```php
return ['model' => User::class];
```

## 3. 403 на всех маршрутах списка

### Частые причины

- middleware `auth` не пройдено
- callbacks `canViewAny`, `canView`, `canAdd`, `canEdit`, `canDelete` возвращают `false`
- policy не настроена

### Что проверить

- авторизован ли пользователь
- что возвращают callbacks в `Component`
- есть ли соответствующие policy methods (`viewAny`, `view`, `create/update/delete`)

## 4. Список открывается, но create/edit/delete запрещены

Индекс использует `canViewAny`, а операции — разные callbacks.

Проверьте отдельно:

- `canAdd`
- `canEdit`
- `canDelete`

## 5. Поле не отображается в нужном месте

### Проверьте visibility flags

- `show_in_index`
- `show_in_detail`
- `show_on_add`
- `show_on_update`

### Fluent aliases

```php
->hideOnForms()
->hideOnIndex()
->hideOnDetail()
```

Частая причина: `ID::make(...)->hideOnForms()` скрывает поле и на create, и на edit.

## 6. Поле не сохраняется

### Возможные причины

- поле скрыто на форме и не входит в validation/save набор
- `onSaveForm()` останавливает сохранение
- у поля `multiple()` и вы передаёте не массив
- кастомное поле не реализовало `saveHandler()`

### Что проверить

- входит ли поле в `show_on_add` / `show_on_update`
- корректен ли payload
- не ломает ли логику кастомный callback

## 7. Validation messages выглядят странно или показывают ключ `lists.*`

### Причина

В проекте не загружены translations пакета, либо в custom message передан несуществующий translation key.

### Что делать

- проверьте публикацию translations
- проверьте, что ключ существует в `resources/lang/*/lists.php`
- если сообщение кастомное и не должно переводиться, передайте обычную строку

Пример:

```php
->addRule('email', 'lists.fields.validation.email')
->addRule('unique:users', 'Email already exists')
```

## 8. DataTables AJAX не работает

### Что проверить

- middleware не блокирует AJAX POST
- пакетный маршрут `/lists/{list}` доступен с `Route::any(...)`
- клиент передаёт `X-Requested-With: XMLHttpRequest`

`IndexAction` считает запрос AJAX только если:

- `request()->ajax()`
- header `X-Requested-With=XMLHttpRequest`

## 9. Экспорт Excel не работает

### Проверьте

- установлен ли `maatwebsite/excel`
- передаётся ли `excel=Y`
- нет ли ошибок в field rendering / callbacks

### Команды

```bash
composer show maatwebsite/excel
```

Если экспорт падает, `ExportService::downloadSafe()` логирует SQL и ошибку, если исключение reportable.

## 10. Пользовательские колонки / фильтры / сортировка не сохраняются

### Проверьте

- миграция для `UserOption` применена
- пользователь авторизован
- маршрут `POST /lists/{list}/option` не блокируется middleware
- `value` в `UserOption` сохраняется как JSON

## 11. Связь `Relation` или `BelongToMany` показывает пустое значение

### Возможные причины

- не задан `model()`
- не задан корректный `field()`
- для `Relation` неверен `relationName()`
- отношение отсутствует у модели
- список для `list()` указан неверно

### Пример

```php
Relation::make('Компания', 'company_id')
    ->model(Company::class)
    ->field('name')
    ->relationName('company')
    ->list('companies');
```

## 12. `copy_from` не копирует запись

`CreateAction` копирует только если запись найдена:

```text
/lists/users/add?copy_from=123
```

Если `123` не существует, будет создан пустой новый item.

## 13. Custom page не открывается

### Проверьте

- страница добавлена в `pages`
- `page` в массиве совпадает с segment в URL
- если используется кастомный view, он существует

## 14. Package views падают из-за layout

По умолчанию пакет использует:

```php
'layout' => 'layouts.app'
```

Если такого layout нет, views не смогут нормально рендериться.

### Решения

- создайте `resources/views/layouts/app.blade.php`
- или смените `config('lists.layout')`

## 15. Assets не подключены

Некоторые поля используют package assets/scripts.

### Решение

```bash
php artisan vendor:publish --tag="lists-assets"
```

При необходимости также:

```bash
php artisan vendor:publish --tag="lists-views"
php artisan vendor:publish --tag="lists-translations"
```

## 16. `zak:component` или `zak:make-field` не видны

### Проверьте

- package discovery
- провайдер `Zak\Lists\ListsServiceProvider` зарегистрирован
- автозагрузка актуальна

### Команды

```bash
composer dump-autoload
php artisan package:discover
```

## 17. Тесты падают из-за переводов

Убедитесь, что test environment загружает translations пакета. В тестовой инфраструктуре пакета это уже сделано в `tests/TestCase.php`.

Если вы пишете собственные integration tests в приложении, загрузите translations аналогично.

## 18. Не работает поиск / фильтрация по Text

`Text::generateFilter()` ожидает формат значения:

```text
operator⚬value
```

Примеры:

```text
like⚬Иван
=⚬admin@example.com
not_like⚬test
```

Для range-полей (`ID`, `Number`, `Date`) используется:

```text
f10⚬t100
```

## 19. Когда использовать кастомное поле, а когда callback

Используйте callback, если меняется только локальная логика поля в одном списке.

Используйте custom field class, если:

- логика переиспользуется в нескольких списках
- нужен отдельный display/save/filter lifecycle
- нужен собственный reusable UI contract

## 20. Что делать перед issue report

Перед созданием issue желательно приложить:

- пример компонента
- конкретный маршрут
- stack trace
- payload запроса
- результат `composer show zak/lists`
- версии PHP / Laravel / package
- проходит ли `./vendor/bin/pest --compact`

## Связанные документы

- `GETTING_STARTED.md`
- `FIELDS.md`
- `CUSTOMIZATION.md`
- `TESTING.md`

