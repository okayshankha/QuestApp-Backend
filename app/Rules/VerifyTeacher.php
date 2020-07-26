<?php

namespace App\Rules;

use App\User;
use Illuminate\Contracts\Validation\Rule;

class VerifyTeacher implements Rule
{
    private $attribute, $invalid_values, $PASS_FOR_NON_EXISTENT = false;
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($pass_filter = null)
    {

        $this->invalid_values = [];
        if ($pass_filter === 'PASS_FOR_NON_EXISTENT') {
            $this->PASS_FOR_NON_EXISTENT = true;
        }
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
        // $values = $values ? explode(',', $values) : null;

        $evaluation = true;
        if (!is_array($values)) {
            $values = [$values];
        }

        foreach ($values as $value) {
            $userLevels = config('QuestApp.UserLevels');
            if (in_array($attribute, ['user_id', 'id'])) {
                $this->attribute = 'user_id';
            } else {
                $this->attribute = 'email';
            }

            $user = User::all()
                ->where($this->attribute, $value)
                ->where('active', true)
                ->first();

            if ($user) {
                if ($user->role !== $userLevels['t']) {
                    $evaluation = false;
                    $this->invalid_values[] = $value;
                }
            } else {
                if (!$this->PASS_FOR_NON_EXISTENT) {
                    $evaluation = false;
                    $this->invalid_values[] = $value;
                }
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
