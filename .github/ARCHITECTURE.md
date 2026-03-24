# Zak/Lists v2.0 Architecture Design

**Status**: STEP 1 - Architecture Documentation  
**Date**: March 18, 2026  
**Version**: 2.0.0 (Breaking Changes)

---

## 🎯 Architecture Principles

### Core Design Decisions

1. **Service-Based Architecture**
   - Each major operation has dedicated, testable service
   - Services are injected via constructor (DI)
   - Services implement interfaces for loose coupling
   - Easier to mock, test, and extend

2. **Action Pattern for CRUD Operations**
   - Each CRUD action (Create, Update, Delete) is separate class
   - Actions contain business logic orchestration
   - Actions use services for heavy lifting
   - Follows Laravel conventions with Jobs for async

3. **Separation of Concerns**
   - **Fields**: Pure configuration objects (no logic)
   - **Services**: Business logic and data manipulation
   - **Requests**: Validation rules and authorization
   - **Resources**: Response formatting and serialization
   - **Controllers**: Thin HTTP layer, delegates to actions

4. **Dependency Injection Throughout**
   - No static methods (except factories like `make()`)
   - Constructor promotion for cleaner code
   - Interface-based dependencies for flexibility
   - Service container binding for extensibility

5. **Testability First**
   - Every class testable in isolation
   - Mock-friendly service interfaces
   - Minimum 80% code coverage target
   - Tests for critical business logic

6. **Documentation & Type Hints**
   - Full PHPDoc blocks on all public methods
   - Strict type hints (PHP 8.3)
   - Examples in docblocks
   - Architecture inline comments for non-obvious logic

---

## 📦 New Package Structure

