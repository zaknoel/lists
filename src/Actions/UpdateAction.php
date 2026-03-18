<?php

namespace Zak\Lists\Actions;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Redirect;
use Zak\Lists\Contracts\AuthorizationContract;
use Zak\Lists\Contracts\ComponentLoaderContract;
use Zak\Lists\Contracts\FieldServiceContract;
use Zak\Lists\Contracts\QueryContract;
use Zak\Lists\Fields\Field;

/**
 * Сохраняет изменения существующего элемента после валидации.
 */
class UpdateAction
{
    public function __construct(
        private readonly ComponentLoaderContract $loader,
        private readonly QueryContract $queryService,
        private readonly AuthorizationContract $authService,
        private readonly FieldServiceContract $fieldService,
    ) {}

    /**
     * @return View|RedirectResponse
     */
    public function handle(Request $request, string $list, int $itemId): mixed
    {
        $component = $this->loader->resolve($list);
        $component->checkCustomPath('customEditPage', $itemId);

        $query = $this->queryService->buildEditQuery($component);
        $item = $query->where('id', $itemId)->firstOrFail();

        $this->authService->ensureCanUpdate($component, $item);

        $fields = Arr::where($component->getFields(), fn (Field $f) => $f->show_on_update);
        $item = $this->fieldService->saveFields($item, $fields, $request, $component);

        if ((int) $request->get('frame', 0)) {
            $view = $component->customViews['success'] ?? 'lists::success';

            return view($view, ['item' => $item]);
        }

        return Redirect::to($component->getRoute('lists_detail', $list, $item))
            ->with('js_success', __('lists.messages.updated'));
    }
}
