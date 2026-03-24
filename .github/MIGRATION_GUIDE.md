# Zak/Lists v1 → v2 Migration Guide

**Version**: 2.0.0  
**Breaking Changes**: Yes - v2 is a complete rewrite  
**Backward Compatibility**: None (see notes below)

---

## ⚠️ Important: Breaking Changes

Zak/Lists v2 is a complete architectural rewrite. It is **not backward compatible** with v1, but migration is straightforward because the **Component configuration syntax remains mostly the same**.

**Key Breaking Changes:**
1. ❌ No more static `ListComponent` handler methods
2. ❌ Controllers are now thin (delegate to Actions)
3. ❌ Fields no longer have logic methods (`getRules`, `saveValue`, `showIndex`, etc.)
4. ❌ Validation moved to FormRequest classes
5. ❌ All services require constructor DI (no static calls)
6. ❌ Different response format (JSON API standard)

**What Stays the Same:**
1. ✅ Component configuration syntax (field definitions, actions, etc.)
2. ✅ Field API for defining fields
3. ✅ Route names and URLs
4. ✅ Blade view structure (enhanced)
5. ✅ Authorization via policies

---

## 🚀 Migration Steps

### Step 1: Update composer.json

```bash
# Remove v1
composer remove zak/lists

# Install v2
composer require zak/lists:^2.0
```

### Step 2: Publish Updated Package Files

```bash
php artisan vendor:publish --tag="lists-views" --force
php artisan vendor:publish --tag="lists-config" --force
php artisan vendor:publish --tag="lists-migrations" --force
php artisan vendor:publish --tag="lists-lang" --force
```

### Step 3: Database Migration

The UserOption table remains the same, but format may change:

```bash
php artisan migrate
```

### Step 4: Update List Configurations

Your list configs (`app/Lists/*.php`) need minimal updates.

#### v1 Format (OLD):
```php
use Zak\Lists\Component;
use Zak\Lists\Fields\Text;
use Zak\Lists\Action;

return new Component(
    model: User::class,
    label: 'Users',
    singleLabel: 'user',
    fields: [
        Text::make('Name', 'name')->required(),
        Text::make('Email', 'email')->sortable(),
    ],
    actions: [
        Action::make('View')->showAction()->default(),
        Action::make('Edit')->editAction(),
        Action::make('Delete')->deleteAction(),
    ],
);
```

#### v2 Format (NEW):
```php
use Zak\Lists\Component;
use Zak\Lists\Fields\Text;
use Zak\Lists\Action;

return new Component(
    model: User::class,
    label: 'Users',
    singleLabel: 'user',
    fields: [
        Text::make('Name', 'name')->required(),
        Text::make('Email', 'email')->sortable(),
    ],
    actions: [
        Action::make('View')->showAction()->default(),
        Action::make('Edit')->editAction(),
        Action::make('Delete')->deleteAction(),
    ],
);
```

**Notice**: The syntax is almost identical! Only internal behavior changed.

### Step 5: Update Custom Fields

If you created custom fields in v1:

#### v1 Custom Field (OLD):
```php
class MyCustomField extends Field
{
    public function getRules($item = null): array
    {
        return ['my_field' => 'required'];
    }
    
    public function saveValue($item, $data): void
    {
        $item->my_field = $data['my_field'];
    }
    
    public function showIndex($item, $list, $defaultAction = null)
    {
        return '<span>' . $item->my_field . '</span>';
    }
    
    public function generateFilter($query = false)
    {
        // Filter logic
    }
}
```

#### v2 Custom Field (NEW):
```php
class MyCustomField extends Field
{
    public function componentName(): string
    {
        return 'my-custom';
    }
    
    // Add configuration methods instead of logic
    public function withOption(string $key, mixed $value): static
    {
        return tap($this, fn() => $this->options[$key] = $value);
    }
}

// Validation: Handled by ValidationService
// Saving: Handled by FieldService
// Display: Handled by Resources
// Filtering: Handled by QueryService
```

