<?php

namespace Zak\Lists;

use Illuminate\Database\Eloquent\Model;
use Zak\Lists\Concerns\Makeable;
use Zak\Lists\Contracts\ComponentLoaderContract;

class Action
{
    use Makeable;

    public string $name = '';

    public string $type = 'action';

    public string $action = 'show';

    public bool $blank = false;

    public bool $default = false;

    public mixed $show = null;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function editAction(): static
    {
        $this->action = 'edit';

        return $this;
    }

    public function show($func): static
    {
        $this->show = $func;

        return $this;
    }

    public function isShown(Component $component, Model $item): bool
    {
        if ($this->show === null) {
            return match ($this->action) {
                'show' => (bool) $component->userCanView($item),
                'edit' => (bool) $component->userCanEdit($item),
                'delete' => (bool) $component->userCanDelete($item),
                default => true,
            };
        }

        return is_callable($this->show) ? (bool) call_user_func($this->show, $component, $item) : true;
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
        $this->action = 'delete';

        return $this;
    }

    public function showAction(): static
    {
        $this->action = 'show';

        return $this;
    }

    public function setLinkAction(string $link): static
    {
        $this->type = 'link';
        $this->action = $link;

        return $this;
    }

    public function setJsAction(string $code): static
    {
        $this->type = 'js';
        $this->action = $code;

        return $this;
    }

    public function getLink(Model $item, string $list, ?string $name = null, string $class = ''): string
    {
        /** @var Component $component */
        $component = app(ComponentLoaderContract::class)->resolve($list);
        $label = $name ?? $this->name ?? '';

        return match (true) {
            $this->action === 'show' => '<a class="'.$class.'" href="'.$component->getRoute('lists_detail', $list, $item).'">'.$label.'</a>',
            $this->action === 'edit' => '<a class="'.$class.'" href="'.$component->getRoute('lists_edit', $list, $item).'">'.$label.'</a>',
            $this->action === 'delete' => $this->renderDeleteForm($component, $item, $list, $label),
            $this->type === 'link' => '<a class="'.$class.'" href="'.$this->action.'">'.$label.'</a>',
            $this->type === 'js' => '<a class="'.$class.'" href="javascript:void(0)" onclick="'.str_replace('item_id', (string) $item->id, $this->action).'">'.$label.'</a>',
            default => $label,
        };
    }

    /**
     * Рендерит форму для удаления с подтверждением.
     */
    private function renderDeleteForm(Component $component, Model $item, string $list, string $label): string
    {
        $action = $component->getRoute('lists_delete', $list, $item);
        $token = csrf_token();
        $confirm = __('lists.messages.delete_confirm');

        return <<<HTML
<form onsubmit="return confirm('{$confirm}')" method="post" action="{$action}">
    <input type="hidden" name="_token" value="{$token}" />
    <input type="hidden" name="_method" value="DELETE" />
    <a class="dropdown-item" onclick="$(this).parent().submit()">{$label}</a>
</form>
HTML;
    }
}
