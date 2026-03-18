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
 * Отображает детальную страницу элемента.
 */
class ShowAction
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
        $component->checkCustomPath('customDetailPage', $itemId);

        $query = $this->queryService->buildDetailQuery($component);
        $item = $this->queryService->findOrAbort($component, $query, $itemId);

        $this->authService->ensureCanView($component, $item);

        $fields = $this->prepareDetailFields($component, $item);

        $detailView = $component->customViews['detail'] ?? null;
        $listView = 'lists::detail';

        $viewData = [
            'pages' => $component->getPages(),
            'component' => $component,
            'item' => $item,
            'list' => $list,
            'fields' => $fields,
        ];

        if ($detailView) {
            $viewData['view'] = view($detailView, ['item' => $item, 'fields' => $fields]);
        }

        return view($listView, $viewData);
    }

    /**
     * Готовит поля для детального просмотра — применяет значения и колбэки.
     *
     * @return array<int, Field>
     */
    private function prepareDetailFields(Component $component, $item): array
    {
        $fields = $component->getFilteredFields(fn (Field $f) => $f->show_in_detail);

        return Arr::map($fields, function (Field $field) use ($item) {
            $field->item($item);

            return $field;
        });
    }
}
