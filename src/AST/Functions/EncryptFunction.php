<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Encrypted Query.
 *
 * (c) Anatoliy Melnikov <5785276@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Aeliot\DoctrineEncrypted\Query\AST\Functions;

use Aeliot\DoctrineEncrypted\Query\Enum\FunctionEnum;

final class EncryptFunction extends AbstractSingleArgumentFunction
{
    public static function getSupportedFunctionName(): string
    {
        return self::hasFunctionNameProvider()
            ? self::getFunctionNameProvider()->getEncryptFunctionName()
            : self::getDefaultFunctionName();
    }

    protected static function getDefaultFunctionName(): string
    {
        return FunctionEnum::ENCRYPT;
    }
}