### Root Level Files
```
.github/
├── ARCHITECTURE.md          ← This file
├── MIGRATION_GUIDE.md       ← v1 → v2 upgrade guide
├── workflows/
│   ├── run-tests.yml
│   ├── static-analysis.yml
│   └── code-coverage.yml

config/
└── lists.php                ← Package configuration

database/
├── factories/
│   └── ModelFactory.php
└── migrations/
    └── create_option_table.php.stub

resources/
├── views/
│   ├── components/          ← NEW: Blade components
│   ├── list.blade.php       ← DataTables listing
│   ├── detail.blade.php     ← Detail view
│   ├── form.blade.php       ← Create/edit form
│   ├── actions.blade.php    ← Action buttons
│   └── filter/              ← Filter views per field type
└── lang/                    ← NEW: i18n translations
    ├── en/
    │   └── lists.php
    └── ru/
        └── lists.php

routes/
└── lists.php                ← 7 HTTP routes

src/
├── Actions/                 ← NEW: CRUD action classes
│   ├── CreateItemAction.php
│   ├── UpdateItemAction.php
│   ├── DeleteItemAction.php
│   ├── BulkActionHandler.php
│   └── ShowItemAction.php
├── Services/                ← NEW: Business logic services
│   ├── ListService.php
│   ├── QueryService.php
│   ├── FieldService.php
│   ├── ValidationService.php
│   ├── AuthorizationService.php
│   ├── DataExportService.php
│   ├── PaginationService.php
│   └── SearchService.php
├── Handlers/                ← NEW: Specialized operation handlers
│   ├── ListQueryHandler.php
│   ├── ListDataHandler.php
│   ├── FieldFilterHandler.php
│   ├── SortHandler.php
│   └── SearchHandler.php
├── Requests/                ← NEW: Form request validation
│   ├── BaseListRequest.php
│   ├── ListIndexRequest.php
│   ├── ListStoreRequest.php
│   ├── ListUpdateRequest.php
│   └── ListDestroyRequest.php
├── Resources/               ← NEW: API response formatting
│   ├── ListItemResource.php
│   ├── ListCollectionResource.php
│   ├── ListFieldResource.php
│   ├── ListFilterResource.php
│   └── ListActionResource.php
├── Jobs/                    ← NEW: Async queue jobs
│   ├── ExportListJob.php
│   ├── BulkActionJob.php
│   └── SendNotificationJob.php
├── Contracts/               ← NEW: Interfaces
│   ├── ListActionContract.php
│   ├── ListServiceContract.php
│   ├── FieldValidatorContract.php
│   ├── QueryHandlerContract.php
│   └── AuthorizationContract.php
├── Exceptions/              ← NEW: Custom exceptions
│   ├── ListException.php
│   ├── ListValidationException.php
│   └── UnauthorizedException.php
├── Listeners/               ← NEW: Event listeners
│   ├── ListCreatedListener.php
│   ├── ListUpdatedListener.php
│   └── ListDeletedListener.php
├── Casts/                   ← NEW: Eloquent casts
│   ├── JsonCast.php
│   └── [custom casts]
├── Component.php            ← Configuration class (refactored)
├── Fields/                  ← REFACTORED: Pure config, no logic
│   ├── Field.php            ← Abstract base field
│   ├── FieldCollection.php  ← Field collection management
│   ├── Concerns/
│   │   └── [field concerns]
│   ├── Casts/               ← Field value transformations
│   │   ├── StringCast.php
│   │   ├── IntegerCast.php
│   │   ├── BooleanCast.php
│   │   └── DateCast.php
│   ├── [15+ field type classes]
│   └── Traits/              ← Field traits
│       ├── FieldProperty.php
│       ├── FieldFilter.php
│       └── FieldEvents.php
├── Action.php               ← Action definition (minimal changes)
├── BulkAction.php           ← Bulk action definition (refactored)
├── Http/
│   ├── Controllers/
│   │   └── ListController.php   ← Thin controller, delegates to actions
│   └── Middleware/
│       └── ListMiddleware.php
├── Models/
│   └── UserOption.php           ← User preferences model
├── Commands/
│   ├── MakeListCommand.php      ← artisan make:list
│   └── MakeFieldCommand.php     ← NEW: artisan make:list-field
├── Traits/
│   ├── HasListConfiguration.php ← NEW: Model trait for lists
│   ├── FieldValidation.php      ← NEW: Validation trait
│   └── FieldFiltering.php       ← NEW: Filtering trait
├── Helpers/
│   └── ListHelpers.php          ← Helper functions
├── ListsServiceProvider.php     ← Refactored provider
└── helper.php                   ← Legacy helpers (backward compat check)

tests/
├── Unit/
│   ├── Services/
│   │   ├── ListServiceTest.php
│   │   ├── QueryServiceTest.php
│   │   ├── FieldServiceTest.php
│   │   ├── ValidationServiceTest.php
│   │   ├── AuthorizationServiceTest.php
│   │   ├── DataExportServiceTest.php
│   │   └── PaginationServiceTest.php
│   ├── Fields/
│   │   ├── TextFieldTest.php
│   │   ├── RelationFieldTest.php
│   │   ├── BelongToManyFieldTest.php
│   │   ├── SelectFieldTest.php
│   │   └── [other field tests]
│   ├── Requests/
│   │   ├── ListStoreRequestTest.php
│   │   ├── ListUpdateRequestTest.php
│   │   └── ListDestroyRequestTest.php
│   ├── Resources/
│   │   ├── ListItemResourceTest.php
│   │   └── ListCollectionResourceTest.php
│   ├── Handlers/
│   │   ├── QueryHandlerTest.php
│   │   ├── DataHandlerTest.php
│   │   ├── FilterHandlerTest.php
│   │   └── SortHandlerTest.php
│   └── Jobs/
│       ├── ExportListJobTest.php
│       └── BulkActionJobTest.php
├── Feature/
│   ├── CrudOperationsTest.php    ← Create, Read, Update, Delete flows
│   ├── FilteringTest.php         ← All filter types
│   ├── SortingTest.php           ← Multi-column sorting
│   ├── SearchingTest.php         ← Full-text search
│   ├── ExportTest.php            ← Excel/CSV export
│   ├── BulkActionsTest.php       ← Bulk operations
│   ├── AuthorizationTest.php     ← Policies and authorization
│   ├── ValidationTest.php        ← Form validation
│   └── LivewireIntegrationTest.php ← NEW: Livewire 4 integration
├── Fixtures/
│   ├── Models/
│   │   ├── TestUser.php
│   │   ├── TestProduct.php
│   │   └── [test models]
│   ├── Factories/
│   │   └── [test factories]
│   └── TestCase.php             ← Base test class with helpers
└── Pest.php                     ← Pest configuration
```

