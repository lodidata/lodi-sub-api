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
class ChinaMobileException extends \Utils\Validation\BaseValidationException
{
    protected $name = 'The mobile phone number';

    public static $defaultTemplates = [
        self::MODE_DEFAULT => [
            self::STANDARD => '{{name}} format is incorrect, please re-enter',
        ],
        self::MODE_NEGATIVE => [
            self::STANDARD => '{{name}} format is incorrect, please re-enter',
        ],
    ];
}