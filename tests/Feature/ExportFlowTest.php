<?php

use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Zak\Lists\Jobs\ExportListJob;
use Zak\Lists\Models\ListExport;
use Zak\Lists\Services\ExportService;
use Zak\Lists\Tests\Fixtures\Models\TestUser;

beforeEach(function () {
    Storage::fake('local');
    config(['lists.export_disk' => 'local', 'lists.export_path' => 'exports', 'lists.max_export_rows' => 10000]);

    $this->actor = TestUser::factory()->create(['name' => 'Alice Actor']);
    $this->actingAs($this->actor);
});

function startExport(array $payload = []): TestResponse
{
    return test()->postJson(route('lists_export_start', 'test-users'), array_merge([
        'format' => 'csv',
        'columns' => ['name', 'email'],
    ], $payload));
}

function runExportJob(int $exportId): void
{
    (new ExportListJob($exportId))->handle(app(ExportService::class));
}

// ── Запуск ───────────────────────────────────────────────────────────────────

it('запуск создаёт запись, считает строки и ставит job в очередь exports', function () {
    Queue::fake();
    TestUser::factory()->count(2)->create();

    startExport()
        ->assertCreated()
        ->assertJsonPath('status', 'pending')
        ->assertJsonPath('total_rows', 3)
        ->assertJsonPath('download_url', null);

    $export = ListExport::firstOrFail();

    expect($export->user_id)->toBe($this->actor->id)
        ->and($export->format)->toBe('csv')
        ->and($export->columns)->toBe(['name', 'email']);

    Queue::assertPushed(ExportListJob::class, fn (ExportListJob $job) => $job->exportId === $export->id && $job->queue === 'exports');
});

it('валидирует формат и колонки', function () {
    Queue::fake();

    startExport(['format' => 'pdf'])->assertJsonValidationErrors('format');
    startExport(['columns' => []])->assertJsonValidationErrors('columns');
    startExport(['columns' => ['password']])->assertJsonValidationErrors('columns.0');

    Queue::assertNothingPushed();
});

it('сохраняет фильтры из query string страницы', function () {
    Queue::fake();

    startExport(['query' => 'name=like⚬Alice&draw=1&order[0][column]=2'])->assertCreated();

    $data = ListExport::firstOrFail()->request_data;

    expect($data)->toHaveKey('name')->and($data)->not->toHaveKey('draw')->and($data)->not->toHaveKey('order');
});

// ── Job ──────────────────────────────────────────────────────────────────────

it('job формирует csv только с выбранными колонками и отмечает прогресс', function () {
    config(['lists.export_chunk_size' => 1]);
    TestUser::factory()->create(['name' => 'Bob Builder', 'email' => 'bob@example.test']);

    Queue::fake();
    $id = startExport(['columns' => ['name']])->json('id');
    runExportJob($id);

    $export = ListExport::findOrFail($id);

    expect($export->status)->toBe(ListExport::STATUS_DONE)
        ->and($export->processed_rows)->toBe(2)
        ->and($export->progress_percent)->toBe(100)
        ->and($export->filepath)->toEndWith('.csv');

    Storage::disk('local')->assertExists($export->filepath);

    $content = Storage::disk('local')->get($export->filepath);

    expect($content)->toContain('Bob Builder')->toContain('Alice Actor')->not->toContain('bob@example.test');
});

it('job формирует валидный xlsx', function () {
    Queue::fake();
    $id = startExport(['format' => 'xlsx'])->json('id');
    runExportJob($id);

    $export = ListExport::findOrFail($id);

    expect($export->status)->toBe(ListExport::STATUS_DONE);

    $zip = new ZipArchive;
    $tmp = tempnam(sys_get_temp_dir(), 'x');
    file_put_contents($tmp, Storage::disk('local')->get($export->filepath));

    expect($zip->open($tmp))->toBeTrue()
        ->and($zip->locateName('xl/workbook.xml'))->not->toBeFalse();

    $zip->close();
    @unlink($tmp);
});

it('job применяет фильтры списка', function () {
    TestUser::factory()->create(['name' => 'Zed Other', 'email' => 'zed@example.test']);

    Queue::fake();
    $id = startExport(['query' => 'name=like⚬Zed'])->json('id');
    runExportJob($id);

    $content = Storage::disk('local')->get(ListExport::findOrFail($id)->filepath);

    expect($content)->toContain('Zed Other')->not->toContain('Alice Actor');
});

it('отменённый экспорт не выполняется', function () {
    Queue::fake();
    $id = startExport()->json('id');

    $this->postJson(route('lists_export_cancel', $id))->assertOk()->assertJsonPath('status', 'cancelled');

    runExportJob($id);

    expect(ListExport::findOrFail($id)->status)->toBe(ListExport::STATUS_CANCELLED);
});

it('failed() переводит зависшую запись в failed', function () {
    Queue::fake();
    $id = startExport()->json('id');

    (new ExportListJob($id))->failed(new RuntimeException('boom'));

    $export = ListExport::findOrFail($id);

    expect($export->status)->toBe(ListExport::STATUS_FAILED)->and($export->error_message)->toBe('boom');
});

// ── Статус и скачивание ──────────────────────────────────────────────────────

it('статус отдаёт прогресс и ссылку на скачивание после завершения', function () {
    Queue::fake();
    $id = startExport()->json('id');

    $this->getJson(route('lists_export_status', $id))
        ->assertOk()
        ->assertJsonPath('status', 'pending')
        ->assertJsonPath('finished', false);

    runExportJob($id);

    $this->getJson(route('lists_export_status', $id))
        ->assertJsonPath('status', 'done')
        ->assertJsonPath('percent', 100)
        ->assertJsonPath('finished', true)
        ->assertJsonPath('download_url', route('lists_export_download', $id));
});

it('скачивание отдаёт файл с правильным расширением', function () {
    Queue::fake();
    $id = startExport()->json('id');
    runExportJob($id);

    $response = $this->get(route('lists_export_download', $id));

    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('.csv');
});

it('чужой экспорт недоступен', function () {
    Queue::fake();
    $id = startExport()->json('id');

    $this->actingAs(TestUser::factory()->create());

    $this->getJson(route('lists_export_status', $id))->assertForbidden();
    $this->postJson(route('lists_export_cancel', $id))->assertForbidden();
    $this->get(route('lists_export_download', $id))->assertForbidden();
});

it('prune помечает зависшие экспорты как failed', function () {
    Queue::fake();
    $id = startExport()->json('id');

    ListExport::whereKey($id)->update(['updated_at' => now()->subHours(5)]);

    $this->artisan('lists:prune-exports')->assertSuccessful();

    expect(ListExport::findOrFail($id)->status)->toBe(ListExport::STATUS_FAILED);
});
