<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at', 'created_at', 'modified_at'];

    protected $fillable = [
        'name', 'department_id', 'description', 'active', 'created_by_user_id', 'modified_by_user_id'
    ];

    protected $hidden = [
        'id', 'hod_user_id'
    ];

    /**
     * Always capitalize the name when we retrieve it
     */
    public function getNameAttribute($value)
    {
        return ucwords($value);
    }

    public static function getUpdatableFields()
    {
        return [
            'name', 'description', 'active', 'modified_by_user_id'
        ];
    }
}
