<?php

namespace Zak\Lists;

use Closure;
use Laravel\Nova\Makeable;

class Action
{
    use Makeable;

    public string $name = "";
    public string $type = "action";
    public string $action = "show";
    public bool $blank = false;
    public bool $default = false;
    public Closure|null $show = null;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function editAction(): static
    {
        $this->action = "edit";
        return $this;
    }

    public function show($func): static
    {
        $this->show = $func;
        return $this;
    }

    public function isShown($component, $item)
    {
        return is_callable($this->show) ? call_user_func($this->show, $component, $item) : true;
    }

    public function default(): static
    {
        $this->default = true;
        return $this;
    }

    public function blank(): static
    {
        $this->blank = true;
        return $this;
    }

    public function deleteAction(): static
    {
        $this->action = "delete";
        return $this;
    }

    public function showAction(): static
    {
        $this->action = "show";
        return $this;
    }

    public function setLinkAction($link): static
    {
        $this->type = "link";
        $this->action = $link;
        return $this;
    }

    public function setJsAction($code): static
    {
        $this->type = "js";
        $this->action = $code;
        return $this;
    }

    public function getLink($item, $list, $name = "", $class = "")
    {
        $name = $name ?: $this->name;
        if ($this->action === "show") {
            return '<a class="' . $class . '" href="' . route("lists_detail", ["list" => $list, "item" => $item]) . '">' . $name . '</a>';
        }

        if ($this->action === "edit") {
            return '<a class="' . $class . '" href="' . route("lists_edit", ["list" => $list, "item" => $item]) . '">' . $name . '</a>';
        }
        if ($this->action === "delete") {
            return ' <form onsubmit="return confirm(\'Вы уверены, что хотите удалить этот элемент?\')" method="post"
                      action="' . route("lists_delete", ["list" => $list, "item" => $item]) . '">
        <input type="hidden" name="_token" value=" ' . csrf_token() . '" />
                    <a class="dropdown-item" onclick="$(this).parent().submit()">' . $name . '</a>
                </form>';
        }
        if ($this->type === 'link') {
            return '<a class="' . $class . '" href="' . $this->action . '">' . $name . '</a>';
        }
        if ($this->type === 'js') {
            return '<a class="' . $class . '" href="javascript:void(0)" onclick="' . str_replace('item_id', $item->id, $this->action) . '">' . $name . '</a>';
        }
        return $name;
    }

}