<?php

namespace Utils\Validation\zh\Rules;

use Respect\Validation\Rules\AbstractRule;
use Respect\Validation\Validator as V;

class MxnUsername extends AbstractRule
{
    public function validate($input)
    {
        $res = V::alnum('_')->noWhitespace()->length(6, 15)->validate($input);
        return $res;
    }
}