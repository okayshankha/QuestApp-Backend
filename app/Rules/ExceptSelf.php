<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ExceptSelf implements Rule
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
        $evaluation = true;
        $self = request()->user();

        if ($attribute == 'email') {
            if ($self->email == $value) {
                $evaluation = false;
            }
        } else if ($attribute == 'user_id' || $attribute == 'id') {
            if ($self->user_id == $value) {
                $evaluation = false;
            }
        }
        return $evaluation;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'You cannot use data that belongs to you.';
    }
}
