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

use Aeliot\DoctrineEncrypted\Contracts\CryptographicSQLFunctionNameProviderInterface;
use Aeliot\DoctrineEncrypted\Contracts\CryptographicSQLFunctionNameProviderNotConfiguredException;
use Aeliot\DoctrineEncrypted\Query\Exception\ConfigurationException;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\SimpleArithmeticExpression;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

/**
 * Representation of cryptographic SQL function AST.
 *
 * It supports structure:
 * - CryptographicSQLFunctionName
 * - symbol "("
 * - SimpleArithmeticExpression
 * - symbol ")"
 */
abstract class AbstractSingleArgumentFunction extends FunctionNode
{
    private static ?CryptographicSQLFunctionNameProviderInterface $functionNameProvider = null;

    public ?SimpleArithmeticExpression $simpleArithmeticExpression = null;

    public static function setFunctionNameProvider(
        CryptographicSQLFunctionNameProviderInterface $functionNameProvider
    ): void {
        self::$functionNameProvider = $functionNameProvider;
    }

    abstract public static function getSupportedFunctionName(): string;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        if (static::getSupportedFunctionName() !== $name) {
            throw new ConfigurationException(sprintf('Invalid function configuration "%s"', $name));
        }

        parent::__construct($name);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        return sprintf(
            '%s(%s)',
            static::getSupportedFunctionName(),
            $sqlWalker->walkSimpleArithmeticExpression($this->simpleArithmeticExpression)
        );
    }

    public function parse(Parser $parser): void
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->simpleArithmeticExpression = $parser->SimpleArithmeticExpression();

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    protected static function getFunctionNameProvider(): CryptographicSQLFunctionNameProviderInterface
    {
        if (!self::$functionNameProvider instanceof CryptographicSQLFunctionNameProviderInterface) {
            throw new CryptographicSQLFunctionNameProviderNotConfiguredException();
        }

        return self::$functionNameProvider;
    }
}