### Step 6: Update Custom Actions

If you extended Action class:

#### v1 Custom Action (OLD):
```php
class CustomAction extends Action
{
    public function getLink($item, $list, $name = '', $class = '')
    {
        return '<a href="/custom/' . $item->id . '">' . $name . '</a>';
    }
}
```

#### v2 Custom Action (NEW):
```php
class CustomAction extends Action
{
    public Closure $linkGenerator;
    
    public function setLinkGenerator(Closure $generator): static
    {
        $this->linkGenerator = $generator;
        return $this;
    }
    
    public function generateLink(Model $item, string $listName): string
    {
        return ($this->linkGenerator)($item, $listName);
    }
}

// Usage in Component:
actions: [
    CustomAction::make('Custom')
        ->setLinkGenerator(fn($item, $list) => '/custom/' . $item->id),
]
```

### Step 7: Update Routes (if custom)

The standard routes remain the same:

```php
// Routes auto-registered from routes/lists.php
Route::middleware(['web', 'auth'])->group(function () {
    Route::any('/lists/{list}', [ListController::class, 'index'])->name('lists');
    Route::get('/lists/{list}/{item}', [ListController::class, 'show'])->name('lists_detail');
    Route::get('/lists/{list}/add', [ListController::class, 'create'])->name('lists_add');
    Route::post('/lists/{list}', [ListController::class, 'store'])->name('lists_store');
    Route::get('/lists/{list}/{item}/edit', [ListController::class, 'edit'])->name('lists_edit');
    Route::put('/lists/{list}/{item}', [ListController::class, 'update'])->name('lists_update');
    Route::delete('/lists/{list}/{item}', [ListController::class, 'destroy'])->name('lists_destroy');
});
```

### Step 8: Update Views (if custom)

If you customized views, update template paths:

#### v1 View Path:
```
resources/views/vendor/lists/list.blade.php
```

#### v2 View Path:
```
resources/views/vendor/lists/list.blade.php  (same)
```

But the variables and structure may have changed:

```blade
<!-- v1 -->
@foreach($items as $item)
    <tr>
        <td>{{ $item->name }}</td>
    </tr>
@endforeach

<!-- v2: Now uses DataTables, variables more structured -->
<!-- See published views for exact syntax -->
```

### Step 9: Update Tests (if any)

If you have tests for lists, update them:

#### v1 Test (OLD):
```php
test('can create user via list', function () {
    $response = $this->post('/lists/users/add', [
        'name' => 'John',
        'email' => 'john@example.com',
    ]);
    
    $response->assertRedirect('/lists/users/1');
});
```

#### v2 Test (NEW):
```php
test('can create user via list', function () {
    $response = $this->postJson('/lists/users', [
        'name' => 'John',
        'email' => 'john@example.com',
    ]);
    
    $response->assertStatus(201);
    $response->assertJsonStructure(['data' => ['id', 'attributes']]);
});
```

### Step 10: Check Configuration

Review `config/lists.php`:

```php
return [
    'path' => app_path('Lists/'),           // Where list configs live
    'layout' => 'layouts.app',              // Default layout
    'middleware' => ['web', 'auth'],        // Routes middleware
    'per_page' => 25,                       // NEW: Default pagination
    'timezone' => config('app.timezone'),   // NEW: For date handling
    'locale' => config('app.locale'),       // NEW: For i18n
];
```

---

## 📋 Field API Changes

### Field Methods That Changed

#### Required (same):
```php
->required()           // ✅ Same
->sortable()           // ✅ Same
->filterable()         // ✅ Same
->searchable()         // ✅ Same
```

#### Display (same):
```php
->showOnIndex()        // ✅ Same
->showOnDetail()       // ✅ Same
->showOnAdd()          // ✅ Same
->showOnUpdate()       // ✅ Same (renamed from showOnEdit)
->hideOnForms()        // ✅ Same
```

