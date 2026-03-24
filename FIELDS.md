# Fields Reference

Этот документ описывает field API пакета Zak/Lists v2.

## Базовая модель поля

Все поля наследуются от `Zak\Lists\Fields\Field`.

Поле отвечает за:

- атрибут модели (`attribute`)
- label (`name`)
- валидацию
- видимость в index/detail/create/update
- фильтрацию
- подготовку значений для формы и отображения
- field-level callbacks

Создание любого поля начинается с `make()`:

```php
Text::make('Имя', 'name');
Email::make('Email', 'email');
```

Если `attribute` не передан, он генерируется из имени.

## Общие fluent-методы

Эти методы доступны практически всем полям через `FieldProperty` и `FieldEvents`.

### Видимость

```php
->showOnIndex()
->hideOnIndex()
->showOnDetail()
->hideOnDetail()
->showOnAdd()
->hideOnAdd()
->showOnUpdate()
->hideOnUpdate()
->showOnForms()
->hideOnForms()
```

### Поведение

```php
->required()
->sortable()
->filterable()
->searchable()
->multiple()
->virtual()
->default($value)
->defaultAction()
->width(6)
->view('custom.view')
```

### Validation rules

```php
->addRule('email', 'lists.fields.validation.email')
->addRule('unique:users', 'Email already exists')
```

### Callbacks

```php
->onShowList(fn ($field) => ...)
->onShowDetail(fn ($field) => ...)
->onFillForm(fn ($field) => ...)
->onSaveForm(fn ($item, $data) => ...)
->onBeforeFilter(fn ($query, $field) => ...)
->showDetailAsIndex()
```

## Text

Класс: `Zak\Lists\Fields\Text`

Базовое текстовое поле. Поддерживает поиск, фильтрацию, default values, rules и callbacks.

```php
Text::make('Имя', 'name')
    ->required()
    ->searchable()
    ->sortable();
```

Дополнительно:

```php
->rows(3)
->setType('textarea')
```

## Email

Класс: `Zak\Lists\Fields\Email`

Наследует `Text`, задаёт `type=email` и email validation.

```php
Email::make('Email', 'email')
    ->required();
```

## Number

Класс: `Zak\Lists\Fields\Number`

Наследует `Text`, добавляет numeric validation и используется для числовых фильтров и диапазонов.

```php
Number::make('Сортировка', 'sort')
    ->sortable()
    ->default(500);
```

## ID

Класс: `Zak\Lists\Fields\ID`

Часто используется для `id`:

```php
ID::make('ID', 'id')
    ->hideOnForms()
    ->sortable()
    ->showOnIndex();
```

Поддерживает range filtering через формат `f10⚬t100`.

## Boolean

Класс: `Zak\Lists\Fields\Boolean`

Отображает значения как badge `Да/Нет`, поддерживает boolean validation и boolean filters.

```php
Boolean::make('Активность', 'active')
    ->sortable()
    ->filterable()
    ->default(true);
```

Особенности:

- `componentName()` возвращает `checkbox`
- filter options используют переводы `lists.filter.yes/no`

## Date

Класс: `Zak\Lists\Fields\Date`

Поддерживает `date` validation и диапазонные фильтры.

```php
Date::make('Дата', 'date')
    ->required()
    ->filterable()
    ->sortable();
```

Дополнительно:

```php
->withTime()
```

`withTime()` переключает `type()` в `datetime-local`.

## Password

Класс: `Zak\Lists\Fields\Password`

Поле для пароля:

- не отображается в index/detail
- не searchable/filterable
- сохраняет значение через `Hash::make()`

```php
Password::make('Пароль', 'password')
    ->required();
```

## Select

Класс: `Zak\Lists\Fields\Select`

Поле с перечислением значений.

```php
Select::make('Статус', 'status')
    ->enum([
        'draft' => 'Черновик',
        'active' => 'Активен',
    ])
    ->filterable();
```

Поведение:

- поддерживает single и multiple значения
- в `index/detail` показывает label из `enum`
- в фильтрах использует выбранные enum keys

## Relation

Класс: `Zak\Lists\Fields\Relation`

Поле для `belongsTo` / похожих связей по FK.

```php
Relation::make('Компания', 'company_id')
    ->model(App\Models\Company::class)
    ->field('name')
    ->list('companies');
```

Дополнительные методы:

```php
->model(Company::class)
->field('name')
->relationName('company')
->list('companies')
->createButton()
->filter(['active', '=', 1])
```

Особенности:

- при наличии `list()` строит ссылку на detail связанной сущности
- умеет ограничивать selectable options через `filter()`

## BelongToMany

Класс: `Zak\Lists\Fields\BelongToMany`

Поле для many-to-many relations.

```php
BelongToMany::make('Товары', 'products')
    ->model(App\Models\Product::class)
    ->field('name')
    ->list('products');
```

Особенности:

- `multiple=true` по умолчанию
- `saveValue()` вызывает `sync()` / `detach()`
- умеет строить ссылки на связанные элементы

## File

Класс: `Zak\Lists\Fields\File`

Поле загрузки файла.

```php
File::make('Файл', 'file')
    ->path('documents')
    ->disk('public');
```

Дополнительные методы:

```php
->path('files')
->disk('public')
```

Особенности:

- загружает файл через `storeAs()`
- умеет удалять старый файл
- поддерживает множественные файлы через `multiple()`
- в index/detail рендерит ссылку на скачивание

## Image

Класс: `Zak\Lists\Fields\Image`

Наследует `File`, но валидирует изображение и показывает preview.

```php
Image::make('Фото', 'photo')
    ->path('images')
    ->maxWidth(200)
    ->maxHeight(200);
```

Дополнительные методы:

```php
->maxWidth(200)
->maxHeight(200)
```

## Location

Класс: `Zak\Lists\Fields\Location`

Поле для координат. В index/detail генерирует ссылку на карту.

```php
Location::make('Локация', 'location');
```

Значение ожидается в виде строки:

```text
latitude, longitude
```

## CustomField

Класс: `Zak\Lists\Fields\CustomField`

Заготовка для нестандартного поведения. Используйте, когда базовых полей недостаточно.

```php
CustomField::make('Статус визита', 'visit_status');
```

Обычно для production-использования создают собственный класс через:

```bash
php artisan zak:make-field VisitStatusField
```

## FieldCollection

Класс: `Zak\Lists\Fields\FieldCollection`

Типизированная коллекция полей. Возвращается через `Component::fieldCollection()`.

Методы:

```php
->visibleForIndex()
->visibleForDetail()
->visibleForCreate()
->visibleForUpdate()
->filterable()
->searchable()
->sortable()
->exportable()
->attributes()
->sortByUserPreference($savedSort)
->withColumnFilter($visibleColumns)
```

Пример:

```php
$attributes = $component->fieldCollection()
    ->visibleForIndex()
    ->exportable()
    ->attributes();
```

## Casts

В пакете есть field-level casts в `src/Fields/Casts/*`.

Использование:

```php
use Zak\Lists\Fields\Casts\StringCast;

Text::make('Имя', 'name')->withCast(new StringCast);
```

Назначение cast-слоя — трансформация значения поля при чтении/записи.

## Практические рекомендации

- Для PK используйте `ID`
- Для строк по умолчанию — `Text`
- Для email, dates, booleans и numbers — специализированные поля
- Для FK используйте `Relation`
- Для M2M — `BelongToMany`
- Для загрузок — `File` / `Image`
- Если логика слишком специфична, создайте custom field через `zak:make-field`

## Связанные документы

- `GETTING_STARTED.md`
- `CUSTOMIZATION.md`
- `API.md`
- `TESTING.md`

