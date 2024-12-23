<?php

use App\Models\Answer;
use App\Models\Category;
use App\Models\Company;
use App\Models\Doctor;
use App\Models\Product;
use App\Models\Question;
use App\Models\Sale;
use App\Models\Script;
use App\Models\Status;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitResult;
use App\Zak\Component\Action;
use App\Zak\Component\Component;
use App\Zak\Component\Fields\BelongToMany;
use App\Zak\Component\Fields\ID;
use App\Zak\Component\Fields\Number;
use App\Zak\Component\Fields\Relation;
use App\Zak\Component\Fields\Select;
use App\Zak\Component\Fields\Text;
use App\Zak\Month;
use Carbon\Carbon;

return Component::init([
    "model" => Visit::class,
    "singleLabel" => "визит",
    "label" => "Визиты",
    "fields" => array_filter([
        ID::make("ID", 'id')->hideOnForms()->sortable()->filterable()->showOnIndex(),
        Relation::make("Статус", 'status_id')
            ->model(Status::class)->field('name')
            ->sortable()->filterable()->hideOnForms()
            ->displayInList(function ($item) {
                if ($item->status_id) {
                    return "<span class='badge text-bg-" . $item->status->code . "'>" . $item->status->name . "</span>";
                } else {
                    return "<span class='badge text-bg-warning'>Без статуса</span>";
                }
            })
            ->displayInDetail(function ($item) {
                if ($item->status_id) {
                    return "<span class='badge text-bg-" . $item->status->code . "'>" . $item->status->name . "</span>";
                } else {
                    return "<span class='badge text-bg-warning'>Без статуса</span>";
                }
            })
        ,
        isMedPred() ? "" : Relation::make("Мед. пред.", 'user_id')
            ->model(User::class)->field('name')
            ->sortable()->filterable()->filter(["operator"])->required(),
        Text::make("Название", "name")->sortable()->defaultAction()->hideOnForms(),
        \App\Zak\Component\Fields\Date::make('Дата', 'date')->filterable()->sortable()->required(),
        Select::make("Тип визита", 'is_doctor')->sortable()->filterable()->default([0])->width(6)
            ->enum([0 => 'Визит к аптеку', 1 => 'Визит к врачу']),
        Relation::make("Врач", 'doctor_id')
            ->model(Doctor::class)->field('name')
            ->sortable()->filterable(),

        Relation::make("Контрагент", 'company_id')
            ->model(Company::class)->field('name')
            ->sortable()->filterable(),
        Relation::make("Скрипт", 'script_id')
            ->model(Script::class)->field('name')
            ->sortable()->filterable()->required(),
        BelongToMany::make("Товары", 'products')->model(Product::class)
            ->field("name")
            ->required()
            ->filter(["active", "=", true])
        ,


        Number::make("Сортировка", "sort")->sortable()->default(500),

    ]),
    "onSearchModel" => function ($model) {
        if (isMedPred()) {
            return $model->where('user_id', auth()->user()->id);
        }

        return $model;
    },
    'onModel' => function ($model) {
        if (isMedPred()) $model->user_id = auth()->user()->id;
        return $model;
    },
    "OnBeforeSave" => static function (Visit $item) {
        if ($item->is_doctor) {
            $item->name = "Визит к врачу " . ($item->doctor_id ? $item->doctor->name : '');
        } else {
            $item->name = "Визит к аптеку " . ($item->company_id ? $item->company->name : '');
        }
        if (!$item->status_id) {
            $item->status_id = Visit::STATUS_NEW;
        }


    },
    'OnAfterSave' => function (Visit $item) {
        $gr=auth()->user()?->category_id;
        $uid=auth()->user()?->id;
        $uG=false;
        if($gr===Category::OTC){
            $uG='ots_user_id';
        }elseif($gr===Category::RX){
            $uG='rx_user_id';
        }elseif ($gr===Category::DERMA){
            $uG='dermo_user_id';
        }
        if($uG){
            $obj=$item->is_doctor?$item->doctor:$item->company;
            if((int)$obj->{$uG}!==(int)$uid){
                $obj->{$uG}=$uid;
                $obj->save();
            }
        }


        if (request()?->has('sub_items')) {
            $sub_items = request()?->get('sub_items', []);

            foreach ($sub_items["obj"] as $k => $v) {
                $newItem = $item->replicate(['id']);
                if ($newItem->is_doctor) {
                    $newItem->doctor_id = $v;
                    $newItem->load(['doctor']);
                } else {
                    $newItem->company_id = $v;
                    $newItem->load(['company']);
                }

                if ($newItem->is_doctor) {
                    $newItem->name = "Визит к врачу " . ($newItem->doctor_id ? $newItem->doctor->name : '');
                } else {
                    $newItem->name = "Визит к аптеку " . ($newItem->company_id ? $newItem->company->name : '');
                }
                $newItem->script_id = $sub_items["script"][$k];
                $newItem->push();
                $newItem->products()->sync($item->products->pluck('id')->toArray());
                if($uG){
                    $obj=$newItem->is_doctor?$newItem->doctor:$newItem->company;
                    if((int)$obj->{$uG}!==(int)$uid){
                        $obj->{$uG}=$uid;
                        $obj->save();
                    }
                }


            }

        }
    },
    'canAddItem' => function () {
        return true;
    },
    'canEditItem' => function ($item) {
        return $item->status_id === Status::STATUS_NEW || !$item->status_id;
    },
    'canDeleteItem' => function ($item) {
        return $item->status_id === Status::STATUS_NEW || !$item->status_id;
    },
    "actions" => array_filter([
        Action::make("Просмотр")->showAction()->default(),
        Action::make("Редактировать")->editAction()->show(function (Component $comp, $item) {
            return $comp->canEdit($item);
        }),
        Action::make("Копировать")->setJsAction('CopyVisit(item_id);'),
        Action::make("Удалить")->deleteAction()->show(function (Component $comp, $item) {
            return $comp->canDelete($item);
        }),
    ]),
    'pages' => [
        "results" => [
            'title' => 'Результаты визита',
            'view' => static function (Visit $item) {
                $result = VisitResult::whereVisitId($item->id)->first();
                if ($result) {
                    $questions = Question::whereScriptId($item->script_id)->get();
                    $ans = [];
                    foreach (Answer::whereVisitId($item->id)->orderBy('id')->get() as $v) {
                        $ans[$v->question_id] = $v;
                    }
                    //Факт закупа предедущие 2 месяца
                    $from = date('01.m.Y', strtotime($result->created_at->format('d.m.Y') . " - 2 month"));
                    $to = date('t.m.Y', strtotime($result->created_at->format('d.m.Y') . " - 1 month"));

                    $data = Sale::where('company_id', $item->company_id)
                        ->where('date', ">=", Carbon::make($from))
                        ->where('date', "<=", Carbon::make($to))
                        ->whereIn('product_id', $item->products ? $item->products->pluck('id')->toArray() : [])
                        ->get();
                    $res = [];
                    foreach ($data as $v) {
                        $res[$v->product->name][doTwo($v->date->month)] += $v->value_int;
                    }
                    $prevInfo = [];
                    $months = Month::array();
                    if ($res):
                        $prevInfo['fact_title'] = '📊  Факт закупа за предыдущие 2 месяца:';
                        foreach ($res as $k => $v) {
                            $prevInfo['fact'][] = '➖ ' . $k . PHP_EOL;
                            foreach ($v as $m => $r1) {
                                $prevInfo['fact'][] = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="font-italic">' . $months[$m] . ' - ' . btf($r1) . ' шт.</span>' . PHP_EOL;
                            }
                            $prevInfo['fact'][] = '------------------------------------' . PHP_EOL;
                        }
                    endif;

                    return view('pages.visit_result', compact('item', 'result', 'questions', 'prevInfo', 'ans'));
                }


                return view('pages.visit_result', ["item" => $item, 'result' => $result]);
            }
        ]
    ],
    "customScript" => "<script>
    $(function (){
       HandleVisitRelations();
       $('#col-is_doctor select').change(function(){
           HandleVisitRelations();
       })
    });
    const Rower={
            is_doctor:false,
            data:[],
            AddNew:function (_this){
                //check required fields
                if(this.is_doctor){
                    const doctor=$('#inputFordoctor_id').val();
                    if(!doctor) return alert('Сначала выберите врача!');
                }else{
                    const doctor=$('#inputForcompany_id').val();
                    if(!doctor) return alert('Сначала выберите контрагента!');
                }
                const date=$('#inputFordate').val();
                if(!date) return alert('Сначала выберите дату!');
                const user=$('#inputForuser_id').length?$('#inputForuser_id').val():" . auth()->user()->id . "
                if(!user) return alert('Сначала выберите мед. преда!');
                const products=$('#inputForproducts').val();
                if(!products) return alert('Сначала выберите товары!');
                const script=$('#inputForscript_id').val();
                if(!script) return alert('Сначала выберите скрипт!');
                this.refreshData();
                this.data.push({
                'is_doctor':this.is_doctor,
                'doctor_id':'',
                'doctor':'',
                'script_id':script,
                'script':$('#inputForscript_id').find('option:selected').text()
                });
                this.reCreate();
            },
            reInit:function (){
                this.is_doctor=parseInt($('#col-is_doctor select').val());
                if(this.is_doctor){
                      $('.add_new_row_btn').html(`<i class='ti ti-plus'></i> Добавить еще врач`);
                      $('.label_title').text('Врач');
                }else{
                     $('.add_new_row_btn').html(`<i class='ti ti-plus'></i> Добавить еще контрагент`);
                     $('.label_title').text('Контрагент');
                }
                this.refreshData();
                this.reCreate();
            },
            removeLine:function (_this, line){
                delete this.data[line];
                this.reCreate();
            },
            refreshData:function (){
                let data=[];
                $('.sub_form tbody>tr').each(function (){
                   const d={
                       'is_doctor':$(this).find('.obj_select').attr('data-model').indexOf('Doctor')>-1,
                       'doctor':$(this).find('.obj_select>option:selected').text(),
                       'doctor_id':$(this).find('.obj_select').val(),
                       'script_id':$(this).find('.script_select').val(),
                       'script':$(this).find('.script_select>option:selected').text(),
                   }
                   data.push(d);
                });
                this.data=data;
            },
            reCreate:function (){

                const container=$('.sub_form tbody');
                container.html('');
                const model=this.is_doctor?$('#inputFordoctor_id').attr('data-model'):$('#inputForcompany_id').attr('data-model');
                const filter=this.is_doctor?$('#inputFordoctor_id').attr('data-filter'):$('#inputForcompany_id').attr('data-filter');
               for(let i in this.data){
                   const data=this.data[i];
                       container.append(`<tr>
                        <td class='text-center fw-bolder'>\${parseInt(i)+1}</td>
                        <td>
                           <div>
                            <select
    class='form-control ajax_sel obj_select'
    name='sub_items[obj][\${i}]'
    required
    data-placeholder='Выберите \${this.is_doctor?'Врача':'контрагента'}'
    data-model='\${model}'
    data-field='name'
    data-filter='\${filter}'
>
    <option></option>

        \${(data.doctor_id && data.is_doctor==this.is_doctor) ?'<option selected value=\''+data.doctor_id+'\'>'+data.doctor+'</option>':''}
</select></div>
                        </td>
                        <td>
                        <div >
<select
    class='form-control ajax_sel script_select'
    name='sub_items[script][\${i}]'
    required
    data-placeholder='Выберите скрипт'
    data-model='App\\\Models\\\Script'
    data-field='name'
    data-filter='[]'
>
    <option></option>

        \${data.script_id?'<option selected value=\''+data.script_id+'\'>'+data.script+'</option>':''}
</select></div>
                        </td>
                        <td class='text-center fw-bolder'><a onclick='Rower.removeLine(this, \${i})' title='Удалить' href='javascript:void(0)' class='text-danger'><i class='ti ti-trash-x-filled'></i></a></td>
                    </tr>`);
               }//endfor
                initSelect();
            }
    };
    function HandleVisitRelations(){
        const isDoctor= parseInt($('#col-is_doctor select').val());
        if(isDoctor){
            $('#col-doctor_id').show().find('select').attr('required', true);
            $('#col-company_id').hide().find('select').attr('required', false);

        }else{
            $('#col-doctor_id').hide().find('select').attr('required', false);
            $('#col-company_id').show().find('select').attr('required', true);
        }
        Rower.reInit();
    }
    function CopyVisit(id){
        location.href='/lists/visits/add?copy_from='+id;
    }

    const form=$('.add_form');
    if(form.length){
       form.find('>.card-body').after(`<div class='sub_form p-3'>
       <div class='text-center fw-bolder mb-3 fs-6' >А так же создать для:</div>
       <div class='table-responsive'>
        <table class='table table-bordered ztable'>
            <thead class='text-center'>
                <tr>
                    <th style='width:50px;'>#</th>
                    <th class='label_title'>Контрагент</th>
                    <th>Скрипт</th>
                    <th style='width:50px;'>Удалить</th>
                </tr>
            </thead>
            <tbody>

            </tbody>
        </table>
        </div>
        <div class='text-end mb-5'>
               <button class='btn btn-sm btn-success rounded-pill px-4 waves-effect waves-light me-3 add_new_row_btn' type='button' onclick='Rower.AddNew(this)'>
        <i class='ti ti-plus'></i>
            Добавить еще контрагент
       </button>
        </div>
       </div>`);
       form.find('button[type=\'submit\']').before(`

       `)


    }


</script>"

]);
