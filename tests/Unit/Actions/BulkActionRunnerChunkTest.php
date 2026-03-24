<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Zak\Lists\Actions\BulkActionRunner;
use Zak\Lists\BulkAction;
use Zak\Lists\Component;
use Zak\Lists\Contracts\ComponentLoaderContract;
use Zak\Lists\Jobs\BulkActionJob;
use Zak\Lists\Tests\Fixtures\Models\TestUser;

beforeEach(function () {
    $this->actor = TestUser::factory()->create();
    Auth::login($this->actor);
});

// ── Chunked loading ───────────────────────────────────────────────────────────

it('загружает элементы в chunks без единого большого whereIn', function () {
    config(['lists.bulk_chunk_size' => 2]);

    $items = TestUser::factory()->count(5)->create(['active' => true]);
    $ids = $items->pluck('id')->toArray();

    DB::enableQueryLog();

    $this->actingAs($this->actor)
        ->post(route('lists_action', 'bulk-test'), [
            'action' => 'deactivate',
            'items' => $ids,
        ]);

    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    // С chunk_size=2 и 5 элементами должно быть 3 SELECT запроса (2+2+1)
    $selectQueries = array_filter($queries, fn ($q) => stripos($q['query'], 'select') === 0);

    expect(count($selectQueries))->toBeGreaterThanOrEqual(3);
    expect(TestUser::whereIn('id', $ids)->where('active', false)->count())->toBe(5);
});

it('загружает все элементы даже при больших списках', function () {
    config(['lists.bulk_chunk_size' => 3]);

    $items = TestUser::factory()->count(7)->create(['active' => true]);
    $ids = $items->pluck('id')->toArray();

    $this->actingAs($this->actor)
        ->post(route('lists_action', 'bulk-test'), [
            'action' => 'deactivate',
            'items' => $ids,
        ]);

    expect(TestUser::whereIn('id', $ids)->where('active', false)->count())->toBe(7);
});

// ── Async dispatch ────────────────────────────────────────────────────────────

it('async bulk action диспетчит BulkActionJob вместо синхронного выполнения', function () {
    Queue::fake();

    $component = new Component(
        model: TestUser::class,
        label: 'Test',
        singleLabel: 'test',
        fields: [],
        canViewAny: fn () => true,
        bulkActions: [
            BulkAction::make('Async Action', 'async-test', new class
            {
                public function __invoke($items, $component): void {}
            })->async(),
        ],
    );

    app()->instance(ComponentLoaderContract::class, new class($component) implements ComponentLoaderContract
    {
        public function __construct(private readonly Component $component) {}

        public function resolve(string $list, bool $applySortOrder = false): Component
        {
            return $this->component;
        }
    });

    $item = TestUser::factory()->create();

    app(BulkActionRunner::class)->handle(
        Request::create('/lists/bulk-test', 'POST', [
            'action' => 'async-test',
            'items' => [$item->id],
        ]),
        'bulk-test'
    );

    Queue::assertPushed(BulkActionJob::class, function (BulkActionJob $job) use ($item) {
        return $job->list === 'bulk-test'
            && $job->actionKey === 'async-test'
            && in_array($item->id, $job->ids, true);
    });
});
