<?php

namespace Utils\Validation\zh\Rules;

use Respect\Validation\Rules\AbstractRule;
use Respect\Validation\Validator as V;

class Mobile extends AbstractRule
{
    public function validate($input)
    {
        $res = V::numeric()->noWhitespace()->length(6, 16)->validate($input);
        return $res;
    }
}