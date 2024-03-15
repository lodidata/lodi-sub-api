<?php

namespace Utils\Validation\th\Rules;

use Respect\Validation\Rules\AbstractRule;
use Model\User;
class UsernameLogin extends AbstractRule
{
    public function validate($input)
    {
        if (User::where('name', '=', $input)->count() > 0) {
            return true;
        }

        return false;
    }
}