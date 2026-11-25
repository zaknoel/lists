<?php

declare(strict_types=1);

namespace Zak\Lists\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;
use Zak\Lists\Models\ListExport;

/**
 * Livewire polling banner that shows the current user's async export statuses.
 *
 * Polling is driven by wire:poll in the view (fast while an export is active, slow otherwise) — no PHP attribute needed,
 * works in Livewire v3 and v4 without importing version-specific attribute classes.
 */
class ExportStatusBanner extends Component
{
    public function render(): View
    {
        $exports = $this->loadExports();

        return view('lists::livewire.export-status-banner', [
            'exports' => $exports,
            'hasActive' => $exports->contains(static fn (ListExport $export): bool => $export->isActive()),
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
