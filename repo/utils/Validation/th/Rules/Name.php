<?php

namespace Utils\Validation\th\Rules;

use Respect\Validation\Rules\AbstractRule;
use Respect\Validation\Validator as V;

class Name extends AbstractRule
{
    public function validate($input)
    {
        $input = trim($input);
        
        $res = V::length(2, 30)->validate($input);
        return $res;
    }
}