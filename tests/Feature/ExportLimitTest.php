<?php

use Illuminate\Support\Facades\Queue;
use Zak\Lists\Jobs\ExportListJob;
use Zak\Lists\Tests\Fixtures\Models\TestUser;

beforeEach(function () {
    $this->actor = TestUser::factory()->create();
    $this->actingAs($this->actor);
});

// ── Лимит строк: жёсткий максимум ────────────────────────────────────────────

it('превышение max_export_rows возвращает js_error без файла', function () {
    config(['lists.max_export_rows' => 2]);

    TestUser::factory()->count(3)->create();

    $this->get(route('lists', 'test-users').'?excel=Y')
        ->assertRedirect()
        ->assertSessionHas('js_error');
});

it('при количестве строк ниже лимита экспорт выполняется синхронно', function () {
    config([
        'lists.max_export_rows' => 100,
        'lists.export_async_threshold' => 0,
    ]);

    TestUser::factory()->count(2)->create();

    $response = $this->get(route('lists', 'test-users').'?excel=Y');

    $response->assertSessionMissing('js_error');
});

// ── Async порог: dispatch в очередь ──────────────────────────────────────────

it('превышение export_async_threshold диспетчит ExportListJob', function () {
    Queue::fake();

    config([
        'lists.max_export_rows' => 10000,
        'lists.export_async_threshold' => 1,
    ]);

    TestUser::factory()->count(3)->create();

    $this->get(route('lists', 'test-users').'?excel=Y')
        ->assertRedirect()
        ->assertSessionHas('js_info');

    Queue::assertPushed(ExportListJob::class);
});

it('при export_async_threshold=0 очередь не используется', function () {
    Queue::fake();

    config([
        'lists.max_export_rows' => 10000,
        'lists.export_async_threshold' => 0,
    ]);

    TestUser::factory()->count(3)->create();

    $this->get(route('lists', 'test-users').'?excel=Y');

    Queue::assertNothingPushed();
});

it('ExportListJob содержит правильные данные', function () {
    Queue::fake();

    config([
        'lists.max_export_rows' => 10000,
        'lists.export_async_threshold' => 1,
    ]);

    TestUser::factory()->count(3)->create();

    $this->get(route('lists', 'test-users').'?excel=Y');

    Queue::assertPushed(ExportListJob::class, function (ExportListJob $job) {
        return $job->list === 'test-users'
            && $job->userId === $this->actor->id;
    });
});
