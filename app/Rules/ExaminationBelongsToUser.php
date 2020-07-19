<?php

namespace App\Rules;

use App\Examination;
use Illuminate\Contracts\Validation\Rule;

class ExaminationBelongsToUser implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
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
        if (in_array($attribute, ['examination_id', 'id'])) {
            $this->attribute = 'examination_id';
        } else {
            $this->attribute = 'name';
        }


        $count = Examination::withTrashed()
            ->where('created_by_user_id', request()->user()->user_id)
            ->where($this->attribute, $value)
            ->count();


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
            return 'This :attribute already exists.';
        } else if ($this->attribute == 'examination_id') {
            return 'This :attribute does not belong to the user.';
        }
    }
}