#### New Methods:
```php
->width(6)             // Column width (Tailwind)
->label('Display Name') // Custom label
->help('Help text')    // Field helper text
->placeholder('...')   // Placeholder text
->default('value')     // Default value
->virtual()            // Not a database field
->disabled()           // Disabled state
->readonly()           // Read-only field
```

#### Removed Methods:
```php
->getRules()           // ❌ Use ValidationService
->saveValue()          // ❌ Use FieldService
->showIndex()          // ❌ Use Resources
->generateFilter()     // ❌ Use QueryService
->onShowList()         // ❌ Use field formatters
->onShowDetail()       // ❌ Use field formatters
```

---

## 🔄 Component API Changes

### Methods That Stayed the Same:
```php
Component::make(...)
->fields([...])
->actions([...])
->pages([...])
->onQuery(fn($q) => $q)
->onBeforeSave(fn($item) => $item)
->onAfterSave(fn($item) => $item)
->onBeforeDelete(fn($item) => $item)
->onAfterDelete(fn($item) => $item)
```

### New Methods:
```php
->onIndexQuery(fn($q) => $q)      // Specific to list
->onDetailQuery(fn($q) => $q)     // Specific to detail
->onEditQuery(fn($q) => $q)       // Specific to edit form
->withLayout('my.layout')         // Custom layout
->withIcon('icon-name')           // List icon
->withColor('blue')               // Accent color
```

### Removed Methods:
```php
// These were static handlers, not methods:
ListComponent::listHandler()       // ❌ Now automatic
ListComponent::detailHandler()     // ❌ Now automatic
ListComponent::addSaveHandler()    // ❌ Now automatic
ListComponent::editSaveHandler()   // ❌ Now automatic
ListComponent::deleteHandler()     // ❌ Now automatic
```

---

## 🔑 Request/Response Format Changes

### API Responses

#### v1 Response (OLD):
```json
{
    "data": [...],
    "recordsTotal": 100,
    "recordsFiltered": 50,
    "draw": 1
}
```

#### v2 Response (NEW - JSON API Standard):
```json
{
    "data": [
        {
            "id": "1",
            "type": "users",
            "attributes": {
                "name": "John Doe",
                "email": "john@example.com"
            },
            "relationships": {
                "companies": {
                    "data": [{"id": "1", "type": "companies"}]
                }
            }
        }
    ],
    "meta": {
        "pagination": {
            "total": 100,
            "per_page": 25,
            "current_page": 1,
            "last_page": 4
        },
        "filters": [...],
        "actions": [...]
    }
}
```

---

## 📝 Common Migration Scenarios

### Scenario 1: Simple CRUD List

**v1:**
```php
return new Component(
    model: User::class,
    label: 'Users',
    fields: [
        ID::make('ID'),
        Text::make('Name')->required(),
        Text::make('Email')->required(),
    ],
);
```

**v2:** (No changes needed!)
```php
return new Component(
    model: User::class,
    label: 'Users',
    fields: [
        ID::make('ID'),
        Text::make('Name')->required(),
        Text::make('Email')->required(),
    ],
);
```

### Scenario 2: Conditional Fields

**v1:**
```php
auth()->user()->isAdmin() ? Text::make('Secret') : null,
```

**v2:** (Same syntax!)
```php
auth()->user()->isAdmin() ? Text::make('Secret') : null,
```

### Scenario 3: Custom Validation Messages

**v1:**
```php
Text::make('Email')
    ->addRule('unique:users', 'Email already taken!')
```

**v2:** (Use FormRequest instead)
```php
// In app/Http/Requests/ListStoreRequest.php
public function messages(): array
{
    return [
        'email.unique' => __('lists.validation.email_unique'),
    ];
}
```

### Scenario 4: Bulk Actions

**v1:**
```php
bulkActions: [
    new BulkAction(
        'Delete All',
        'delete_all',
        function($items) {
            $items->each->delete();
        }
    ),
]
```

