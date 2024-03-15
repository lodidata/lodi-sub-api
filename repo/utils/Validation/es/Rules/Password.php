<?php

namespace Utils\Validation\es\Rules;

use Respect\Validation\Rules\AbstractRule;
use Respect\Validation\Validator as V;

class Password extends AbstractRule
{
    public function validate($input)
    {
        $res = V::alnum()->noWhitespace()->length(4, 16)->validate($input);
        return $res;
    }
}