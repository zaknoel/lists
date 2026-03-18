<?php

namespace Zak\Lists\Actions;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Zak\Lists\Contracts\AuthorizationContract;
use Zak\Lists\Contracts\ComponentLoaderContract;
use Zak\Lists\Contracts\FieldServiceContract;
use Zak\Lists\Fields\Field;

/**
 * Сохраняет новый элемент после валидации.
 */
class StoreAction
{
    public function __construct(
        private readonly ComponentLoaderContract $loader,
        private readonly AuthorizationContract $authService,
        private readonly FieldServiceContract $fieldService,
    ) {}

    /**
     * @return View|RedirectResponse
     */
    public function handle(Request $request, string $list): mixed
    {
        $component = $this->loader->resolve($list);

        $this->authService->ensureCanCreate($component);
        $component->checkCustomPath('customAddPage');

        $fields = Arr::where($component->getFields(), fn (Field $f) => $f->show_on_add);
        $item = $this->fieldService->saveFields(null, $fields, $request, $component);

        if ((int) $request->get('frame', 0)) {
            $view = $component->customViews['success'] ?? 'lists::success';

            return view($view, ['item' => $item]);
        }

        return redirect()
            ->route('lists', $list)
            ->with('js_success', __('lists.messages.created'));
    }
}
