<?php

declare(strict_types=1);

namespace Zak\Lists\Actions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Zak\Lists\Models\ListExport;

/**
 * Streams a completed async export file to the browser.
 * Marks the record as seen after a successful response is sent.
 */
class DownloadExportAction
{
    public function handle(Request $request, int $exportId): StreamedResponse
    {
        $export = ListExport::findOrFail($exportId);

        abort_unless((int) auth()->id() === $export->user_id, 403);
        abort_unless($export->status === ListExport::STATUS_DONE && $export->filepath, 404);

        $disk = Storage::disk($export->disk);

        abort_unless($disk->exists($export->filepath), 404);

        // Mark as seen so the Livewire banner stops showing it.
        $export->update(['seen_at' => now()]);

        $filename = $export->filename.'.xlsx';

        return $disk->download($export->filepath, $filename);
    }
}

