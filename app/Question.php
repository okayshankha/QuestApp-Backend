<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use SoftDeletes;

    /*
    Option Strorage Format:
        [
            {
                "option": "Option",
                "iscorrect": "true",
                "ismathexpr": "true",
            }
        ]
     */

    protected $fillable = [
        'question_id',
        'question',
        'question_image_url',
        'options',
        'active',
        'created_by_user_id',
        'modified_by_user_id',
        'deleted_by_user_id'
    ];

    protected $hidden = [
        'id'
    ];

    public static function getOptionsAttribute($value)
    {
        return json_decode($value, true);
    }

    public static function getUpdatableFields()
    {
        return [
            'question', 'question_id', 'question_image_url', 'options', 'active'
        ];
    }
}
