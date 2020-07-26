<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EntityUserMapping extends Model
{
    protected $fillable = [
        'entity_user_mapping_id',
        'user_id',
        'entity_id',
        'activation_token',
        'type',
        'active',
        'joined_at',
        'created_by_user_id',
        'modified_by_user_id',
        'deleted_by_user_id'
    ];

    protected $hidden = [
        'id'
    ];
}
