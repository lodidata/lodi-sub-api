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
class PasswordException extends \Utils\Validation\BaseValidationException
{
    protected $name = 'Contraseña';

    public static $defaultTemplates = [
        self::MODE_DEFAULT => [
            self::STANDARD => '{{name}} formato incorrecto, por favor en inglés o números, y una longitud de 6 a 15 dígitos',
        ],
        self::MODE_NEGATIVE => [
            self::STANDARD => '{{name}} formato incorrecto, por favor en inglés o números, y una longitud de 6 a 15 dígitos',
        ],
    ];

}