---

## 🔄 Data Flow: List Index Request

```
┌─────────────────────────────────────────────────────────────┐
│  GET /lists/users?page=1&sort=name&active=1                │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
        ┌────────────────────────────────┐
        │  ListController@list()         │
        │  - Thin HTTP entry point       │
        └────────────┬───────────────────┘
                     │
                     ▼
        ┌────────────────────────────────┐
        │  ListIndexRequest::validate()  │
        │  - Validate page, sort, filters│
        │  - Authorize viewAny           │
        └────────────┬───────────────────┘
                     │
                     ▼
        ┌────────────────────────────────┐
        │  ListService::getIndexData()   │
        │  - Orchestrate list retrieval  │
        └────────┬──────────────┬────────┘
                 │              │
        ┌────────▼──┐    ┌──────▼──────────┐
        │ QueryService  │ FieldService    │
        │ - buildQuery()│ - getFields()    │
        │ - getFilters()│ - formatDisplay()│
        │ - applySort() │ - applyRules()   │
        └───┬──────────┘ └──────┬──────────┘
            │                   │
        ┌───▼──────────────────▼────┐
        │ Handler: FilterHandler    │
        │ Handler: SortHandler      │
        │ Handler: PaginationService│
        └───┬──────────────────────┘
            │
            ▼
        ┌────────────────────────────┐
        │ Eloquent Query Results     │
        │ - Executed with eager load │
        └────┬─────────────────────────┘
            │
            ▼
        ┌─────────────────────────────┐
        │ ListCollectionResource      │
        │ - Format items              │
        │ - Include relationships     │
        │ - Include metadata          │
        │ - Check permissions         │
        └────┬──────────────────────────┘
            │
            ▼
        ┌─────────────────────────────┐
        │ JSON Response               │
        │ {                           │
        │   "data": [...],            │
        │   "meta": {pagination, ...} │
        │ }                           │
        └─────────────────────────────┘
```

---

## 🔄 Data Flow: Create Item Request

```
┌────────────────────────────────────────────────────┐
│  POST /lists/users                                │
│  { "name": "John", "email": "john@example.com" }  │
└────────────────┬─────────────────────────────────┘
                 │
                 ▼
        ┌────────────────────────────┐
        │ ListController@store()     │
        │ - Entry point              │
        └────────┬───────────────────┘
                 │
                 ▼
        ┌────────────────────────────┐
        │ ListStoreRequest::validate()
        │ - Validate all fields      │
        │ - Authorize create         │
        │ - Custom rules             │
        └────────┬───────────────────┘
                 │
                 ▼
        ┌────────────────────────────┐
        │ CreateItemAction::handle() │
        │ - Orchestrate save flow    │
        └────────┬───────────────────┘
                 │
        ┌────────▼──────────────────┐
        │ FieldService::saveFields()│
        │ - Process each field      │
        │ - Apply casts/transforms  │
        │ - Handle relationships    │
        └────────┬──────────────────┘
                 │
                 ▼
        ┌────────────────────────────┐
        │ Component::onBeforeSave()  │
        │ - Custom hook              │
        └────────┬───────────────────┘
                 │
                 ▼
        ┌────────────────────────────┐
        │ Model::save()              │
        │ - Database insert          │
        └────────┬───────────────────┘
                 │
                 ▼
        ┌────────────────────────────┐
        │ Component::onAfterSave()   │
        │ - Custom hook              │
        └────────┬───────────────────┘
                 │
                 ▼
        ┌────────────────────────────┐
        │ ListCreatedListener        │
        │ - Emit event               │
        └────────┬───────────────────┘
                 │
                 ▼
        ┌────────────────────────────┐
        │ ListItemResource           │
        │ - Format response          │
        │ - Include relationships    │
        └────────┬───────────────────┘
                 │
                 ▼
        ┌────────────────────────────┐
        │ JSON 201 Response          │
        │ { "data": {...} }          │
        └────────────────────────────┘
```

