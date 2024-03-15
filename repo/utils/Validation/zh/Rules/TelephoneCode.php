<?php

namespace Utils\Validation\zh\Rules;

use Respect\Validation\Rules\AbstractRule;
use Respect\Validation\Validator as V;

class TelephoneCode extends AbstractRule
{
    public function validate($input)
    {
        $res = V::alnum('+')->noWhitespace()->length(2, 5)->validate($input);
        return $res;
    }
}