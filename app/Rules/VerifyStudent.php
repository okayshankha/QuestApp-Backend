<?php

namespace App\Rules;

use App\User;
use Illuminate\Contracts\Validation\Rule;

class VerifyStudent implements Rule
{
    private $attribute, $invalid_values;
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->invalid_values = [];
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute  (id, user_id or email only)
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $values)
    {
        $values = $values ? explode(',', $values) : null;
        $evaluation = true;

        foreach ($values as $value) {
            $userLevels = config('QuestApp.UserLevels');
            if (in_array($attribute, ['user_id', 'id'])) {
                $this->attribute = 'user_id';
            } else {
                $this->attribute = 'email';
            }

            $count = User::all()
                ->where($this->attribute, $value)
                ->where('active', true)
                ->where('role', $userLevels['s'])
                ->count();

            if (!$count) {
                $evaluation = false;
                $this->invalid_values[] = $value;
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
        $error_msg = [];
        foreach ($this->invalid_values as $value) {
            $error_msg[] = "This :attribute {$value} does not belong to a student or student account is not active.";
        }
        return $error_msg;
    }
}
