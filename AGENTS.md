# AGENTS.md — Zak/Lists

Laravel CRUD-пакет для административных data grids. PHP 8.2+, Laravel 11/12, PHPStan level 6, Pest suite (~450 tests).

## Architecture Overview

**Component** = PHP file in `app/Lists/{name}.php` that returns a `new Component(...)` instance.  
This is the central config object — not a class, not a migration, just a `return` statement with a `Component`.

```
Request → ListController → {Index|Edit|Store|…}Action → ComponentLoader → QueryService → DataTables / ExportService
```

Key objects:
- `src/Component.php` — holds model, fields, actions, lifecycle closures, per-user `UserOption`
- `src/Fields/Field.php` — abstract base; all fields extend it with fluent API
- `src/Services/ComponentLoader.php` — `include`s the PHP file, caches per request, clones for sorted variant
- `src/Models/UserOption.php` — per-user state (`_user_list_options` table): column order, sort, visible columns, active filters
- `src/Jobs/ExportListJob.php` — async export; **re-resolves Component from file** inside the job because PHP closures can't be serialized

## Developer Workflows

```bash
# Run tests (SQLite in-memory via Orchestra Testbench)
./vendor/bin/pest

# Static analysis
./vendor/bin/phpstan analyse

# Generate a new component stub
php artisan zak:make-component Users --model=User
# → creates app/Lists/Users.php

# Generate a new field class
php artisan zak:make-field MyField
```

Test fixtures live in `tests/Fixtures/` (Lists, Models, Factories, views). The test layout is registered from `tests/Fixtures/views/`.

## Project-Specific Patterns

### Component files are `include`d, not instantiated
`ComponentLoader::resolve()` does `$component = include $file`. The file must `return new Component(...)`. Never store state between requests inside the file.

### Closures on Component are not serializable
`ExportListJob` receives only primitives (`list` name, `requestData` array, `userId`). It re-builds the `Component` and `Request` inside `handle()`. Never dispatch a job with a `Component` instance directly.

### Field fluent API
```php
Text::make('Name', 'name')
    ->sortable()->searchable()->required()
    ->showOnIndex()->showOnDetail();
```
Use `Field::make()` (via `Makeable` trait) rather than `new Field()`.

### Authorization defaults to Laravel Policies
`Component` defaults:
- `canViewAny` → `$user->can('viewAny', Model::class)`  
- `canEdit` / `canDelete` / `canView` → `$user->can('edit|delete|view', $item)`

Override per-component with a closure: `canEdit: fn ($item) => $item->owner_id === auth()->id()`.

### Export three-tier flow (`IndexAction::handleExport`)
1. Count rows first (cheap `COUNT` query).
2. `> max_export_rows` → flash `js_error`, abort.
3. `> export_async_threshold` → dispatch `ExportListJob`, flash `js_success`.
4. Otherwise → synchronous `ExportService::downloadQuerySafe()` with chunking.

Config knobs: `lists.max_export_rows` (default 50 000), `lists.export_async_threshold` (default 5 000), `lists.export_chunk_size` (default 500).

### Translations use short form
Both `__('lists.key')` and `__('lists::key')` work — the provider registers the lang path twice in `packageBooted()`.

### UserOption stores all per-user UI state
`component->options->value` is a JSON blob with keys: `curSort`, `length`, `columns`, `filters`, `sort`. Always read defensively: `$options['curSort'] ?? ['id', 'desc']`.

## Key Files

| Path | Purpose |
|---|---|
| `src/Component.php` | Central config + lifecycle hooks |
| `src/Actions/IndexAction.php` | DataTables AJAX + export orchestration |
| `src/Services/ComponentLoader.php` | File include + per-request cache |
| `src/Services/QueryService.php` | Eager-load + `OnQuery` hooks |
| `src/Services/ExportService.php` | Excel generation (sync and chunked) |
| `src/Jobs/ExportListJob.php` | Async export (queue) |
| `src/ListsServiceProvider.php` | Service bindings, config validation |
| `config/lists.php` | All tunable knobs |
| `routes/lists.php` | All package routes (`/lists/{list}`, etc.) |
| `stubs/component.stub` | Template used by `zak:make-component` |
| `tests/TestCase.php` | Orchestra Testbench base; SQLite in-memory |
| `tests/Fixtures/` | Test components, models, factories, views |

## Contracts → Implementations

| Contract | Implementation |
|---|---|
| `ComponentLoaderContract` | `ComponentLoader` (singleton) |
| `AuthorizationContract` | `AuthorizationService` |
| `QueryContract` | `QueryService` |
| `FieldServiceContract` | `FieldService` |

`ExportService` is bound directly (no contract) as a singleton.

