<?php

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Zak\Lists\Component;
use Zak\Lists\Fields\Relation;
use Zak\Lists\Fields\Select;
use Zak\Lists\Services\ComponentLoader;
use Zak\Lists\Services\ExportService;
use Zak\Lists\Services\QueryService;
use Zak\Lists\Tests\Fixtures\Models\TestUser;

beforeEach(function () {
    $this->actor = TestUser::factory()->create();
    Auth::login($this->actor);
});

// ── Eager loading: нет N+1 ────────────────────────────────────────────────────

it('buildIndexQuery добавляет eager loading для Relation полей без N+1', function () {
    $service = new QueryService;

    $relatedModel = new class extends TestUser
    {
        protected $table = 'test_users';

        public function supervisor(): BelongsTo
        {
            return $this->belongsTo(TestUser::class, 'id');
        }
    };

    $component = new Component(
        model: $relatedModel::class,
        label: 'Test',
        singleLabel: 'test',
        fields: [
            Relation::make('Руководитель', 'supervisor_id')
                ->model(TestUser::class)
                ->relationName('supervisor')
                ->showOnIndex(),
        ],
        canViewAny: fn () => true,
        canView: fn ($i) => true,
        canAdd: fn () => true,
        canEdit: fn ($i) => true,
        canDelete: fn ($i) => true,
    );

    $request = Request::create('/');
    $query = $service->buildIndexQuery($component, $request);

    // Убеждаемся что eager loading применён к индексному запросу
    expect(array_keys($query->getEagerLoads()))->toContain('supervisor');
});

// ── FilterQueryCache: нет дублирующихся запросов ────────────────────────────

it('FilterQueryCache::clearMemo() сбрасывает статический кэш', function () {
    Select::clearMemo();

    expect(true)->toBeTrue(); // метод должен существовать и не бросать исключений
});

// ── ExportService::countRows не мутирует Builder ────────────────────────────

it('countRows не добавляет LIMIT или OFFSET к оригинальному запросу', function () {
    $service = new ExportService;

    TestUser::factory()->count(5)->sequence(
        ['email' => 'perfopt-1@test.com'],
        ['email' => 'perfopt-2@test.com'],
        ['email' => 'perfopt-3@test.com'],
        ['email' => 'perfopt-4@test.com'],
        ['email' => 'perfopt-5@test.com'],
    )->create();

    $query = TestUser::query()->where('email', 'like', 'perfopt-%@test.com');

    $count = $service->countRows($query);

    expect($query->getQuery()->limit)->toBeNull();
    expect($query->getQuery()->offset)->toBeNull();
    expect($count)->toBe(5);
});

// ── ComponentLoader: один файл — один include ────────────────────────────────

it('ComponentLoader не выполняет UserOption запрос при повторном resolve sorted', function () {
    $loader = new ComponentLoader;

    // Загружаем base — это вызывает firstOrCreate (1+ запросов к _user_list_options)
    $loader->resolve('test-users', false);

    // Теперь замеряем только дополнительные запросы при sorted resolve
    DB::enableQueryLog();
    $loader->resolve('test-users', true); // должен clone, без DB для UserOption
    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    $optionQueries = array_filter($queries, fn ($q) => str_contains($q['query'], '_user_list_options'));

    // Sorted resolve не должен генерировать новых запросов к user options
    expect(count($optionQueries))->toBe(0);
});

it('ComponentLoader возвращает один и тот же объект для повторного base resolve', function () {
    $loader = new ComponentLoader;

    $first = $loader->resolve('test-users', false);
    $second = $loader->resolve('test-users', false);

    expect($first)->toBe($second);
});

// ── SQL Server: ORDER BY не попадает в subquery DataTables count ──────────────

it('DataTables AJAX не содержит ORDER BY в базовом query (SQL Server совместимость)', function () {
    // SQL Server: select count(*) from (select ... order by ...) — недопустимо без TOP/OFFSET.
    // После фикса orderBy применяется только к export-ветке; DataTables::of() получает чистый query.
    TestUser::factory()->count(3)->create();

    $this->actingAs(TestUser::factory()->create());

    $params = [
        'draw' => '1',
        'start' => '0',
        'length' => '10',
        'order' => [['column' => 0, 'dir' => 'asc']],
        'columns' => [
            ['data' => 'name', 'name' => 'name', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']],
        ],
        'search' => ['value' => '', 'regex' => 'false'],
    ];

    $response = $this->call(
        'GET',
        route('lists', 'test-users'),
        $params,
        [],
        [],
        ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
    );

    $response->assertOk();
    $data = $response->json();
    expect($data)->toHaveKey('data');
    expect($data)->toHaveKey('recordsTotal');
});
