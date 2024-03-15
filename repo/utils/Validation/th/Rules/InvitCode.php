<?php

namespace Utils\Validation\th\Rules;

use Respect\Validation\Rules\AbstractRule;
use Respect\Validation\Validator as V;
class InvitCode extends AbstractRule
{
    public function validate($input)
    {
        return V::noWhitespace()->length(6, 16)->validate($input);
    }
}