<?php

use App\Models\Category;
use App\Models\Question;
use App\Models\Script;
use App\Models\Spec;
use App\Zak\Component\Component;
use App\Zak\Component\Fields\Boolean;
use App\Zak\Component\Fields\ID;
use App\Zak\Component\Fields\Number;
use App\Zak\Component\Fields\Relation;
use App\Zak\Component\Fields\Text;

return Component::init([
    "model" => Script::class,
    "singleLabel" => "скрипт",
    "label" => "Скрипты",
    "fields" => [
        ID::make("ID", 'id')->hideOnForms()->sortable()->filterable()->showOnIndex(),
        Boolean::make("Активность", 'active')->sortable()->filterable()
            ->default(true)->width(6),
        Boolean::make("Для врачей", 'is_doctor')->sortable()->filterable()
            ->default(false)->width(6),
        Text::make("Название", "name")->sortable()->defaultAction(),

        Number::make("Номер визита", "number")->sortable()->filterable(),
        Relation::make("Специальность врача", 'spec_id')
            ->model(Spec::class)->field('name')->filter(["active", "=", true])
            ->sortable()->filterable(),
        Relation::make('Категория', "category_id")
            ->model(Category::class)
            ->filter(["active", "=", true])
            ->field('name'),
        Number::make("Сортировка", "sort")->sortable()->default(500),

    ],
    'pages' => [
        "questions" => [
            'title' => 'Вопросы',
            'view' => static function ($item) {
                $quests = [];
                $branched = [];
                global $used_quests;
                $used_quests = [];
                $all_quests = Question::whereScriptId($item->id)->orderBy('parent_id', 'asc')->orderBy('number', 'asc')->get();
                foreach ($all_quests as $v) {
                    if (!$v->parent_id) {
                        $v->number;
                        $quests[] = $v;
                    } else {
                        $parent = $all_quests->where('id', $v->parent_id)->first();
                        $v->number = $parent?->number . '.' . $v->number;
                        $branched[$v->parent_id][$v->variant][$v->id] = $v;
                    }
                }
                $questData = "";
                foreach ($quests as $v):
                    $questData .= showQuest($v, $branched);
                endforeach;

                return view('pages.script_questions',
                    [
                        "item" => $item,
                        "questData" => $questData
                    ]
                );
            }
        ]
    ],
    "customScript" => "<script>
    $(function (){
       HandleScriptRelations();
       $('#col-is_doctor input[type=\"checkbox\"]').on('switchChange.bootstrapSwitch', function(){
           HandleScriptRelations();
       })
    });
    function HandleScriptRelations(){
        const isDoctor= $('#col-is_doctor input[type=\"checkbox\"]').is(':checked');
        if(isDoctor){
            $('#col-spec_id').show().find('select').attr('required', true);
        }else{
          $('#col-spec_id').hide().find('select').attr('required', false);

        }
    }

</script>"

]);
