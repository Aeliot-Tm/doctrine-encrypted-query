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

namespace Aeliot\DoctrineEncrypted\Query\Enum;

final class FunctionEnum
{
    public const DECRYPT = 'AELIOT_DECRYPT';
    public const ENCRYPT = 'AELIOT_ENCRYPT';
}
