<?php

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Zak\Lists\Component;
use Zak\Lists\Fields\BelongToMany;
use Zak\Lists\Fields\Relation;
use Zak\Lists\Fields\Text;
use Zak\Lists\Services\QueryService;
use Zak\Lists\Tests\Fixtures\Models\TestUser;

beforeEach(function () {
    $this->user = TestUser::factory()->create();
    Auth::login($this->user);

    $this->queryService = new QueryService;

    $this->component = new Component(
        model: TestUser::class,
        label: 'Test',
        singleLabel: 'test',
        fields: [],
        canViewAny: fn () => true,
        canView: fn ($i) => true,
        canAdd: fn () => true,
        canEdit: fn ($i) => true,
        canDelete: fn ($i) => true,
    );
});

it('строит запрос для индексной страницы', function () {
    $request = Request::create('/lists/test-users');
    $query = $this->queryService->buildIndexQuery($this->component, $request);

    expect($query)->toBeInstanceOf(Builder::class);
    expect($query->getModel())->toBeInstanceOf(TestUser::class);
});

it('применяет OnIndexQuery колбэк компонента', function () {
    $callbackCalled = false;

    $component = new Component(
        model: TestUser::class,
        label: 'Test',
        singleLabel: 'test',
        fields: [],
        canViewAny: fn () => true,
        OnIndexQuery: function ($query) use (&$callbackCalled) {
            $callbackCalled = true;
        },
    );

    $request = Request::create('/');
    $this->queryService->buildIndexQuery($component, $request);

    expect($callbackCalled)->toBeTrue();
});

it('находит элемент по ID', function () {
    $item = TestUser::factory()->create();

    $query = $this->queryService->buildEditQuery($this->component);
    $found = $this->queryService->findOrAbort($this->component, $query, $item->id);

    expect($found->id)->toBe($item->id);
});

it('возвращает 404 если элемент не найден', function () {
    $query = $this->queryService->buildEditQuery($this->component);
    $this->queryService->findOrAbort($this->component, $query, 99999);
})->throws(NotFoundHttpException::class);

it('определяет eager-load связи для индексной страницы', function () {
    $fields = [
        Text::make('Имя', 'name')->showOnIndex(),
    ];

    $relations = $this->queryService->resolveEagerRelations($this->component, $fields);

    expect($relations)->toBeArray();
});

it('buildDetailQuery добавляет eager loading для relation полей detail страницы', function () {
    $relatedModel = new class extends TestUser
    {
        public function manager()
        {
            return $this->belongsTo(TestUser::class, 'id');
        }
    };

    $component = new Component(
        model: $relatedModel::class,
        label: 'Test',
        singleLabel: 'test',
        fields: [
            Relation::make('Менеджер', 'manager_id')
                ->model(TestUser::class)
                ->relationName('manager')
                ->showOnDetail(),
        ],
        canViewAny: fn () => true,
        canView: fn ($i) => true,
        canAdd: fn () => true,
        canEdit: fn ($i) => true,
        canDelete: fn ($i) => true,
    );

    $query = $this->queryService->buildDetailQuery($component);

    expect(array_keys($query->getEagerLoads()))->toContain('manager');
});

it('buildEditQuery добавляет eager loading для relation полей edit формы', function () {
    $relatedModel = new class extends TestUser
    {
        public function tags()
        {
            return $this->belongsToMany(TestUser::class, 'pivot_test_users', 'test_user_id', 'tag_id');
        }
    };

    $component = new Component(
        model: $relatedModel::class,
        label: 'Test',
        singleLabel: 'test',
        fields: [
            BelongToMany::make('Теги', 'tags')
                ->model(TestUser::class)
                ->showOnUpdate(),
        ],
        canViewAny: fn () => true,
        canView: fn ($i) => true,
        canAdd: fn () => true,
        canEdit: fn ($i) => true,
        canDelete: fn ($i) => true,
    );

    $query = $this->queryService->buildEditQuery($component);

    expect(array_keys($query->getEagerLoads()))->toContain('tags');
});

it('resolveEagerRelations удаляет дубли relation names', function () {
    $relatedModel = new class extends TestUser
    {
        public function manager()
        {
            return $this->belongsTo(TestUser::class, 'id');
        }
    };

    $component = new Component(
        model: $relatedModel::class,
        label: 'Test',
        singleLabel: 'test',
        fields: [],
        canViewAny: fn () => true,
        canView: fn ($i) => true,
        canAdd: fn () => true,
        canEdit: fn ($i) => true,
        canDelete: fn ($i) => true,
    );

    $fields = [
        Relation::make('Менеджер 1', 'manager_id')->model(TestUser::class)->relationName('manager'),
        Relation::make('Менеджер 2', 'manager_id')->model(TestUser::class)->relationName('manager'),
    ];

    $relations = $this->queryService->resolveEagerRelations($component, $fields);

    expect($relations)->toBe(['manager']);
});

it('buildIndexQuery добавляет eager loading для Relation полей индекса', function () {
    $relatedModel = new class extends TestUser
    {
        protected $table = 'test_users';

        public function manager(): BelongsTo
        {
            return $this->belongsTo(TestUser::class, 'id');
        }
    };

    $component = new Component(
        model: $relatedModel::class,
        label: 'Test',
        singleLabel: 'test',
        fields: [
            Relation::make('Менеджер', 'manager_id')
                ->model(TestUser::class)
                ->relationName('manager')
                ->showOnIndex(),
        ],
        canViewAny: fn () => true,
        canView: fn ($i) => true,
        canAdd: fn () => true,
        canEdit: fn ($i) => true,
        canDelete: fn ($i) => true,
    );

    $request = Request::create('/');
    $query = $this->queryService->buildIndexQuery($component, $request);

    expect(array_keys($query->getEagerLoads()))->toContain('manager');
});

it('buildIndexQuery добавляет eager loading для BelongToMany полей индекса', function () {
    $relatedModel = new class extends TestUser
    {
        protected $table = 'test_users';

        public function roles(): BelongsToMany
        {
            return $this->belongsToMany(TestUser::class, 'pivot_test_users', 'test_user_id', 'role_id');
        }
    };

    $component = new Component(
        model: $relatedModel::class,
        label: 'Test',
        singleLabel: 'test',
        fields: [
            BelongToMany::make('Роли', 'roles')
                ->model(TestUser::class)
                ->showOnIndex(),
        ],
        canViewAny: fn () => true,
        canView: fn ($i) => true,
        canAdd: fn () => true,
        canEdit: fn ($i) => true,
        canDelete: fn ($i) => true,
    );

    $request = Request::create('/');
    $query = $this->queryService->buildIndexQuery($component, $request);

    expect(array_keys($query->getEagerLoads()))->toContain('roles');
});

it('buildIndexQuery не добавляет eager loading для обычных Text полей', function () {
    $component = new Component(
        model: TestUser::class,
        label: 'Test',
        singleLabel: 'test',
        fields: [
            Text::make('Имя', 'name')->showOnIndex(),
        ],
        canViewAny: fn () => true,
        canView: fn ($i) => true,
        canAdd: fn () => true,
        canEdit: fn ($i) => true,
        canDelete: fn ($i) => true,
    );

    $request = Request::create('/');
    $query = $this->queryService->buildIndexQuery($component, $request);

    expect($query->getEagerLoads())->toBeEmpty();
});
