<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExamQuestionMap extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'exam_question_map_id',
        'question_id',
        'examination_id',
        'active',
        'created_by_user_id',
        'modified_by_user_id',
        'deleted_by_user_id'
    ];

    protected $hidden = [
        'id'
    ];

    public static function getQuestionIdAttribute($value)
    {
        return Question::where('question_id', $value)->first(['question_id', 'question']);
    }

    public static function getUpdatableFields()
    {
        return [
            'active',
        ];
    }
}
