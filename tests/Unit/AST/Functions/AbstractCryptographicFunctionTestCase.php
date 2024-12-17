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

namespace Aeliot\DoctrineEncrypted\Query\Tests\Unit\AST\Functions;

use Aeliot\DoctrineEncrypted\Contracts\CryptographicSQLFunctionNameProviderInterface as FuncProviderInterface;
use Aeliot\DoctrineEncrypted\Contracts\CryptographicSQLFunctionWrapper;
use PHPUnit\Framework\TestCase;

abstract class AbstractCryptographicFunctionTestCase extends TestCase
{
    protected const FUNC_DECRYPT = 'decrypt';
    protected const FUNC_ENCRYPT = 'encrypt';

    protected function setUp(): void
    {
        $functionNameProvider = new class(self::FUNC_ENCRYPT, self::FUNC_DECRYPT) implements FuncProviderInterface {
            public function __construct(
                private readonly string $encryptFunctionName,
                private readonly string $decryptFunctionName,
            ) {
            }

            public function getDecryptFunctionName(): string
            {
                return $this->decryptFunctionName;
            }

            public function getEncryptFunctionName(): string
            {
                return $this->encryptFunctionName;
            }
        };

        CryptographicSQLFunctionWrapper::setFunctionNameProvider($functionNameProvider);
    }
}
