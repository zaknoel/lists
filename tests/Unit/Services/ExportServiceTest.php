<?php

use Illuminate\Support\Facades\Auth;
use Zak\Lists\Component;
use Zak\Lists\Fields\Boolean;
use Zak\Lists\Fields\Text;
use Zak\Lists\ListImport;
use Zak\Lists\Services\ExportService;
use Zak\Lists\Tests\Fixtures\Models\TestUser;

beforeEach(function () {
    $this->user = TestUser::factory()->create();
    Auth::login($this->user);

    $this->service = new ExportService;
    $this->component = new Component(
        model: TestUser::class,
        label: 'Пользователи',
        singleLabel: 'пользователь',
        fields: [
            Text::make('Имя', 'name')->showOnIndex(),
            Boolean::make('Активность', 'active')->showOnIndex(),
        ],
        canViewAny: fn () => true,
        canView: fn ($i) => true,
        canAdd: fn () => true,
        canEdit: fn ($i) => true,
        canDelete: fn ($i) => true,
    );
});

it('buildRowsFromQuery строит export data из query', function () {
    TestUser::factory()->create(['name' => 'Иван', 'email' => 'ivan.export@test.com', 'active' => true]);
    TestUser::factory()->create(['name' => 'Пётр', 'email' => 'petr.export@test.com', 'active' => false]);

    $fields = $this->component->getFields();
    $rows = $this->service->buildRowsFromQuery(
        $this->component,
        TestUser::query()->whereIn('email', ['ivan.export@test.com', 'petr.export@test.com'])->orderBy('id'),
        $fields,
        'test-users',
        1,
    );

    expect($rows)->toHaveKey('data');
    expect($rows['data'])->toHaveCount(2);
    expect($rows['data'][0])->toHaveKey('name');
    expect($rows['data'][0])->toHaveKey('active');
});

it('buildRowsFromQuery использует chunked lazy iteration без потери строк', function () {
    TestUser::factory()->count(5)->sequence(
        ['email' => 'chunk1@test.com'],
        ['email' => 'chunk2@test.com'],
        ['email' => 'chunk3@test.com'],
        ['email' => 'chunk4@test.com'],
        ['email' => 'chunk5@test.com'],
    )->create();

    $rows = $this->service->buildRowsFromQuery(
        $this->component,
        TestUser::query()->where('email', 'like', 'chunk%@test.com')->orderBy('id'),
        $this->component->getFields(),
        'test-users',
        2,
    );

    expect($rows['data'])->toHaveCount(5);
});

it('buildRowsFromQuery возвращает пустой data для пустого query', function () {
    $rows = $this->service->buildRowsFromQuery(
        $this->component,
        TestUser::query()->where('email', 'empty-export@test.com'),
        $this->component->getFields(),
        'test-users',
        2,
    );

    expect($rows['data'])->toBeEmpty();
});

// ── 9B: exceedsExportLimit ────────────────────────────────────────────────────

it('exceedsExportLimit возвращает true если строк больше лимита', function () {
    config(['lists.max_export_rows' => 2]);

    TestUser::factory()->count(3)->sequence(
        ['email' => 'limit-a1@test.com'],
        ['email' => 'limit-a2@test.com'],
        ['email' => 'limit-a3@test.com'],
    )->create();

    $query = TestUser::query()->where('email', 'like', 'limit-a%@test.com');

    expect($this->service->exceedsExportLimit($query))->toBeTrue();
});

it('exceedsExportLimit возвращает false если строк не больше лимита', function () {
    config(['lists.max_export_rows' => 100]);

    TestUser::factory()->count(2)->sequence(
        ['email' => 'underlimit-b1@test.com'],
        ['email' => 'underlimit-b2@test.com'],
    )->create();

    $query = TestUser::query()->where('email', 'like', 'underlimit-b%@test.com');

    expect($this->service->exceedsExportLimit($query))->toBeFalse();
});

it('exceedsExportLimit возвращает false при лимите 0 (отключён)', function () {
    config(['lists.max_export_rows' => 0]);

    TestUser::factory()->count(5)->create();

    $query = TestUser::query();

    expect($this->service->exceedsExportLimit($query))->toBeFalse();
});

it('shouldQueueExport возвращает true если строк больше порога', function () {
    config(['lists.export_async_threshold' => 2]);

    TestUser::factory()->count(5)->sequence(
        ['email' => 'async-c1@test.com'],
        ['email' => 'async-c2@test.com'],
        ['email' => 'async-c3@test.com'],
        ['email' => 'async-c4@test.com'],
        ['email' => 'async-c5@test.com'],
    )->create();

    $query = TestUser::query()->where('email', 'like', 'async-c%@test.com');

    expect($this->service->shouldQueueExport($query))->toBeTrue();
});

