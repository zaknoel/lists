<?php

namespace Zak\Lists\Actions;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Throwable;
use Zak\Lists\BulkAction;
use Zak\Lists\Contracts\AuthorizationContract;
use Zak\Lists\Contracts\ComponentLoaderContract;
use Zak\Lists\Jobs\BulkActionJob;

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

        // Данные уже провалидированы FormRequest (ListBulkActionRequest).
        // Поддерживаем fallback для прямых вызовов без FormRequest.
        $data = $request instanceof FormRequest
            ? $request->validated()
            : $request->validate([
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

        // Async path: замыкания не сериализуются, поэтому допустимы только invokable-классы.
        if ($action->async) {
            if (! is_object($action->callback) || ! method_exists($action->callback, '__invoke')) {
                info('Zak.Lists.BulkActionRunner: async=true, но callback "'.$action->key.'" не является invokable-классом.');

                return back()->with('js_error', __('lists.errors.action_failed').': async callback must be an invokable class.');
            }

            BulkActionJob::dispatch($list, $action->key, array_map('intval', $data['items']), (int) auth()->id());

            return back()->with('js_success', $action->getSuccessMessage());
        }

        try {
            $items = $this->loadItemsInChunks($component, $data['items']);
            call_user_func($action->callback, $items, $component, $request);
        } catch (Throwable $e) {
            if (isReportable($e)) {
                report('Zak.Lists.BulkAction: '.$e->getMessage()."\n".$e->getFile().':'.$e->getLine());
            }

            return back()->with('js_error', __('lists.errors.action_failed').': '.$e->getMessage());
        }

        return back()->with('js_success', $action->getSuccessMessage());
    }

    /**
     * Загружает выбранные элементы пакетами, чтобы не строить один большой whereIn для больших bulk-операций.
     * При этом callback по-прежнему получает обычную Eloquent Collection.
     *
     * @param  array<int, mixed>  $rawIds
     */
    private function loadItemsInChunks($component, array $rawIds): EloquentCollection
    {
        $ids = array_values(array_unique(array_map('intval', $rawIds)));
        $ids = array_values(array_filter($ids, static fn (int $id) => $id > 0));

        $items = new EloquentCollection;
        $chunkSize = max(1, (int) config('lists.bulk_chunk_size', 500));

        foreach (array_chunk($ids, $chunkSize) as $chunk) {
            $items = $items->merge(
                $component->getQuery()->whereIn('id', $chunk)->get()
            );
        }

        return $items;
    }
}
