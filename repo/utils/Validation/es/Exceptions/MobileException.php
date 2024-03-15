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
class MobileException extends \Utils\Validation\BaseValidationException
{
    protected $name = 'El número de teléfono móvil';

    public static $defaultTemplates = [
        self::MODE_DEFAULT => [
            self::STANDARD => '{{name}} el formato es incorrecto, por favor vuelva a ingresar',
        ],
        self::MODE_NEGATIVE => [
            self::STANDARD => '{{name}} el formato es incorrecto, por favor vuelva a ingresar',
        ],
    ];

}