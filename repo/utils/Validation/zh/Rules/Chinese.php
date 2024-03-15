<?php

namespace Utils\Validation\zh\Rules;

use Respect\Validation\Rules\AbstractRule;
use Respect\Validation\Validator as V;
class Chinese extends AbstractRule
{
    public function validate($input)
    {
        $input = trim($input);
        return V::regex('/^[\x{4e00}-\x{9fa5}]+$/u')->noWhitespace()->length(2, 20)->validate($input);

        /*$bool1 = V::regex("/[`~!@#$%^&*()_\-+=<>?:\"{}|,.\/;'\\[\]·~！@#￥%……&*（）——\-+={}|《》？：“”【】、；‘'，。、]+/is")->validate($input);
        if(true === $bool1){
            return false;
        }

        return V::length(2, 20)->validate($input);*/
    }
}