<?php

namespace Zak\Lists\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Zak\Lists\Contracts\AuthorizationContract;
use Zak\Lists\Contracts\ComponentLoaderContract;

/**
 * Удаляет элемент после проверки прав доступа.
 */
class DestroyAction
{
    public function __construct(
        private readonly ComponentLoaderContract $loader,
        private readonly AuthorizationContract $authService,
    ) {}

    public function handle(Request $request, string $list, int $itemId): RedirectResponse
    {
        $component = $this->loader->resolve($list);
        $component->checkCustomPath('customDeletePage', $itemId);

        /** @var Model $item */
        $item = $component->getQuery()->where('id', $itemId)->firstOrFail();

        $this->authService->ensureCanDelete($component, $item);

        $component->eventOnBeforeDelete($item);
        $item->deleteOrFail();
        $component->eventOnAfterDelete($item);

        return redirect()
            ->route('lists', $list)
            ->with('js_success', __('lists.messages.deleted'));
    }
}
