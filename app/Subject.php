<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subject extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'subject_id',
        'category_id',
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
        $category = Category::withTrashed()->where('category_id', $this->category_id)->first();
        if ($category) {
            $active_status = $value && $category->active;
            return $active_status ? 1 : 0;
        } else {
            return 0;
        }
    }

    public static function getUpdatableFields()
    {
        return [
            'name', 'category_id', 'description', 'active'
        ];
    }
}
