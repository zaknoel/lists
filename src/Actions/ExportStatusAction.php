<?php

declare(strict_types=1);

namespace Zak\Lists\Actions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Zak\Lists\Models\ListExport;

/**
 * Returns the live state of an export for the polling UI and allows cancelling it.
 */
class ExportStatusAction
{
    public function show(Request $request, int $exportId): JsonResponse
    {
        return response()->json(self::payload($this->findOwned($exportId)));
    }

    public function cancel(Request $request, int $exportId): JsonResponse
    {
        $export = $this->findOwned($exportId);

        if ($export->isActive()) {
            $export->forceFill([
                'status' => ListExport::STATUS_CANCELLED,
                'finished_at' => now(),
            ])->save();
        }

        return response()->json(self::payload($export));
    }

    /**
     * @return array<string, mixed>
     */
    public static function payload(ListExport $export): array
    {
        return [
            'id' => $export->id,
            'status' => $export->status,
            'format' => $export->format,
            'filename' => $export->filename,
            'total_rows' => $export->total_rows,
            'processed_rows' => $export->processed_rows,
            'percent' => $export->progress_percent,
            'finished' => $export->isFinished(),
            'error' => $export->status === ListExport::STATUS_FAILED ? $export->error_message : null,
            'status_url' => route('lists_export_status', $export->id),
            'cancel_url' => route('lists_export_cancel', $export->id),
            'download_url' => $export->status === ListExport::STATUS_DONE
                ? route('lists_export_download', $export->id)
                : null,
        ];
    }

    private function findOwned(int $exportId): ListExport
    {
        $export = ListExport::findOrFail($exportId);

        abort_unless((int) auth()->id() === (int) $export->user_id, 403);

        return $export;
    }
}
