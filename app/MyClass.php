<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MyClass extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at', 'created_at', 'modified_at'];

    protected $fillable = [
        'name',
        'class_id',
        'space_id',
        'description',
        'active',
        'created_by_user_id',
        'modified_by_user_id',
        'deleted_by_user_id'
    ];

    protected $hidden = [
        'id'
    ];

    /**
     * Always capitalize the name when we retrieve it
     */
    public function getNameAttribute($value)
    {
        return ucwords($value);
    }

    /**
     * Always dependents on the active data when we retrieve it
     */
    public function getActiveAttribute($value)
    {
        $space = Space::withTrashed()->where('space_id', $this->space_id)->first();
        if ($space) {
            $active_status = $value && $space->active;
            return $active_status ? 1 : 0;
        } else {
            return 0;
        }
    }

    public static function getUpdatableFields()
    {
        // space_id is updatable to transfer my class to other teacher
        return [
            'name', 'space_id', 'description', 'active'
        ];
    }
}
