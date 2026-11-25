<?php

declare(strict_types=1);

namespace Zak\Lists\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Auth;
use Throwable;
use Zak\Lists\Component;
use Zak\Lists\Contracts\ComponentLoaderContract;
use Zak\Lists\Export\ExportWriter;
use Zak\Lists\Export\StreamingCsvWriter;
use Zak\Lists\Export\StreamingXlsxWriter;
use Zak\Lists\Fields\Field;
use Zak\Lists\Models\ListExport;
use Zak\Lists\Services\ExportService;

/**
 * Background list export (xlsx / csv) with row-level progress reporting.
 *
 * Only the ListExport id is serialized: columns, format and filters are stored on the
 * record, and the component is re-created from its file (PHP closures are not serializable).
 */
class ExportListJob implements ShouldQueue
{
    use Queueable;

    /**
     * No retry on timeout — a timed-out export would just time out again.
     */
    public int $tries = 1;

    /**
     * Allow up to 1 hour for large exports.
     */
    public int $timeout = 3600;

    public function __construct(public readonly int $exportId)
    {
        $this->onQueue((string) config('lists.export_queue', 'exports'));

        $connection = config('lists.export_connection');

        if ($connection) {
            $this->onConnection((string) $connection);
        }
    }

    public function handle(ExportService $exportService): void
    {
        $export = ListExport::find($this->exportId);

        if (! $export || $export->status !== ListExport::STATUS_PENDING) {
            return;
        }

        $user = $this->resolveUser($export->user_id);

        if (! $user) {
            $this->markFailed($export, 'User not found.');

            return;
        }

        $export->update([
            'status' => ListExport::STATUS_PROCESSING,
            'started_at' => now(),
            'processed_rows' => 0,
        ]);

        $originalInput = request()->all();

        // Long-lived workers keep singletons between jobs: drop the cached components
        // so per-user options and filters of a previous job cannot leak into this one.
        app()->forgetInstance(ComponentLoaderContract::class);

        $previousUser = Auth::guard()->user();

        Auth::setUser($user);

        try {
            $this->run($export, $exportService);
        } catch (Throwable $e) {
            $this->markFailed($export, $e->getMessage());

            info('Zak.Lists.ExportListJob: error: '.$e->getMessage());

            if (isReportable($e)) {
                report($e);
            }

            // Do not re-throw: status is already persisted and error reported.
        } finally {
            request()->replace($originalInput);
            app()->forgetInstance(ComponentLoaderContract::class);

            $guard = Auth::guard();

            if ($previousUser !== null) {
                $guard->setUser($previousUser);
            } elseif (method_exists($guard, 'forgetUser')) {
                $guard->forgetUser();
            }
        }
    }

    /**
     * Called by the queue worker when the job is killed outside of handle()
     * (timeout via SIGTERM, MaxAttemptsExceededException, etc.).
     * Ensures the ListExport record never stays stuck in an active status.
     */
    public function failed(Throwable $exception): void
    {
        $export = ListExport::find($this->exportId);

        if ($export && $export->isActive()) {
            $this->markFailed($export, $exception->getMessage());
        }

        info('Zak.Lists.ExportListJob.failed: '.$exception->getMessage());
    }

    private function run(ListExport $export, ExportService $exportService): void
    {
        $loader = app(ComponentLoaderContract::class);
        $component = $loader->resolve($export->list, true);

        $query = $exportService->makeQuery($component, $exportService->extractFilters($export->request_data ?? []));

        $fields = $this->resolveExportFields($exportService, $component, $export->columns);

        if ($fields === []) {
            $this->markFailed($export, 'No columns selected for export.');

            return;
        }

        $disk = (string) ($export->disk ?: config('lists.export_disk', 'local'));
        $path = trim((string) config('lists.export_path', 'exports'), '/');
        // ASCII-safe stored name: the human-readable filename may contain Cyrillic or filter values.
        $storedPath = $path.'/'.$export->id.'-'.now()->format('Ymd-His').'.'.$export->format;

        $stored = $this->writerFor($export->format)->write(
            $component,
            $query,
            $fields,
            $export->list,
            $storedPath,
            $disk,
            max(1, (int) config('lists.export_chunk_size', 500)),
            function (int $processed) use ($export): bool {
                ListExport::whereKey($export->id)
                    ->where('status', ListExport::STATUS_PROCESSING)
                    ->update(['processed_rows' => $processed, 'updated_at' => now()]);

                return ListExport::whereKey($export->id)->value('status') === ListExport::STATUS_PROCESSING;
            },
        );

        if ($stored === null) {
            return;
        }

        $finalCount = ListExport::whereKey($export->id)->value('processed_rows');

        $export->forceFill([
            'status' => ListExport::STATUS_DONE,
            'filepath' => $stored,
            'disk' => $disk,
            'processed_rows' => max((int) $finalCount, $export->total_rows),
            'finished_at' => now(),
        ])->save();
    }

    /**
     * @param  array<int, string>|null  $selected
     * @return array<int, Field>
     */
    private function resolveExportFields(ExportService $exportService, Component $component, ?array $selected): array
    {
        $whitelist = $selected ?: $exportService->defaultColumns($component);

        return array_values(array_filter(
            $exportService->exportableFields($component),
            static fn (Field $field): bool => in_array($field->attribute, $whitelist, false)
        ));
    }

    private function writerFor(string $format): ExportWriter
    {
        return $format === ListExport::FORMAT_CSV
            ? app(StreamingCsvWriter::class)
            : app(StreamingXlsxWriter::class);
    }

    private function markFailed(ListExport $export, string $message): void
    {
        $export->forceFill([
            'status' => ListExport::STATUS_FAILED,
            'error_message' => $message,
            'finished_at' => now(),
        ])->save();
    }

    private function resolveUser(int $userId): mixed
    {
        $provider = config('auth.providers.'.config('auth.guards.web.provider', 'users').'.model');

        if (! $provider || ! class_exists($provider)) {
            return null;
        }

        return $provider::find($userId);
    }
}
