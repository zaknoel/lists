# Анализ пакета `zaknoel/lists` v2.0.0

## Общая картина

Пакет предоставляет CRUD-таблицы (DataTables) для Laravel-приложений: индекс, добавление, редактирование, удаление, детальный просмотр, bulk-действия, экспорт в Excel. Архитектура активно рефакторится (есть deprecated [ListComponent.php](file:///Users/zaknoel/MAMP/www/zak.list/packages/zaknoel/lists/src/ListComponent.php) и новые [Actions/](file:///Users/zaknoel/MAMP/www/zak.list/packages/zaknoel/lists/src/Component.php#187-191)+`Services/`). В целом — хороший задел, но есть ряд сдерживающих проблем.

---

## 🔴 Критические проблемы

### 1. Публичный API-ключ Яндекс.Карт в исходном коде
**Файл:** [Component.php](file:///Users/zaknoel/MAMP/www/zak.list/packages/zaknoel/lists/src/Component.php#L388)
```php
// Строка 388 — ключ зашит прямо в PHP-файл
'<script src="https://api-maps.yandex.ru/2.1/?lang=ru_RU&apikey=f583857c-aaf5-454e-943b-d94c3e908c3f" ...>'
```
**Риск:** ключ попадает в git-историю и может быть использован чужими сервисами.  
**Решение:** вынести в [config/lists.php](file:///Users/zaknoel/MAMP/www/zak.list/packages/zaknoel/lists/config/lists.php) → `yandex_maps_key`, читать через [config()](file:///Users/zaknoel/MAMP/www/zak.list/packages/zaknoel/lists/src/ListsServiceProvider.php#22-48).

---

### 2. `header()` + `exit` вместо Laravel Response
**Файл:** [Component.php](file:///Users/zaknoel/MAMP/www/zak.list/packages/zaknoel/lists/src/Component.php#L346-L348)
```php
header('Location: '.$redirectTo);
exit;
```
**Проблема:** обходит весь Laravel middleware stack (сессии, cookies, логирование), ломает тесты, несовместимо с Octane/FrankenPHP.  
**Решение:** `throw new HttpRedirectException($redirectTo)` или вернуть `RedirectResponse`.

---

## 🟠 Серьёзные проблемы

### 3. Конструктор [Component](file:///Users/zaknoel/MAMP/www/zak.list/packages/zaknoel/lists/src/Component.php#14-411) с 30 параметрами
**Файл:** [Component.php](file:///Users/zaknoel/MAMP/www/zak.list/packages/zaknoel/lists/src/Component.php#L22-L55)

Конструктор принимает 30 параметров — это анти-паттерн. Создавать объект вручную невозможно читаемо.

**Решение:** паттерн Builder / fluent API:
```php
Component::for(User::class)
    ->label('Пользователи')
    ->canEdit(fn($item) => auth()->user()->isAdmin())
    ->fields([...]);
```

---

### 4. `Field::create()` с 24 параметрами
**Файл:** [Fields/Field.php](file:///Users/zaknoel/MAMP/www/zak.list/packages/zaknoel/lists/src/Fields/Field.php#L42-L139)

Статический фабричный метод с 24 параметрами. Существующий fluent-API (`make()->required()->sortable()...`) решает задачу лучше — [create()](file:///Users/zaknoel/MAMP/www/zak.list/packages/zaknoel/lists/src/Fields/Field.php#42-140) лишний.

**Решение:** объявить [create()](file:///Users/zaknoel/MAMP/www/zak.list/packages/zaknoel/lists/src/Fields/Field.php#42-140) как `@deprecated` и удалить в v3.

---

### 5. Дублирование логики "элемент не найден"
**Файл:** [ListComponent.php](file:///Users/zaknoel/MAMP/www/zak.list/packages/zaknoel/lists/src/ListComponent.php) — методы [detailHandler](file:///Users/zaknoel/MAMP/www/zak.list/packages/zaknoel/lists/src/ListComponent.php#237-294), [editFormHandler](file:///Users/zaknoel/MAMP/www/zak.list/packages/zaknoel/lists/src/ListComponent.php#295-343), [pagesHandler](file:///Users/zaknoel/MAMP/www/zak.list/packages/zaknoel/lists/src/ListComponent.php#495-538)

Один и тот же блок повторяется трижды:
```php
$item = $query->where('id', $item)->first();
if (!$item) {
    $query2 = $class::query();
    $item = $query2->withoutGlobalScopes()->where('id', $item_id)->first();
    if ($item) { setJsWarning(...); return redirect('/'); }
    abort(404);
}
```
**Решение:** вынести в приватный метод `resolveItemOrFail(Builder $query, int $id): Model`.

---

### 6. Неявное сохранение пользовательских предпочтений при каждом AJAX-запросе
**Файл:** [Actions/IndexAction.php](file:///Users/zaknoel/MAMP/www/zak.list/packages/zaknoel/lists/src/Actions/IndexAction.php#L295-L310)

Каждый DataTables AJAX-запрос с параметром `length` или `order` триггерит `UserOption::save()` — лишние SQL UPDATE на каждую строку таблицы.

**Решение:** сравнивать новые значения со старыми, сохранять только при реальном изменении:
```php
if ($options['length'] !== $newLength) {
    $options['length'] = $newLength;
    $component->options->value = $options;
    $component->options->save();
}
```

---

### 7. [UserOption](file:///Users/zaknoel/MAMP/www/zak.list/packages/zaknoel/lists/src/Models/UserOption.php#14-26) использует `$guarded = []`
**Файл:** [Models/UserOption.php](file:///Users/zaknoel/MAMP/www/zak.list/packages/zaknoel/lists/src/Models/UserOption.php#L20)

`$guarded = []` означает полную отмену mass-assignment protection. Если данные из [Request](file:///Users/zaknoel/MAMP/www/zak.list/packages/zaknoel/lists/src/Actions/IndexAction.php#325-329) когда-либо попадут напрямую в `->fill()`, это уязвимость.  
**Решение:** явно указать `$fillable = ['user_id', 'name', 'value']`.

---

## 🟡 Умеренные проблемы

### 8. Хардкод русских строк в PHP-коде
**Файлы:** [ListComponent.php](file:///Users/zaknoel/MAMP/www/zak.list/packages/zaknoel/lists/src/ListComponent.php), [Component.php](file:///Users/zaknoel/MAMP/www/zak.list/packages/zaknoel/lists/src/Component.php), [Actions/](file:///Users/zaknoel/MAMP/www/zak.list/packages/zaknoel/lists/src/Component.php#187-191).

```php
'Успешно обновлено!'
'Элемент удален успешно !'
'Редактировать '.$component->getSingleLabel()
'Проект переключился на другой...'
```
Часть строк уже вынесена в `lang/`, но многие всё ещё хардкодены.  
**Решение:** последовательно заменить на [__('lists.key')](file:///Users/zaknoel/MAMP/www/zak.list/packages/zaknoel/lists/src/Fields/Field.php#36-41).

---

### 9. [show()](file:///Users/zaknoel/MAMP/www/zak.list/packages/zaknoel/lists/src/Fields/Field.php#186-202) в [Field](file:///Users/zaknoel/MAMP/www/zak.list/packages/zaknoel/lists/src/Fields/Field.php#16-293) создаёт файлы на диске в рантайме
**Файл:** [Fields/Field.php](file:///Users/zaknoel/MAMP/www/zak.list/packages/zaknoel/lists/src/Fields/Field.php#L192-L197)
```php
file_put_contents($file, '<div></div>');
```
Создание view-файла прямо в HTTP-запросе — неожиданное поведение, засоряет репозиторий, ломает immutable-деплои (read-only filesystem).  
**Решение:** выбрасывать `\RuntimeException` с подсказкой, а не молча создавать файл.

---

### 10. [ExportService](file:///Users/zaknoel/MAMP/www/zak.list/packages/zaknoel/lists/src/Services/ExportService.php#16-173) делает [count()](file:///Users/zaknoel/MAMP/www/zak.list/packages/zaknoel/lists/src/Services/ExportService.php#164-172) дважды
**Файл:** [Actions/IndexAction.php](file:///Users/zaknoel/MAMP/www/zak.list/packages/zaknoel/lists/src/Actions/IndexAction.php#L167-L186)

```php
if ($this->exportService->exceedsExportLimit($query)) { // COUNT #1
    $count = $this->exportService->countRows($query);    // COUNT #2
}
if ($this->exportService->shouldQueueExport($query)) {   // COUNT #3
    $count = $this->exportService->countRows($query);    // COUNT #4
}
```
Один и тот же `SELECT COUNT(*)` выполняется до 4 раз.  
**Решение:** сделать `$count = $exportService->countRows($query)` один раз, передавать значение в методы.

---

### 11. [getCurPageParams()](file:///Users/zaknoel/MAMP/www/zak.list/packages/zaknoel/lists/src/helper.php#6-20) использует `Arr::forget` без импорта
**Файл:** [helper.php](file:///Users/zaknoel/MAMP/www/zak.list/packages/zaknoel/lists/src/helper.php#L13)
```php
Arr::forget($r, $remove); // Arr не импортирован!
```
Работает только потому что `Arr` находится в глобальном namespace через фасад — но это хрупко.  
**Решение:** добавить `use Illuminate\Support\Arr;` или использовать `array_except()`.

---

### 12. `deprecated` класс [ListComponent](file:///Users/zaknoel/MAMP/www/zak.list/packages/zaknoel/lists/src/ListComponent.php#22-539) не удалён
**Файл:** [ListComponent.php](file:///Users/zaknoel/MAMP/www/zak.list/packages/zaknoel/lists/src/ListComponent.php) — 539 строк

Помечен как `@deprecated since v2.0`, но остаётся в пакете и дублирует логику из [Actions/](file:///Users/zaknoel/MAMP/www/zak.list/packages/zaknoel/lists/src/Component.php#187-191). Это техдолг, который нужно нести и обновлять при изменениях.  
**Решение:** удалить в v3.0 (уже задокументировано в [MIGRATION.md](file:///Users/zaknoel/MAMP/www/zak.list/packages/zaknoel/lists/MIGRATION.md) — нужно просто отрезать).

---

## 🟢 Возможности для улучшения

### 13. Отсутствие кеширования компонентов на уровне запроса
`ComponentLoader::resolve()` вызывает `include $file` при каждом обращении. Функция `once()` использована в старом коде, но не во всех новых Actions.  
**Решение:** убедиться, что `ComponentLoaderContract` кешируется в scope запроса (singleton + array cache внутри).

### 14. [getQuery()](file:///Users/zaknoel/MAMP/www/zak.list/packages/zaknoel/lists/src/Component.php#227-231) не применяет [OnQuery](file:///Users/zaknoel/MAMP/www/zak.list/packages/zaknoel/lists/src/Component.php#260-268) автоматически
**Файл:** [Component.php](file:///Users/zaknoel/MAMP/www/zak.list/packages/zaknoel/lists/src/Component.php#L227-L230)
```php
public function getQuery(): Builder
{
    return $this->model::query(); // OnQuery НЕ применяется
}
```
Разработчик ожидает, что базовый скоуп всегда активен. Нужно либо применять [eventOnQuery()](file:///Users/zaknoel/MAMP/www/zak.list/packages/zaknoel/lists/src/Component.php#260-268) здесь, либо явно документировать, что [getQuery()](file:///Users/zaknoel/MAMP/www/zak.list/packages/zaknoel/lists/src/Component.php#227-231) возвращает «чистый» Builder.

### 15. [isAjaxRequest()](file:///Users/zaknoel/MAMP/www/zak.list/packages/zaknoel/lists/src/Actions/IndexAction.php#325-329) дублируется в двух местах
Метод присутствует в [ListComponent](file:///Users/zaknoel/MAMP/www/zak.list/packages/zaknoel/lists/src/ListComponent.php#22-539) (строка 152) и в [IndexAction](file:///Users/zaknoel/MAMP/www/zak.list/packages/zaknoel/lists/src/Actions/IndexAction.php#27-330) (строка 325) — идентичная реализация.  
**Решение:** вынести в отдельный helper или trait.

### 16. Нет типизации у ряда публичных свойств [Field](file:///Users/zaknoel/MAMP/www/zak.list/packages/zaknoel/lists/src/Fields/Field.php#16-293)
```php
public $value;  // нет типа — может быть чем угодно
```
**Решение:** `public mixed $value = null;` или интерфейс `ValueObject`.

### 17. Тесты практически отсутствуют
В `tests/` есть только [ExampleTest.php](file:///Users/zaknoel/MAMP/www/zak.list/packages/zaknoel/lists/tests/ExampleTest.php) (1 строка), [ArchTest.php](file:///Users/zaknoel/MAMP/www/zak.list/packages/zaknoel/lists/tests/ArchTest.php) и пустой [Pest.php](file:///Users/zaknoel/MAMP/www/zak.list/packages/zaknoel/lists/tests/Pest.php). Нет ни одного функционального теста для Actions, Fields, Export.  
**Риск:** любой рефакторинг проходит без страховочной сети.  
**Решение:** написать Feature-тесты через testbench для [IndexAction](file:///Users/zaknoel/MAMP/www/zak.list/packages/zaknoel/lists/src/Actions/IndexAction.php#27-330), `EditAction`, `CreateAction`.

---

## Приоритизированный план улучшений

| Приоритет | Задача | Усилие |
|-----------|--------|--------|
| 🔴 1 | Вынести Yandex API key в конфиг | 15 мин |
| 🔴 2 | Заменить `header()`+`exit` на Laravel redirect | 30 мин |
| 🟠 3 | Исправить `$guarded = []` → `$fillable` | 5 мин |
| 🟠 4 | Устранить дублирование `resolveItemOrFail` | 1 ч |
| 🟠 5 | Убрать дублирующие `COUNT` запросы в Export | 30 мин |
| 🟠 6 | Deprecate `Field::create()` | 15 мин |
| 🟡 7 | Перенести все RU-строки в lang-файлы | 2-3 ч |
| 🟡 8 | Убрать `file_put_contents` в `Field::show()` | 20 мин |
| 🟡 9 | Удалить [ListComponent.php](file:///Users/zaknoel/MAMP/www/zak.list/packages/zaknoel/lists/src/ListComponent.php) (v3 milestone) | 1 ч |
| 🟢 10 | Написать Feature-тесты для Actions | 4-8 ч |
| 🟢 11 | Builder-паттерн для [Component](file:///Users/zaknoel/MAMP/www/zak.list/packages/zaknoel/lists/src/Component.php#14-411) | 3-5 ч |
| 🟢 12 | Добавить импорт `Arr` в [helper.php](file:///Users/zaknoel/MAMP/www/zak.list/packages/zaknoel/lists/src/helper.php) | 2 мин |
