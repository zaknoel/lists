<?php

declare(strict_types=1);

namespace Zak\Lists\Actions;

use Illuminate\Http\JsonResponse;
use Zak\Lists\Component;
use Zak\Lists\Contracts\AuthorizationContract;
use Zak\Lists\Contracts\ComponentLoaderContract;
use Zak\Lists\Jobs\ExportListJob;
use Zak\Lists\Models\ListExport;
use Zak\Lists\Requests\StartExportRequest;
use Zak\Lists\Services\ExportService;

/**
 * Creates a ListExport record and queues the background job that builds the file.
 */
class StartExportAction
{
    private int $lastCount = 0;

    public function __construct(
        private readonly ComponentLoaderContract $loader,
        private readonly AuthorizationContract $authService,
        private readonly ExportService $exportService,
    ) {}

    public function handle(StartExportRequest $request, string $list): JsonResponse
    {
        $component = $this->loader->resolve($list, true);
        $this->authService->ensureCanViewAny($component);

        $format = (string) $request->validated('format');
        $columns = array_values((array) $request->validated('columns'));
        $filters = $request->filters();

        $export = $this->start($component, $list, $format, $columns, $filters);

        if ($export === null) {
            return response()->json([
                'message' => __('lists.errors.export_limit_exceeded', [
                    'count' => number_format($this->lastCount),
                    'max' => number_format((int) config('lists.max_export_rows', 50000)),
                ]),
            ], 422);
        }

        return response()->json(ExportStatusAction::payload($export), 201);
    }

    /**
     * Creates the export record and dispatches the job.
     *
     * Returns null when the result set exceeds `lists.max_export_rows`.
     *
     * @param  array<int, string>  $columns
     * @param  array<string, mixed>  $filters
     */
    public function start(Component $component, string $list, string $format, array $columns, array $filters): ?ListExport
    {
        $original = request()->all();

        try {
            $this->lastCount = $this->exportService->countRows($this->exportService->makeQuery($component, $filters));
        } finally {
            request()->replace($original);
        }

        if ($this->exportService->exceedsExportLimitByCount($this->lastCount)) {
            return null;
        }

        $export = ListExport::create([
            'user_id' => (int) auth()->id(),
            'list' => $list,
            'filename' => $this->exportService->buildLabel($component, $filters),
            'format' => $format,
            'columns' => $columns,
            'request_data' => $filters,
            'total_rows' => $this->lastCount,
            'processed_rows' => 0,
            'status' => ListExport::STATUS_PENDING,
            'disk' => (string) config('lists.export_disk', 'local'),
        ]);

        ExportListJob::dispatch($export->id);

        return $export;
    }
}
