<?php

/*
 * This file is part of Respect/Validation.
 *
 * (c) Alexandre Gomes Gaigalas <alexandre@gaigalas.net>
 *
 * For the full copyright and license information, please view the "LICENSE.md"
 * file that was distributed with this source code.
 */

namespace Utils\Validation\en\Exceptions;
class PasswordException extends \Utils\Validation\BaseValidationException
{
    protected $name = 'Password';

    public static $defaultTemplates = [
        self::MODE_DEFAULT => [
            self::STANDARD => '{{name}} incorrect format, please use English or numbers, and the length is 4 to 16 digits',
        ],
        self::MODE_NEGATIVE => [
            self::STANDARD => '{{name}} incorrect format, please use English or numbers, and the length is 4 to 16 digits',
        ],
    ];

}