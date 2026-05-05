<?php

declare(strict_types=1);

namespace Zak\Lists\Livewire;

use Illuminate\Support\Collection;
use Livewire\Component;
use Zak\Lists\Models\ListExport;

/**
 * Livewire polling banner that shows the current user's async export statuses.
 *
 * Polling is driven by wire:poll.5000ms in the view — no PHP attribute needed,
 * works in Livewire v3 and v4 without importing version-specific attribute classes.
 */
class ExportStatusBanner extends Component
{
    public function render(): \Illuminate\Contracts\View\View
    {
        $exports = $this->loadExports();

        return view('lists::livewire.export-status-banner', [
            'exports' => $exports,
        ]);
    }

    /**
     * Dismiss a single export record (mark as seen without downloading).
     */
    public function dismiss(int $exportId): void
    {
        $export = ListExport::find($exportId);

        if ($export && (int) auth()->id() === $export->user_id) {
            $export->update(['seen_at' => now()]);
        }
    }

    /**
     * @return Collection<int, ListExport>
     */
    private function loadExports(): Collection
    {
        if (! auth()->check()) {
            return collect();
        }

        return ListExport::visibleForUser(auth()->id())->get();
    }
}

