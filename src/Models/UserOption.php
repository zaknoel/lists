<?php

namespace Zak\Lists\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserOption extends Model
{
    use HasFactory;
    protected $table='_user_list_options';

    protected $casts=[
        "value" => "json"
    ];
}
