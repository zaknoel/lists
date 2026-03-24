# Changelog

All notable changes to `zak/lists` will be documented in this file.

## [2.0.0] - 2026-03-19

### Added

- v2 service-based architecture with `Actions`, `Services`, `Requests` and `Resources`
- `AuthorizationService`, `ComponentLoader`, `FieldService`, `QueryService`, `ExportService`
- CRUD action classes: `CreateAction`, `StoreAction`, `ShowAction`, `EditAction`, `UpdateAction`, `DestroyAction`
- additional actions: `IndexAction`, `SaveOptionsAction`, `BulkActionRunner`, `PageAction`
- API resources: `ListItemResource`, `ListCollectionResource`, `ListFieldResource`, `ListFilterResource`, `ListActionResource`
- form requests for store/update/destroy/options/bulk flows
- i18n layer via `resources/lang/en/lists.php` and `resources/lang/ru/lists.php`
- export row limits: `max_export_rows` (hard cap) and `export_async_threshold` (queue offload)
- `src/Jobs/ExportListJob` — queued Excel export with re-authentication and disk storage
- `src/Jobs/BulkActionJob` — queued bulk-action execution for invokable callbacks
- `BulkAction::async()` fluent method for queued bulk actions
- `src/Fields/Traits/FilterQueryCache` — per-request memoization of relation pluck queries
- `ComponentLoader` — clone-based sorted-variant caching (single `UserOption::firstOrCreate` per request)
- `QueryService::buildIndexQuery` — unified eager loading for index (consistent with detail/edit paths)
- `ListsServiceProvider::validateConfig()` — early config validation with clear error messages
- `zak:make-component` Artisan command with stub-based generation (fixes legacy `\<?php` output bug)
- `stubs/component.stub` — clean component template
- PHPStan analysis at level 6 with generated baseline
- architecture tests: `Jobs` implement `ShouldQueue`, `Contracts` are interfaces, `Fields` extend base `Field`
- documentation set: `README.md`, `GETTING_STARTED.md`, `ARCHITECTURE.md`, `API.md`, `FIELDS.md`, `CUSTOMIZATION.md`, `MIGRATION.md`, `TESTING.md`, `TROUBLESHOOTING.md`
- extended Pest suite: **450 passing tests / 707 assertions**

### Changed

- `Component` now acts as declarative list configuration object
- field validation messages support translation keys and raw custom strings
- default action labels use translations
- `ComponentLoader` and user-facing field messages now use package translations
- `BulkAction::$callback` type widened from `\Closure` to `mixed` to support invokable classes
- `handleDataRequest` guards `$curSort` against empty-array values (bug fix)
- documentation is Russian-first and aligned with the real v2 API
- `zak:component` renamed to `zak:make-component` for consistency with `zak:make-field`

### Removed

- legacy class `Zak\Lists\ListComponent` removed from package

### Internal

- total automated coverage: **450 passing tests / 707 assertions** (1 skipped)
- PHPStan level 6, baseline with pre-existing violations in untyped legacy-compatible APIs
