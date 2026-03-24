<?php

namespace Zak\Lists\Contracts;

use Illuminate\Database\Eloquent\Model;
use Zak\Lists\Component;

interface AuthorizationContract
{
    /**
     * Проверяет право просматривать список. При отказе — abort(403).
     */
    public function ensureCanViewAny(Component $component): void;

    /**
     * Проверяет право просматривать конкретный элемент. При отказе — abort(403).
     */
    public function ensureCanView(Component $component, Model $item): void;

    /**
     * Проверяет право создавать элемент. При отказе — abort(403).
     */
    public function ensureCanCreate(Component $component): void;

    /**
     * Проверяет право редактировать элемент. При отказе — abort(403).
     */
    public function ensureCanUpdate(Component $component, Model $item): void;

    /**
     * Проверяет право удалять элемент. При отказе — abort(403).
     */
    public function ensureCanDelete(Component $component, Model $item): void;
}
