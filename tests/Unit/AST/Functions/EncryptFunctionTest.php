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

use Aeliot\DoctrineEncrypted\Query\AST\Functions\EncryptFunction;
use Doctrine\ORM\Query\AST\SimpleArithmeticExpression;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

final class EncryptFunctionTest extends AbstractCryptographicFunctionTestCase
{
    public function testParse(): void
    {
        $function = new EncryptFunction(self::FUNC_ENCRYPT);

        $simpleArithmeticExpression = $this->createMock(SimpleArithmeticExpression::class);

        $parser = $this->createMock(Parser::class);
        $parser->method('SimpleArithmeticExpression')->willReturn($simpleArithmeticExpression);

        $parser->expects($this->exactly(3))
            ->method('match')
            ->withConsecutive(
                [Lexer::T_IDENTIFIER],
                [Lexer::T_OPEN_PARENTHESIS],
                [Lexer::T_CLOSE_PARENTHESIS],
            );

        $function->parse($parser);

        self::assertEquals($simpleArithmeticExpression, $function->simpleArithmeticExpression);
    }

    public function testGetSQL(): void
    {
        $sqlWalker = $this->createMock(SqlWalker::class);
        $sqlWalker->method('walkSimpleArithmeticExpression')->willReturn('expression');

        $function = new EncryptFunction(self::FUNC_ENCRYPT);
        self::assertEquals(
            sprintf('%s(%s)', self::FUNC_ENCRYPT, 'expression'),
            $function->getSql($sqlWalker)
        );
    }
}
