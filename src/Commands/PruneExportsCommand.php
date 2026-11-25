<?php

declare(strict_types=1);

namespace Zak\Lists\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Zak\Lists\Models\ListExport;

/**
 * Deletes old ListExport records and their associated files from storage.
 *
 * Usage:
 *   php artisan lists:prune-exports            # uses lists.export_prune_days config
 *   php artisan lists:prune-exports --days=14  # override retention period
 */
class PruneExportsCommand extends Command
{
    protected $signature = 'lists:prune-exports
                            {--days= : Number of days to keep exports (overrides config)}';

    protected $description = 'Delete old async export records and their stored files';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? config('lists.export_prune_days', 7));

        if ($days <= 0) {
            $this->error('--days must be a positive integer.');

            return self::FAILURE;
        }

        $this->failStaleExports();

        $cutoff = now()->subDays($days);

        $exports = ListExport::where('created_at', '<', $cutoff)->get();

        if ($exports->isEmpty()) {
            $this->info('No exports older than '.$days.' days found.');

            return self::SUCCESS;
        }

        $deleted = 0;
        $filesRemoved = 0;

        foreach ($exports as $export) {
            if ($export->filepath) {
                $disk = Storage::disk($export->disk ?: config('lists.export_disk', 'local'));

                if ($disk->exists($export->filepath)) {
                    $disk->delete($export->filepath);
                    $filesRemoved++;
                }
            }

            $export->delete();
            $deleted++;
        }

        $this->info("Pruned {$deleted} export records, removed {$filesRemoved} files (older than {$days} days).");

        return self::SUCCESS;
    }

    /**
     * Marks exports that have been pending/processing for too long as failed (worker died, queue flushed, etc.).
     */
    private function failStaleExports(): void
    {
        $hours = (int) config('lists.export_stale_hours', 3);

        if ($hours <= 0) {
            return;
        }

        $stale = ListExport::query()
            ->whereIn('status', ListExport::ACTIVE_STATUSES)
            ->where('updated_at', '<', now()->subHours($hours))
            ->update([
                'status' => ListExport::STATUS_FAILED,
                'error_message' => 'Export timed out or the worker stopped unexpectedly.',
                'finished_at' => now(),
            ]);

        if ($stale > 0) {
            $this->warn("Marked {$stale} stalled exports as failed.");
        }
    }
}
