<?php

use Illuminate\Support\Facades\Queue;
use Zak\Lists\Jobs\ExportListJob;
use Zak\Lists\Models\ListExport;
use Zak\Lists\Tests\Fixtures\Models\TestUser;

beforeEach(function () {
    $this->actor = TestUser::factory()->create();
    $this->actingAs($this->actor);
});

// ── Лимит строк: жёсткий максимум ────────────────────────────────────────────

it('превышение max_export_rows отклоняет экспорт с 422 и не ставит job', function () {
    Queue::fake();
    config(['lists.max_export_rows' => 2]);

    TestUser::factory()->count(3)->create();

    $this->postJson(route('lists_export_start', 'test-users'), [
        'format' => 'xlsx',
        'columns' => ['name'],
    ])->assertStatus(422)->assertJsonStructure(['message']);

    Queue::assertNothingPushed();
    expect(ListExport::count())->toBe(0);
});

it('старая ссылка ?excel=Y превышающая лимит возвращает js_error', function () {
    Queue::fake();
    config(['lists.max_export_rows' => 2]);

    TestUser::factory()->count(3)->create();

    $this->get(route('lists', 'test-users').'?excel=Y')
        ->assertRedirect()
        ->assertSessionHas('js_error');

    Queue::assertNothingPushed();
});

it('старая ссылка ?excel=Y ставит xlsx-экспорт в очередь', function () {
    Queue::fake();
    config(['lists.max_export_rows' => 10000]);

    TestUser::factory()->count(3)->create();

    $this->get(route('lists', 'test-users').'?excel=Y')
        ->assertRedirect()
        ->assertSessionHas('js_success');

    Queue::assertPushed(ExportListJob::class);
    expect(ListExport::first()->format)->toBe('xlsx');
});
