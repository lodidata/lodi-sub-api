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
    protected $name = 'Código de teléfono';

    public static $defaultTemplates = [
        self::MODE_DEFAULT => [
            self::STANDARD => '{{name}} formato incorrecto',
        ],
        self::MODE_NEGATIVE => [
            self::STANDARD => '{{name}} formato incorrecto',
        ],
    ];

}