---

## 📊 Service Responsibilities

### ListService
- Orchestrates high-level list operations
- Delegates to specialized services
- Manages component configuration loading
- Handles authorization checks

```php
interface ListServiceContract
{
    public function getIndexData(Component $component, array $params): array;
    public function getDetailData(Component $component, Model $item): array;
    public function getAvailableActions(Component $component, ?Model $item = null): array;
    public function getAvailableFilters(Component $component): array;
}
```

### QueryService
- Builds Eloquent queries
- Applies filters, sorting, searching
- Eager loads relationships
- Prevents N+1 queries

```php
interface QueryHandlerContract
{
    public function buildQuery(Component $component): Builder;
    public function applyFilters(Builder $query, array $filters): Builder;
    public function applySorting(Builder $query, array $sort): Builder;
    public function applySearch(Builder $query, string $search): Builder;
}
```

### FieldService
- Processes field values
- Applies validation rules
- Handles casting and transformation
- Formats for display

```php
interface FieldServiceContract
{
    public function saveFieldValue(Field $field, Model $model, mixed $value): void;
    public function formatFieldForDisplay(Field $field, Model $model): mixed;
    public function getRulesForField(Field $field, ?Model $model = null): array;
}
```

### ValidationService
- Aggregates validation rules from fields
- Handles custom validation messages
- Supports conditional validation
- Authorization validation

```php
interface FieldValidatorContract
{
    public function validate(array $data, Component $component, string $action): array;
    public function getRules(Component $component, string $action): array;
    public function getMessages(Component $component): array;
}
```

### AuthorizationService
- Checks policies
- Verifies permissions
- Handles role-based access
- Field-level authorization

```php
interface AuthorizationContract
{
    public function canViewAny(Component $component): bool;
    public function canView(Component $component, Model $item): bool;
    public function canCreate(Component $component): bool;
    public function canUpdate(Component $component, Model $item): bool;
    public function canDelete(Component $component, Model $item): bool;
}
```

### DataExportService
- Excel export functionality
- CSV export support
- Asynchronous processing (via Jobs)
- Custom formatting per field

```php
interface DataExportContract
{
    public function toExcel(Collection $items, array $fields, string $filename): StreamedResponse;
    public function toCsv(Collection $items, array $fields, string $filename): StreamedResponse;
    public function queueExport(Component $component, array $params, string $format): Job;
}
```

### PaginationService
- Handles pagination logic
- Configurable page sizes
- Optional cursor-based pagination
- Metadata generation

```php
interface PaginationContract
{
    public function paginate(Builder $query, int $perPage = 25): LengthAwarePaginator;
    public function paginateCursor(Builder $query, string $cursor): CursorPaginator;
    public function getPaginationMeta(): array;
}
```

---

## 🔑 Key Classes

### Component (Refactored)
```php
class Component
{
    public function __construct(
        public string $model,
        public string $label,
        public string $singleLabel,
        public array $fields = [],
        public ?array $actions = null,
        // ... callbacks
    ) {
        // ...
    }
    
    // New methods:
    public function getFieldCollectionModel(): FieldCollection { }
    public function getValidationRules(string $action = 'create'): array { }
    public function getValidationMessages(): array { }
    public function canUserViewAny(): bool { }
    public function canUserView(Model $item): bool { }
    public function canUserCreate(): bool { }
    public function canUserUpdate(Model $item): bool { }
    public function canUserDelete(Model $item): bool { }
}
```

