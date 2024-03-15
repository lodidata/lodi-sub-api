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
class ChineseException extends \Utils\Validation\BaseValidationException
{
    protected $name = 'nombre';

    public static $defaultTemplates = [
        self::MODE_DEFAULT => [
            self::STANDARD => '{{name}}  el formato es incorrecto, por favor vuelva a ingresar',
        ],
        self::MODE_NEGATIVE => [
            self::STANDARD => '{{name}}  el formato es incorrecto, por favor vuelva a ingresar',
        ],
    ];
}