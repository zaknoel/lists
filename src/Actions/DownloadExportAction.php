<?php

declare(strict_types=1);

namespace Zak\Lists\Actions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Zak\Lists\Models\ListExport;

/**
 * Streams a completed async export file to the browser.
 * Marks the record as seen so the Livewire banner stops showing it.
 */
class DownloadExportAction
{
    public function handle(Request $request, int $exportId): Response
    {
        $export = ListExport::findOrFail($exportId);

        // user_id is cast to int in the model, but guard with explicit cast on both sides.
        abort_unless((int) auth()->id() === (int) $export->user_id, 403);
        abort_unless($export->status === ListExport::STATUS_DONE && $export->filepath, 404);

        $disk = Storage::disk($export->disk);

        abort_unless($disk->exists($export->filepath), 404);

        // Mark as seen before streaming so a page refresh won't re-show the banner.
        $export->update(['seen_at' => now()]);

        $filename = $export->filename.'.xlsx';

        return $disk->download($export->filepath, $filename);
    }
}
