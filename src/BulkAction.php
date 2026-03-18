<?php

namespace Zak\Lists;

use Zak\Lists\Concerns\Makeable;

class BulkAction
{
    use Makeable;

    public string $confirmText = '';

    public string $successMessage = '';

    public string $icon = '';

    public function __construct(
        public string $name,
        public string $key,
        public \Closure $callback,
    ) {
        $this->confirmText = __('lists.messages.bulk_confirm');
        $this->successMessage = __('lists.messages.bulk_success');
    }

    public function setSuccessMessage(string $text): static
    {
        $this->successMessage = $text;

        return $this;
    }

    public function setConfirmText(string $text): static
    {
        $this->confirmText = $text;

        return $this;
    }

    public function setIcon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function getSuccessMessage(): string
    {
        return $this->successMessage ?: __('lists.messages.bulk_success');
    }

    public function confirmText(): string
    {
        return $this->confirmText ?: __('lists.messages.bulk_confirm');
    }

    public function label(): string
    {
        return $this->name;
    }
}
