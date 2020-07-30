<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class VerifyActiveWithEntryCurstomID implements Rule
{
    private $model;
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($Model)
    {
        $this->model = $Model;
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
        $modelClassIdString = GetCustomClassIdString($this->model);

        $entry = (new $this->model)::where($modelClassIdString, $value)
            ->where('active', 1)
            ->first();

        if (!$entry) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute is suspended.';
    }
}
