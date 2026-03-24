<?php

namespace Zak\Lists\Actions;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Zak\Lists\Component;
use Zak\Lists\Contracts\AuthorizationContract;
use Zak\Lists\Contracts\ComponentLoaderContract;
use Zak\Lists\Contracts\QueryContract;
use Zak\Lists\Fields\Field;

/**
 * Отображает форму редактирования существующего элемента.
 */
class EditAction
{
    public function __construct(
        private readonly ComponentLoaderContract $loader,
        private readonly QueryContract $queryService,
        private readonly AuthorizationContract $authService,
    ) {}

    /**
     * @return View
     */
    public function handle(Request $request, string $list, int $itemId): mixed
    {
        $component = $this->loader->resolve($list);

        $query = $this->queryService->buildEditQuery($component);
        $item = $this->queryService->findOrAbort($component, $query, $itemId);

        $component->checkCustomPath('customEditPage', $item);
        $this->authService->ensureCanUpdate($component, $item);

        $fields = $this->prepareEditFields($component, $item);

        $view = $component->customViews['form'] ?? 'lists::form';

        return view($view, [
            'item' => $item,
            'component' => $component,
            'scripts' => $component->scripts(),
            'fields' => $fields,
            'list' => $list,
            'title' => $component->getCustomLabel('edit') ?? __('lists.actions.edit').' '.$component->getSingleLabel(),
            'pageTitle' => $component->getCustomLabel('edit') ?? __('lists.actions.edit').' '.$component->getSingleLabel(),
            'frame' => (int) $request->get('frame', 0),
        ]);
    }

    /**
     * Подготавливает поля для формы редактирования.
     *
     * @return array<int, Field>
     */
    private function prepareEditFields(Component $component, $item): array
    {
        $fields = Arr::where($component->getFields(), fn (Field $f) => $f->show_on_update);

        return Arr::map($fields, function (Field $field) use ($item) {
            $field->item($item)->showEdit();

            return $field;
        });
    }
}
