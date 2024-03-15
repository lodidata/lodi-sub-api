<?php

/*
 * This file is part of Respect/Validation.
 *
 * (c) Alexandre Gomes Gaigalas <alexandre@gaigalas.net>
 *
 * For the full copyright and license information, please view the "LICENSE.md"
 * file that was distributed with this source code.
 */

namespace Utils\Validation\th\Exceptions;
class UsernameRegisterException extends \Utils\Validation\BaseValidationException
{
    public static $defaultTemplates = [
        self::MODE_DEFAULT => [
            self::STANDARD => '{{name}} ลงทะเบียน',
        ],
        self::MODE_NEGATIVE => [
            self::STANDARD => '{{name}} ยังไม่ได้ลงทะเบียน',
        ],
    ];

}