it('shouldQueueExport возвращает false при пороге 0 (отключён)', function () {
    config(['lists.export_async_threshold' => 0]);

    TestUser::factory()->count(5)->create();

    $query = TestUser::query();

    expect($this->service->shouldQueueExport($query))->toBeFalse();
});

it('countRows не модифицирует оригинальный Builder', function () {
    TestUser::factory()->count(3)->sequence(
        ['email' => 'count-d1@test.com'],
        ['email' => 'count-d2@test.com'],
        ['email' => 'count-d3@test.com'],
    )->create();

    $query = TestUser::query()->where('email', 'like', 'count-d%@test.com');
    $originalSql = $query->toRawSql();

    $count = $this->service->countRows($query);

    expect($count)->toBe(3);
    expect($query->toRawSql())->toBe($originalSql);
});

it('exceedsExportLimitByCount возвращает true когда count больше лимита', function () {
    config(['lists.max_export_rows' => 10]);

    expect($this->service->exceedsExportLimitByCount(11))->toBeTrue();
});

it('exceedsExportLimitByCount возвращает false когда count не больше лимита', function () {
    config(['lists.max_export_rows' => 10]);

    expect($this->service->exceedsExportLimitByCount(10))->toBeFalse();
});

it('shouldQueueExportByCount возвращает true когда count больше порога', function () {
    config(['lists.export_async_threshold' => 5]);

    expect($this->service->shouldQueueExportByCount(6))->toBeTrue();
});

it('shouldQueueExportByCount возвращает false когда count не больше порога', function () {
    config(['lists.export_async_threshold' => 5]);

    expect($this->service->shouldQueueExportByCount(5))->toBeFalse();
});

// ── prepareExportData ─────────────────────────────────────────────────────────

it('prepareExportData возвращает заголовок как первую строку', function () {
    $fields = $this->component->getFields();
    $rows = ['data' => []];

    $result = $this->service->prepareExportData($rows, $fields);

    expect($result)->toHaveCount(1);
    expect($result[0])->toContain('Имя');
    expect($result[0])->toContain('Активность');
});

it('prepareExportData преобразует строки данных и strip_tags значения', function () {
    $fields = $this->component->getFields();
    $rows = [
        'data' => [
            ['name' => '<b>Иван</b>', 'active' => '1'],
            ['name' => 'Пётр', 'active' => '0'],
        ],
    ];

    $result = $this->service->prepareExportData($rows, $fields);

    expect($result)->toHaveCount(3); // header + 2 rows
    expect($result[1]['name'])->toBe('Иван');
    expect($result[2]['name'])->toBe('Пётр');
});

it('prepareExportData фильтрует только разрешённые атрибуты полей', function () {
    $fields = $this->component->getFields(); // name + active
    $rows = [
        'data' => [
            ['name' => 'Иван', 'active' => '1', 'secret' => 'не должно попасть'],
        ],
    ];

    $result = $this->service->prepareExportData($rows, $fields);

    expect($result[1])->toHaveKey('name');
    expect($result[1])->toHaveKey('active');
    expect($result[1])->not->toHaveKey('secret');
});

// ── import_class config ───────────────────────────────────────────────────────

it('download использует кастомный import_class из конфига', function () {
    $capturedRows = null;

    $customClass = new class([]) extends ListImport
    {
        public static array $lastRows = [];

        public function __construct(array $rows)
        {
            parent::__construct($rows);
            self::$lastRows = $rows;
        }
    };

    $className = get_class($customClass);
    config(['lists.import_class' => $className]);

    $fields = $this->component->getFields();
    $rows = ['data' => [['name' => 'Тест', 'active' => '1']]];

    $prepared = $this->service->prepareExportData($rows, $fields);

    $instance = new $className($prepared);

    expect($instance->collection()->first())->toContain('Тест');
})->todo('Requires Excel facade mock for full download() integration');

it('prepareExportData возвращает пустой массив данных при пустом rows[data]', function () {
    $fields = $this->component->getFields();
    $rows = ['data' => []];

    $result = $this->service->prepareExportData($rows, $fields);

    expect($result)->toHaveCount(1); // только заголовок
    expect($result[0])->not->toBeEmpty();
});
