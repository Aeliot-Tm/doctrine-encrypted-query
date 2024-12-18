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
use Aeliot\DoctrineEncrypted\Query\AST\Functions\AbstractSingleArgumentFunction;
use Doctrine\ORM\Query\AST\SimpleArithmeticExpression;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
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

        AbstractSingleArgumentFunction::setFunctionNameProvider($functionNameProvider);
    }

    protected function mockParser(SimpleArithmeticExpression $simpleArithmeticExpression): Parser
    {
        $parser = $this->createMock(Parser::class);
        $parser->method('SimpleArithmeticExpression')->willReturn($simpleArithmeticExpression);

        $invokedCount = $this->exactly(3);
        $parser->expects($invokedCount)
            ->method('match')
            ->willReturnCallback(function ($token) use ($invokedCount) {
                switch ($invokedCount->numberOfInvocations()) {
                    case 1:
                        self::assertSame(Lexer::T_IDENTIFIER, $token);
                        break;
                    case 2:
                        self::assertSame(Lexer::T_OPEN_PARENTHESIS, $token);
                        break;
                    case 3:
                        self::assertSame(Lexer::T_CLOSE_PARENTHESIS, $token);
                        break;
                }
            });

        return $parser;
    }
}
