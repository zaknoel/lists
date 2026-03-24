<?php

namespace Zak\Lists\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Zak\Lists\Database\Factories\UserOptionFactory;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property array<string, mixed> $value
 */
class UserOption extends Model
{


    protected static function newFactory(): UserOptionFactory
    {
        return UserOptionFactory::new();
    }

    protected $table = '_user_list_options';

    protected $fillable = [
        'user_id',
        'name',
        'value',
    ];

    protected $casts = [
        'value' => 'json',
    ];
}
