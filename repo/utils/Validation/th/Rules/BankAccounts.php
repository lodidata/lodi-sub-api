<?php

namespace Utils\Validation\th\Rules;

use Respect\Validation\Rules\AbstractRule;
use Respect\Validation\Validator as V;

class BankAccounts extends AbstractRule
{
    public function validate($input)
    {
        $res = V::numeric()->noWhitespace()->length(2, 19)->validate($input);
        return $res;
    }
}