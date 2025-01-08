<?php

namespace Zak\Lists\Commands;

use File;
use Illuminate\Console\Command;

class ComponentCommand extends Command
{
    protected $signature = 'zak:component {name} {--m|model= : The name of the model}';

    protected $description = 'Create new component';

    public function handle()
    {

        $name = $this->argument('name');
        $model = $this->option('model') ?? 'User';

        // Paths for component and optional model
        $componentPath = config('lists.path').'/'.$name.'.php';
        $modelPath = $model ? app_path("Models/{$model}.php") : null;

        // Create the component file
        if (! File::exists($componentPath)) {
            File::ensureDirectoryExists(dirname($componentPath));
            File::put($componentPath, "
<?php
use App\Models\{$model};
use Zak\Lists\Component;
use Zak\Lists\Fields\ID;
use Zak\Lists\Action;
return new Component(
    model: $model::class,
    label: '',
    singleLabel: '',
    fields: [
        ID::make('ID', 'id')
            ->hideOnForms()
            ->sortable()
            ->filterable()
            ->showOnIndex(),
        //other fields

    ],
    actions: [
                Action::make('Просмотр')->showAction()->default(),
                Action::make('Редактировать')->editAction(),
                Action::make('Удалить')->deleteAction(),
    ],
    pages: [
        //pages
    ],
    customScript: '',
    OnQuery: function (\$query) {
        return \$query;
    },
    OnIndexQuery: function (\$query) {
        return \$query;
    },
    OnDetailQuery: function (\$query) {
        return \$query;
    },
    OnEditQuery: function (\$query) {
        return \$query;
    },
    OnBeforeSave: function (\$item) {
        return \$item;
    },
    OnAfterSave: function (\$item) {
        return \$item;
    },
    OnBeforeDelete: function (\$item) {
        return \$item;
    },
    OnAfterDelete: function (\$item) {
        return \$item;
    },
    canView: function (\$item) {
        return true;
    },
    canViewAny: function () {
        return true;
    },
    canAdd: function () {
        return true;
    },
    canEdit: function (\$item) {
        return true;
    },
    canDelete: function (\$item) {
        return true;
    }
);

            ");
            $this->info("Component {$name} created at {$componentPath}");
        } else {
            $this->error("Component {$name} already exists.");
        }

        return Command::SUCCESS;
    }
}
