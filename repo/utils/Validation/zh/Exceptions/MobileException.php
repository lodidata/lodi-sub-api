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
    protected $name = '手机号码';

    public static $defaultTemplates = [
        self::MODE_DEFAULT => [
            self::STANDARD => '
            {{name}}格式不正确，请重新输入',
        ],
        self::MODE_NEGATIVE => [
            self::STANDARD => '{{name}}格式不正确，请重新输入',
        ],
    ];

}