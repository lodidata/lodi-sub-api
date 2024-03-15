<?php

namespace Utils\Validation\es\Rules;

use Respect\Validation\Rules\AbstractRule;
use Respect\Validation\Validator as V;

class WithdrawPwd extends AbstractRule
{
    public function validate($input)
    {
        $res = V::numeric()->length(4,4)->noWhitespace()->validate($input);
        return $res;
    }
}