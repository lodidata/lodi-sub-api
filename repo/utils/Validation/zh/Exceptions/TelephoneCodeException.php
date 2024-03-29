<?php

/*
 * This file is part of Respect/Validation.
 *
 * (c) Alexandre Gomes Gaigalas <alexandre@gaigalas.net>
 *
 * For the full copyright and license information, please view the "LICENSE.md"
 * file that was distributed with this source code.
 */

namespace Utils\Validation\es\Exceptions;
class TelephoneCodeException extends \Utils\Validation\BaseValidationException
{
    protected $name = '区号';

    public static $defaultTemplates = [
        self::MODE_DEFAULT => [
            self::STANDARD => '{{name}}格式不正确',
        ],
        self::MODE_NEGATIVE => [
            self::STANDARD => '{{name}}格式不正确',
        ],
    ];

}