**v2:** (Mostly same, but runs as Job)
```php
bulkActions: [
    BulkAction::make('Delete All', 'delete_all')
        ->callback(function($items) {
            $items->each->delete();
        })
        ->setIcon('trash')
        ->setConfirmText('Are you sure?'),
]
```

### Scenario 5: Custom Pages

**v1:**
```php
pages: [
    'map' => [
        'title' => 'Map',
        'view' => fn($user) => view('user.map', ['user' => $user]),
    ],
]
```

**v2:** (Same!)
```php
pages: [
    'map' => [
        'title' => 'Map',
        'view' => fn($user) => view('user.map', ['user' => $user]),
    ],
]
```

---

## 🧪 Testing Migration

### Update Test Configuration

```php
// tests/TestCase.php
class TestCase extends Orchestra::TestCase
{
    use RefreshDatabase; // Ensure clean state
    
    protected function setUp(): void
    {
        parent::setUp();
        // Setup for Zak/Lists tests
    }
}
```

### Test Example (v2 style)

```php
test('can create user through list', function () {
    // 1. Authenticate
    $user = User::factory()->create(['is_admin' => true]);
    actingAs($user);
    
    // 2. Make request
    $response = $this->postJson(route('lists_store', 'users'), [
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);
    
    // 3. Assert response
    $response->assertCreated();
    $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
});

test('cannot create without authorization', function () {
    $user = User::factory()->create(['is_admin' => false]);
    actingAs($user);
    
    $response = $this->postJson(route('lists_store', 'users'), [
        'name' => 'John',
        'email' => 'john@example.com',
    ]);
    
    $response->assertForbidden();
});
```

---

## ✅ Checklist

Before going to production:

- [ ] Updated composer.json
- [ ] Ran `composer update zak/lists`
- [ ] Published package assets
- [ ] Ran migrations
- [ ] Updated all list configurations
- [ ] Updated custom fields (if any)
- [ ] Updated custom actions (if any)
- [ ] Updated routes (if custom)
- [ ] Updated views (if custom)
- [ ] Updated tests
- [ ] Tested CRUD operations
- [ ] Tested filtering
- [ ] Tested bulk actions
- [ ] Tested authorization
- [ ] Tested export functionality
- [ ] Reviewed error logs
- [ ] Verified performance (should be better!)

---

## 🆘 Troubleshooting

### Issue: "Class 'Zak\Lists\ListComponent' not found"

**Cause**: Using old static methods  
**Solution**: Use new service-based approach or controller actions

```php
// ❌ OLD (doesn't work):
ListComponent::listHandler($request, 'users');

// ✅ NEW:
// Automatic! Just GET /lists/users
return $this->getJson('/lists/users');
```

### Issue: Custom fields not working

**Cause**: Old field methods removed  
**Solution**: Extend Field and use new pattern

```php
// ❌ OLD:
public function getRules() { ... }

// ✅ NEW:
// Let ValidationService handle it
// Define rules in Component or FormRequest
```

### Issue: Blade views have undefined variables

**Cause**: View structure changed  
**Solution**: Check published views in `resources/views/vendor/lists/`

```bash
php artisan vendor:publish --tag="lists-views" --force
```

### Issue: Authorization not working

**Cause**: Policies not updated  
**Solution**: Ensure policies exist for your models

```php
// app/Policies/UserPolicy.php
class UserPolicy
{
    public function viewAny(User $user) { }
    public function view(User $user, User $model) { }
    public function create(User $user) { }
    public function update(User $user, User $model) { }
    public function delete(User $user, User $model) { }
}
```

---

## 📞 Support

If you run into issues:

1. Check the `.github/ARCHITECTURE.md` for design details
2. Review the documentation in `docs/`
3. Check test examples in `tests/Feature/`
4. Open an issue with details

---

## 🎉 Migration Complete!

You should now be running Zak/Lists v2.0 with:
- ✅ Better performance
- ✅ Cleaner code
- ✅ Full test coverage
- ✅ Professional documentation
- ✅ Multi-language support
- ✅ Async processing capability

Enjoy! 🚀

