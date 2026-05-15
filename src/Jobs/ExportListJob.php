<?php

declare(strict_types=1);

namespace Zak\Lists\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Auth;
use Throwable;
use Zak\Lists\Contracts\ComponentLoaderContract;
use Zak\Lists\Export\StreamingXlsxWriter;
use Zak\Lists\Fields\Field;
use Zak\Lists\Models\ListExport;
use Zak\Lists\Services\QueryService;

/**
 * Фоновый экспорт списка в Excel.
 *
 * Компонент пересоздаётся из файла внутри job, поскольку PHP-замыкания
 * не подлежат сериализации. В очередь передаются только примитивные данные.
 *
 * Запись ListExport создаётся до dispatch() в IndexAction и передаётся
 * через $exportId — это позволяет пользователю сразу видеть статус «pending».
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

    /** @var array<string, mixed> */
    public readonly array $requestData;

    /**
     * @param  string  $list  Component file name (without extension)
     * @param  array<string, mixed>  $requestData  Request data for filter re-application
     * @param  int  $userId  Authenticated user ID
     * @param  string  $filename  Base Excel filename (without extension)
     * @param  int  $exportId  ID of the pre-created ListExport record
     */
    public function __construct(
        public readonly string $list,
        array $requestData,
        public readonly int $userId,
        public readonly string $filename,
        public readonly int $exportId,
    ) {
        // Strip DataTables-specific HTTP params irrelevant to exports.
        $exportKeys = ['excel', 'order', 'length', 'start', 'draw', 'search', 'columns', '_token'];
        $this->requestData = array_diff_key($requestData, array_flip($exportKeys));
    }

    public function handle(
        ComponentLoaderContract $loader,
        QueryService $queryService,
        StreamingXlsxWriter $writer,
    ): void {
        $export = ListExport::find($this->exportId);

        if (! $export) {
            info('Zak.Lists.ExportListJob: ListExport #'.$this->exportId.' not found, skipping.');

            return;
        }

        $user = $this->resolveUser();

        if (! $user) {
            info('Zak.Lists.ExportListJob: user #'.$this->userId.' not found, skipping.');
            $export->update(['status' => ListExport::STATUS_FAILED, 'error_message' => 'User not found.']);

            return;
        }

        Auth::login($user);

        try {
            $component = $loader->resolve($this->list, true);

            // All Field::generateFilter() implementations read filters via the global request()
            // helper (app('request')), not from a Request object passed as a parameter.
            // We must replace the container's request input so filters are applied correctly.
            // Null/empty values are stripped to avoid applying empty filter conditions.
            $filterData = array_filter(
                $this->requestData,
                static fn (mixed $v): bool => $v !== null && $v !== '',
            );
            request()->replace($filterData);

            $query = $queryService->buildIndexQuery($component, request());

            $curSort = $component->options->value['curSort'] ?? ['id', 'desc'];
            $visibleColumns = $component->options->value['columns'] ?? [];

            /** @var array<int, Field> $fields */
            $fields = array_values(array_filter(
                $component->getFields(),
                fn (Field $field) => $field->show_in_index
                    && ! $field->hide_on_export
                    && (empty($visibleColumns) || in_array($field->attribute, $visibleColumns, false))
            ));

            foreach ($fields as $field) {
                $field->generateFilter($query);
            }
            //info('Zak.Lists.ExportListJob: query built, starting export: '.$query->toRawSql());
            $query->orderBy($curSort[0], $curSort[1]);

            $disk = config('lists.export_disk', 'local');
            $path = trim((string) config('lists.export_path', 'exports'), '/');
            // Use export ID for the stored path — keeps it ASCII-safe regardless of the
            // human-readable filename which may contain Cyrillic or filter values.
            $storedPath = $path.'/'.$this->exportId.'-'.now()->format('Ymd-His').'.xlsx';

            $writer->write(
                $component,
                $query,
                $fields,
                $this->list,
                $storedPath,
                $disk,
                (int) config('lists.export_chunk_size', 500),
            );

            $export->update([
                'status' => ListExport::STATUS_DONE,
                'filepath' => $storedPath,
                'disk' => $disk,
            ]);

            //info('Zak.Lists.ExportListJob: file saved: '.$storedPath);
        } catch (Throwable $e) {
            $export->update([
                'status' => ListExport::STATUS_FAILED,
                'error_message' => $e->getMessage(),
            ]);

            info('Zak.Lists.ExportListJob: error: '.$e->getMessage());

            if (isReportable($e)) {
                report('Zak.Lists.ExportListJob: '.$e->getMessage());
            }

            // Do not re-throw: status is already persisted and error reported.
            // Re-throwing with $tries=1 only produces a noisy MaxAttemptsExceededException.
        }
    }

    /**
     * Called by the queue worker when the job is killed outside of handle()
     * (timeout via SIGTERM, OOM, MaxAttemptsExceededException, etc.).
     * Ensures the ListExport record never stays stuck in 'pending'.
     */
    public function failed(Throwable $exception): void
    {
        $export = ListExport::find($this->exportId);

        if (! $export) {
            return;
        }

        // Only update if the handle() catch block did not already set a terminal status.
        if ($export->status === ListExport::STATUS_PENDING) {
            $export->update([
                'status' => ListExport::STATUS_FAILED,
                'error_message' => $exception->getMessage(),
            ]);
        }

        info('Zak.Lists.ExportListJob.failed: '.$exception->getMessage());
    }

    /**
     * Finds the user by ID using the configured auth provider model.
     */
    private function resolveUser(): mixed
    {
        $provider = config('auth.providers.'.config('auth.guards.web.provider', 'users').'.model');

        if (! $provider || ! class_exists($provider)) {
            return null;
        }

        return $provider::find($this->userId);
    }
}
