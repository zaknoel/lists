<?php

namespace Zak\Lists\Http\Contollers;

use Illuminate\Http\Request;
use Zak\Lists\ListComponent;

class ListController
{
    public function list(Request $request, string $list)
    {

        return ListComponent::listHandler($request, $list);
    }

    public function detail(Request $request, string $list, int $item)
    {

        return ListComponent::detailHandler($request, $list, $item);
    }

    public function pages(Request $request, string $list, int $item, string $page)
    {

        return ListComponent::pagesHandler($request, $list, $item, $page);
    }

    public function edit_form(Request $request, string $list, int $item)
    {

        return ListComponent::editFormHandler($request, $list, $item);
    }

    public function edit_save(Request $request, string $list, int $item)
    {

        return ListComponent::editSaveHandler($request, $list, $item);
    }

    public function add_form(Request $request, string $list)
    {

        return ListComponent::addFormHandler($request, $list);
    }

    public function add_save(Request $request, string $list)
    {

        return ListComponent::addSaveHandler($request, $list);
    }

    public function delete(Request $request, string $list, int $item)
    {

        return ListComponent::deleteHandler($request, $list, $item);
    }

    public function options(Request $request, string $list)
    {

        return ListComponent::optionHandler($request, $list);
    }
}
