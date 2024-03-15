<?php

namespace Utils\Validation\en\Rules;

use Respect\Validation\Rules\AbstractRule;
use Model\User;
class UsernameRegister extends AbstractRule
{
    public function validate($input)
    {
        if (User::where('name', '=', $input)->count() > 0) {
            return false;
        }

        return true;
    }
}