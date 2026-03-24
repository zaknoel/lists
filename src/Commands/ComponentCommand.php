<?php

namespace Zak\Lists\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Создаёт новый файл конфигурации компонента (Lists/*.php).
 */
class ComponentCommand extends Command
{
    protected $signature = 'zak:make-component
                            {name : Имя файла компонента (например: Users)}
                            {--m|model= : Имя модели Eloquent (по умолчанию совпадает с name)}';

    protected $description = 'Создать новый файл компонента в директории Lists/';

    public function handle(): int
    {
        $name = $this->argument('name');
        $model = $this->option('model') ?? $name;
        $label = Str::headline($name);
        $singular = Str::lower(Str::singular($label));

        $listsPath = rtrim(config('lists.path', app_path('Lists/')), '/');
        $componentPath = $listsPath.'/'.$name.'.php';

        if (File::exists($componentPath)) {
            $this->error("Компонент уже существует: {$componentPath}");

            return self::FAILURE;
        }

        $stub = File::get(__DIR__.'/../../stubs/component.stub');

        $content = str_replace(
            ['{{ model }}', '{{ label }}', '{{ singular }}'],
            [$model, $label, $singular],
            $stub,
        );

        File::ensureDirectoryExists($listsPath);
        File::put($componentPath, $content);

        $this->info("Компонент создан: {$componentPath}");

        return self::SUCCESS;
    }
}
