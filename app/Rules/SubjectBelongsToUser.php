<?php

namespace App\Rules;

use App\Subject;
use Illuminate\Contracts\Validation\Rule;

class SubjectBelongsToUser implements Rule
{
    private $class_id;
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($class_id = null)
    {
        $this->class_id = $class_id;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $count = 0;
        if (in_array($attribute, ['subject_id', 'id'])) {
            $this->attribute = 'subject_id';
            $count = Subject::where('created_by_user_id', request()->user()->user_id)
                ->where($this->attribute, $value)
                ->count();
        } else {
            $this->attribute = 'name';
            $count = Subject::withTrashed()
                ->where('created_by_user_id', request()->user()->user_id)
                ->where('class_id', $this->class_id)
                ->where($this->attribute, $value)
                ->count();
        }


        if ($this->attribute == 'name') {
            return ($count <= 0) ? true : false;
        } else {
            return ($count > 0) ? true : false;
        }
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        if ($this->attribute == 'name') {
            return 'This subject :attribute already exists(may be trashed) in the class.';
        } else if ($this->attribute == 'subject_id') {
            return 'This :attribute does not belong to the user.';
        }
    }
}
