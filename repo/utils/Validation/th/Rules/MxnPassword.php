<?php

namespace Utils\Validation\th\Rules;

use Respect\Validation\Rules\AbstractRule;
use Respect\Validation\Validator as V;

class MxnPassword extends AbstractRule
{
    public function validate($input)
    {
        $res = V::alnum()->noWhitespace()->length(6, 15)->validate($input);
        return $res;
    }
}