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

use Aeliot\DoctrineEncrypted\Query\AST\Functions\DecryptFunction;
use Doctrine\ORM\Query\AST\SimpleArithmeticExpression;
use Doctrine\ORM\Query\SqlWalker;

final class DecryptFunctionTest extends AbstractCryptographicFunctionTestCase
{
    public function testParse(): void
    {
        $function = new DecryptFunction(self::FUNC_DECRYPT);

        $simpleArithmeticExpression = $this->createMock(SimpleArithmeticExpression::class);
        $parser = $this->mockParser($simpleArithmeticExpression);

        $function->parse($parser);

        self::assertEquals($simpleArithmeticExpression, $function->simpleArithmeticExpression);
    }

    public function testGetSQL(): void
    {
        $sqlWalker = $this->createMock(SqlWalker::class);
        $sqlWalker->method('walkSimpleArithmeticExpression')->willReturn('expression');

        $function = new DecryptFunction(self::FUNC_DECRYPT);
        self::assertEquals(
            sprintf('%s(%s)', self::FUNC_DECRYPT, 'expression'),
            $function->getSql($sqlWalker)
        );
    }
}
