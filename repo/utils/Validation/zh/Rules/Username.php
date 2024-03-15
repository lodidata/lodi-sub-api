<?php

namespace Utils\Validation\zh\Rules;

use Respect\Validation\Rules\AbstractRule;
use Respect\Validation\Validator as V;

class Username extends AbstractRule
{
    public function validate($input)
    {
        $res = V::alnum('_')->noWhitespace()->length(4, 30)->validate($input);
        return $res;
    }
}