<?php

namespace Zak\Lists\Actions;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Throwable;
use Zak\Lists\BulkAction;
use Zak\Lists\Contracts\AuthorizationContract;
use Zak\Lists\Contracts\ComponentLoaderContract;

/**
 * Обрабатывает групповые действия над множеством элементов.
 */
class BulkActionRunner
{
    public function __construct(
        private readonly ComponentLoaderContract $loader,
        private readonly AuthorizationContract $authService,
    ) {}

    public function handle(Request $request, string $list): RedirectResponse
    {
        $component = $this->loader->resolve($list);
        $this->authService->ensureCanViewAny($component);

        $data = $request->validate([
            'action' => ['required', 'string'],
            'items' => ['required', 'array'],
            'items.*' => ['integer'],
        ]);

        /** @var BulkAction|null $action */
        $action = Arr::first(
            $component->bulkActions,
            fn (BulkAction $a) => $a->key === $data['action']
        );

        if (! $action) {
            return back()->with('js_error', __('lists.errors.action_not_found'));
        }

        try {
            $items = $component->getQuery()->whereIn('id', $data['items'])->get();
            call_user_func($action->callback, $items, $component, $request);
        } catch (Throwable $e) {
            if (isReportable($e)) {
                report('Zak.Lists.BulkAction: '.$e->getMessage()."\n".$e->getFile().':'.$e->getLine());
            }

            return back()->with('js_error', __('lists.errors.action_failed').': '.$e->getMessage());
        }

        return back()->with('js_success', $action->getSuccessMessage());
    }
}
