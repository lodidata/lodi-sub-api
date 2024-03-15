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
class MxnUsernameException extends \Utils\Validation\BaseValidationException
{
    public $name = 'username';

    public static $defaultTemplates = [
        self::MODE_DEFAULT => [
            self::STANDARD => '{{name}} incorrect format, please use English or numbers, and the length is 6 to 15 digits',
        ],

    ];

}