### Field (Refactored - Pure Config)
```php
abstract class Field
{
    public string $name;
    public string $attribute;
    public bool $required = false;
    public bool $sortable = false;
    public bool $filterable = true;
    public bool $searchable = true;
    public array $rules = [];
    public mixed $default = null;
    // ... other config properties
    
    // NO logic methods here anymore!
    // Old methods moved to services:
    // - getRules() → ValidationService
    // - saveValue() → FieldService
    // - generateFilter() → QueryService
    // - showIndex() → Resources
}
```

### Action (Minimal Changes)
```php
class Action
{
    public string $name;
    public string $type = 'action'; // 'show', 'edit', 'delete', 'custom'
    public bool $default = false;
    public ?Closure $show = null; // Condition to show action
    
    public static function make(string $name): static { }
    public function showAction(): static { }
    public function editAction(): static { }
    public function deleteAction(): static { }
    public function custom(Closure $callback): static { }
}
```

### BulkAction (Refactored)
```php
class BulkAction
{
    public function __construct(
        public string $name,
        public string $key,
        public Closure $callback, // Moved to Job for async
    ) { }
    
    public function setConfirmText(string $text): static { }
    public function setSuccessMessage(string $text): static { }
    public function setIcon(string $icon): static { }
    // Jobs now handle async processing
}
```

---

## 🚀 Jobs for Async Processing

### ExportListJob
```php
class ExportListJob implements ShouldQueue
{
    public function __construct(
        public Component $component,
        public array $filters,
        public array $sort,
        public string $format = 'xlsx', // 'xlsx', 'csv'
        public User $user,
    ) { }
    
    public function handle(DataExportService $exportService): void
    {
        // Execute export
        // Send notification to user
    }
}
```

### BulkActionJob
```php
class BulkActionJob implements ShouldQueue
{
    public function __construct(
        public Component $component,
        public BulkAction $action,
        public array $itemIds,
        public User $user,
    ) { }
    
    public function handle(): void
    {
        // Process bulk action
        // Log results
        // Send notification
    }
}
```

---

## 📋 Validation Flow

```php
// OLD approach (mixed concerns):
$rules = [];
foreach ($fields as $field) {
    $rules = array_merge($rules, $field->getRules());
}
$data = $request->validate($rules);

// NEW approach (clean separation):
// 1. Component defines validation rules
class Component {
    public function getValidationRules(string $action = 'create'): array
    {
        return app(ValidationService::class)
            ->buildRules($this, $action);
    }
}

// 2. Form Request uses component rules
class ListStoreRequest extends FormRequest {
    public function rules(): array
    {
        return $this->component->getValidationRules('create');
    }
    
    public function messages(): array
    {
        return $this->component->getValidationMessages();
    }
}

// 3. Controller uses form request
public function store(ListStoreRequest $request)
{
    $data = $request->validated(); // Already validated
    return app(CreateItemAction::class)->handle($component, $data);
}
```

---

## 🧪 Testing Strategy

### Unit Tests
- Service tests (all business logic)
- Field tests (configuration, validation rules)
- Request tests (validation rules, authorization)
- Resource tests (response formatting)

### Feature Tests
- Full CRUD flows
- Filtering with all field types
- Sorting combinations
- Authorization scenarios
- Export functionality
- Bulk actions

### Coverage Target
```
Services:    90%+
Fields:      85%+
Requests:    90%+
Resources:   80%+
Controllers: 85%+
Overall:     80%+
```

---

## 🌍 Internationalization

All user-facing strings moved to translation files:

```
resources/lang/
├── en/lists.php
│   ├── messages.created
│   ├── messages.updated
│   ├── messages.deleted
│   ├── buttons.create
│   ├── buttons.edit
│   ├── validation.required
│   └── ...
└── ru/lists.php
    └── [Russian translations]
```

Usage in code:
```php
// ❌ OLD:
'Успешно создано!'

// ✅ NEW:
trans('lists.messages.created')
__('lists.buttons.create')
```

---

