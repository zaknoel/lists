<?php

namespace Zak\Lists\Services;

use Illuminate\Database\Eloquent\Model;
use Zak\Lists\Component;
use Zak\Lists\Contracts\AuthorizationContract;

/**
 * Централизованная проверка прав доступа через политики Laravel.
 * Инкапсулирует abort(403) чтобы контроллеры и экшены оставались тонкими.
 */
class AuthorizationService implements AuthorizationContract
{
    public function ensureCanViewAny(Component $component): void
    {
        if (! $component->userCanViewAny()) {
            abort(403, __('lists.errors.unauthorized'));
        }
    }

    public function ensureCanView(Component $component, Model $item): void
    {
        if (! $component->userCanView($item)) {
            abort(403, __('lists.errors.unauthorized'));
        }
    }

    public function ensureCanCreate(Component $component): void
    {
        if (! $component->userCanAdd()) {
            abort(403, __('lists.errors.unauthorized'));
        }
    }

    public function ensureCanUpdate(Component $component, Model $item): void
    {
        if (! $component->userCanEdit($item)) {
            abort(403, __('lists.errors.unauthorized'));
        }
    }

    public function ensureCanDelete(Component $component, Model $item): void
    {
        if (! $component->userCanDelete($item)) {
            abort(403, __('lists.errors.unauthorized'));
        }
    }
}
