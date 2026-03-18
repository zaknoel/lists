<?php

namespace Zak\Lists\Actions;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Zak\Lists\Component;
use Zak\Lists\Contracts\AuthorizationContract;
use Zak\Lists\Contracts\ComponentLoaderContract;
use Zak\Lists\Fields\Field;

/**
 * Отображает форму создания нового элемента.
 */
class CreateAction
{
    public function __construct(
        private readonly ComponentLoaderContract $loader,
        private readonly AuthorizationContract $authService,
    ) {}

    /**
     * @return View
     */
    public function handle(Request $request, string $list): mixed
    {
        $component = $this->loader->resolve($list);

        $this->authService->ensureCanCreate($component);
        $component->checkCustomPath('customAddPage');

        $item = new ($component->getModel());

        // Поддержка копирования элемента
        if ($request->filled('copy_from')) {
            $copy = $component->getQuery()->find((int) $request->get('copy_from'));

            if ($copy) {
                $item = clone $copy;
                unset($item->id);
                $item->exists = false;
            }
        }

        $fields = $this->prepareFormFields($component, $item, 'show_on_add');

        $view = $component->customViews['form'] ?? 'lists::form';

        return view($view, [
            'component' => $component,
            'item' => $item,
            'scripts' => $component->scripts(),
            'fields' => $fields,
            'list' => $list,
            'title' => $component->getCustomLabel('add') ?? __('lists.actions.create').' '.$component->getSingleLabel(),
            'pageTitle' => $component->getCustomLabel('add') ?? __('lists.actions.new').' '.$component->getSingleLabel(),
            'frame' => (int) $request->get('frame', 0),
        ]);
    }

    /**
     * Подготавливает поля для формы.
     *
     * @return array<int, Field>
     */
    private function prepareFormFields(Component $component, $item, string $visibility): array
    {
        $fields = Arr::where($component->getFields(), fn (Field $f) => $f->{$visibility});

        return Arr::map($fields, function (Field $field) use ($item) {
            $field->item($item)->showEdit();

            return $field;
        });
    }
}
