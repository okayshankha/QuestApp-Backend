<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at', 'created_at', 'modified_at'];

    protected $fillable = [
        'name', 'category_id', 'department_id', 'description', 'active', 'created_by_user_id', 'modified_by_user_id'
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
     * Always dependent active data when we retrieve it
     */
    public function getActiveAttribute($value)
    {
        $department = Department::withTrashed()->where('department_id', $this->department_id)->first();
        if ($department) {
            $active_status = $value && $department->active;
            return $active_status ? 1 : 0;
        } else {
            return 0;
        }
    }

    public static function getUpdatableFields()
    {
        return [
            'name', 'department_id', 'description'
        ];
    }
}
