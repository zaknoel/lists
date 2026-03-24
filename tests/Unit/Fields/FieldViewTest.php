<?php

use Zak\Lists\Fields\Field;

it('show бросает RuntimeException если view для поля не найдена', function () {
    $field = new class('Missing', 'missing') extends Field
    {
        public function type(): string
        {
            return 'text';
        }

        public function handleFill(): void {}

        public function componentName(): string
        {
            return 'missing_component_for_test';
        }

        public function detailHandler(): void {}

        public function indexHandler(): void {}

        public function generateFilter($query = null): mixed
        {
            return null;
        }

        public function filteredValue(): string
        {
            return '';
        }

        public function saveHandler($item, $data): void {}
    };

    $field->show();
})->throws(RuntimeException::class, 'field view "lists::fields.missing_component_for_test" was not found');
