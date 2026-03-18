<?php

namespace Zak\Lists\Actions;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Zak\Lists\Contracts\AuthorizationContract;
use Zak\Lists\Contracts\ComponentLoaderContract;
use Zak\Lists\Contracts\QueryContract;

/**
 * Отображает кастомную страницу (вкладку) детального просмотра элемента.
 */
class PageAction
{
    public function __construct(
        private readonly ComponentLoaderContract $loader,
        private readonly QueryContract $queryService,
        private readonly AuthorizationContract $authService,
    ) {}

    /**
     * @return View
     */
    public function handle(Request $request, string $list, int $itemId, string $page): mixed
    {
        $component = $this->loader->resolve($list);

        $query = $this->queryService->buildDetailQuery($component);
        $item = $this->queryService->findOrAbort($component, $query, $itemId);

        $this->authService->ensureCanView($component, $item);

        $pages = $component->getPages();
        $currentPage = $pages[$page] ?? null;

        if (! $currentPage) {
            abort(404, __('lists.errors.page_not_found'));
        }

        $view = is_callable($currentPage['view'])
            ? call_user_func($currentPage['view'], $item)
            : '';

        return view('lists::detail', [
            'component' => $component,
            'view' => $view,
            'pages' => $pages,
            'item' => $item,
            'list' => $list,
            'fields' => [],
            'page' => $page,
        ]);
    }
}
