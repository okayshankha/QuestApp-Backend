<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Examination extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'examination_id',
        'subject_id',
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
        $subject = Subject::withTrashed()->where('subject_id', $this->subject_id)->first();
        if ($subject) {
            $active_status = $value && $subject->active;
            return $active_status ? 1 : 0;
        } else {
            return 0;
        }
    }

    public static function getUpdatableFields()
    {
        return [
            'name', 'subject_id', 'description', 'active'
        ];
    }

}
