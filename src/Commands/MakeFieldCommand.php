<?php

namespace Zak\Lists\Commands;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Создаёт новый класс кастомного поля в директории app/Lists/Custom/.
 */
class MakeFieldCommand extends GeneratorCommand
{
    protected $name = 'zak:make-field';

    protected $description = 'Создать новый класс кастомного поля для списка';

    protected $type = 'Field';

    protected function getStub(): string
    {
        return __DIR__.'/../../stubs/field.stub';
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Lists\Custom';
    }

    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::REQUIRED, 'Название класса поля (например: StatusField)'],
        ];
    }
}