## 🔐 Authorization Strategy

Layer-based authorization:

1. **ViewAny**: Can user see the list?
2. **View**: Can user view this specific item?
3. **Create**: Can user create new items?
4. **Update**: Can user update this item?
5. **Delete**: Can user delete this item?
6. **Field-Level**: Can user see this field?

```php
public function authorizeUser(): bool
{
    return auth()->user()->can('viewAny', $this->component->getModel());
}

// Per-item:
if (!$component->canUserView($item)) {
    abort(403);
}

// Per-field (optional):
if (!$field->isVisibleFor(auth()->user())) {
    $field->hide();
}
```

---

## 📦 Dependencies

### Required
```json
{
    "php": "^8.3",
    "illuminate/framework": "^12.0",
    "illuminate/contracts": "^12.0",
    "yajra/laravel-datatables": "^11.0|^12.0",
    "maatwebsite/excel": "^3.1",
    "spatie/laravel-package-tools": "^1.16"
}
```

### Optional
```json
{
    "livewire/livewire": "^4.0",  // For reactive components
    "livewire/flux": "^2.0"       // Optional Flux UI components
}
```

---

## 🚦 Error Handling

### Exception Hierarchy
```
Exception
├── ListException (all package exceptions extend this)
│   ├── ListValidationException
│   ├── UnauthorizedException
│   ├── ListNotFoundException
│   ├── InvalidFieldException
│   └── InvalidActionException
```

### Error Response Format
```json
{
  "message": "Validation error",
  "errors": {
    "email": ["Email is required"],
    "name": ["Name must be unique"]
  }
}
```

---

## 📈 Performance Considerations

### Query Optimization
- Eager loading by default
- Field-specific query customization
- Relationship filtering at database level
- Index recommendations in migrations

### Caching Strategy
- Cache field definitions per component
- Cache available filters
- Cache user permissions
- Cache filter options (enumerable fields)

### Pagination
- Default 25 items per page (configurable)
- Cursor-based pagination for large datasets
- Total count optimization

---

## 🔄 Component Lifecycle Hooks

```php
Component {
    // Before any operation
    onQuery(fn($query) => $query)               // All queries
    
    // Index-specific
    onIndexQuery(fn($query) => $query)          // List query
    
    // Detail-specific
    onDetailQuery(fn($query) => $query)         // Detail query
    
    // Edit-specific
    onEditQuery(fn($query) => $query)           // Edit form query
    
    // Save lifecycle
    onBeforeSave(fn($item) => $item)            // Before save
    onAfterSave(fn($item) => $item)             // After save
    
    // Delete lifecycle
    onBeforeDelete(fn($item) => $item)          // Before delete
    onAfterDelete(fn($item) => $item)           // After delete
    
    // Custom pages
    customPages: [
        'route' => [
            'title' => 'Routes',
            'view' => fn($item) => view(...)
        ]
    ]
}
```

---

## 🎯 Migration Path (v1 → v2)

**Major Breaking Changes:**
1. No more `ListComponent::handler()` static methods
2. Controller now thin, delegates to Actions
3. Fields no longer have logic methods (getRules, saveValue, etc.)
4. Validation now in FormRequest classes
5. All services require DI

**What Stays the Same:**
1. Component configuration syntax (mostly)
2. Field API for defining fields
3. Action definitions
4. Route names and URLs
5. Blade view structure

**See**: `.github/MIGRATION_GUIDE.md` for detailed upgrade steps

---

## ✅ Quality Standards

- **Code Style**: PSR-12 + Laravel Pint
- **Static Analysis**: PHPStan level 9
- **Tests**: Minimum 80% coverage
- **Documentation**: All public methods documented
- **Type Hints**: All parameters and return types
- **Performance**: <100ms for typical list queries

---

This architecture ensures the v2.0 package is:
- ✅ Professional and production-ready
- ✅ Testable and maintainable
- ✅ Flexible and extensible
- ✅ Well-documented
- ✅ Performant and scalable

