<?php

namespace Zak\Lists\Http\Controllers;

use Illuminate\Http\Request;
use Zak\Lists\Actions\BulkActionRunner;
use Zak\Lists\Actions\CreateAction;
use Zak\Lists\Actions\DestroyAction;
use Zak\Lists\Actions\EditAction;
use Zak\Lists\Actions\IndexAction;
use Zak\Lists\Actions\PageAction;
use Zak\Lists\Actions\SaveOptionsAction;
use Zak\Lists\Actions\ShowAction;
use Zak\Lists\Actions\StoreAction;
use Zak\Lists\Actions\UpdateAction;

/**
 * Тонкий контроллер: принимает HTTP-запрос и делегирует его соответствующему Action-классу.
 * Никакой бизнес-логики здесь нет.
 */
class ListController
{
    public function index(Request $request, string $list, IndexAction $action): mixed
    {
        return $action->handle($request, $list);
    }

    public function show(Request $request, string $list, int $item, ShowAction $action): mixed
    {
        return $action->handle($request, $list, $item);
    }

    public function create(Request $request, string $list, CreateAction $action): mixed
    {
        return $action->handle($request, $list);
    }

    public function store(Request $request, string $list, StoreAction $action): mixed
    {
        return $action->handle($request, $list);
    }

    public function edit(Request $request, string $list, int $item, EditAction $action): mixed
    {
        return $action->handle($request, $list, $item);
    }

    public function update(Request $request, string $list, int $item, UpdateAction $action): mixed
    {
        return $action->handle($request, $list, $item);
    }

    public function destroy(Request $request, string $list, int $item, DestroyAction $action): mixed
    {
        return $action->handle($request, $list, $item);
    }

    public function options(Request $request, string $list, SaveOptionsAction $action): mixed
    {
        return $action->handle($request, $list);
    }

    public function bulkAction(Request $request, string $list, BulkActionRunner $action): mixed
    {
        return $action->handle($request, $list);
    }

    public function pages(Request $request, string $list, int $item, string $page, PageAction $action): mixed
    {
        return $action->handle($request, $list, $item, $page);
    }
}
