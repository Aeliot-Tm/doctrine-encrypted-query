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

namespace Aeliot\DoctrineEncrypted\Query;

use Aeliot\DoctrineEncrypted\Contracts\CryptographicSQLFunctionWrapper;
use Aeliot\DoctrineEncrypted\Types\Enum\TypeEnum;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\AST\ArithmeticExpression;
use Doctrine\ORM\Query\AST\ComparisonExpression;
use Doctrine\ORM\Query\AST\InputParameter;
use Doctrine\ORM\Query\AST\Literal;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\AST\NullComparisonExpression;
use Doctrine\ORM\Query\AST\PathExpression;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\Query\SqlWalker;

class EncryptionSQLWalker extends SqlWalker
{
    /**
     * @var array<string,string>
     */
    private array $parametersWithAdditionalEncryption = [];

    /**
     * @var array<string,object>
     */
    private array $pathExpressionsWithSkippedDecryption = [];

    /**
     * @param ComparisonExpression $compExpr
     */
    public function walkComparisonExpression($compExpr): string
    {
        $this->processComparisonOfArithmeticExpressions($compExpr);
        $sql = parent::walkComparisonExpression($compExpr);
        $this->clearComparisonExpressionWalkingCache();

        return $sql;
    }

    /**
     * @param InputParameter $inputParam
     */
    public function walkInputParameter($inputParam): string
    {
        $inputParameter = parent::walkInputParameter($inputParam);

        if (
            $inputParam->isNamed
            && \in_array($inputParam->name, $this->parametersWithAdditionalEncryption, true)
        ) {
            $inputParameter = CryptographicSQLFunctionWrapper::getEncryptSQLExpression($inputParameter);
        }

        return $inputParameter;
    }

    /**
     * @param NullComparisonExpression $nullCompExpr
     */
    public function walkNullComparisonExpression($nullCompExpr): string
    {
        $this->processNullComparisonExpression($nullCompExpr);
        $sql = parent::walkNullComparisonExpression($nullCompExpr);
        $this->clearComparisonExpressionWalkingCache();

        return $sql;
    }

    /**
     * @param PathExpression $pathExpr
     */
    public function walkPathExpression($pathExpr): string
    {
        $sql = parent::walkPathExpression($pathExpr);

        if (PathExpression::TYPE_STATE_FIELD === $pathExpr->type) {
            $pathExpressionHash = spl_object_hash($pathExpr);

            if (!\array_key_exists($pathExpressionHash, $this->pathExpressionsWithSkippedDecryption)) {
                $sql = $this->getDecryptedPathExpression($sql, $pathExpr->identificationVariable, $pathExpr->field);
            }
        }

        return $sql;
    }

    private function clearComparisonExpressionWalkingCache(): void
    {
        $this->parametersWithAdditionalEncryption = [];
        $this->pathExpressionsWithSkippedDecryption = [];
    }

    private function getDecryptedPathExpression(string &$sql, string $dqlAlias, ?string $fieldName = null): string
    {
        if ($fieldName && \array_key_exists($dqlAlias, $this->getQueryComponents())) {
            /** @var ClassMetadata<object> $metadata */
            $metadata = $this->getQueryComponent($dqlAlias)['metadata'];

            if (
                ($fieldMapping = $this->getFieldMapping($metadata, $fieldName))
                && \in_array($fieldMapping['type'], TypeEnum::all(), true)
            ) {
                $sql = CryptographicSQLFunctionWrapper::getDecryptSQLExpression($sql);
            }
        }

        return $sql;
    }

    private function getExpressionFieldType(Node $expression): ?string
    {
        $metadata = $this->getExpressionMetadata($expression);
        if ($metadata && ($fieldName = $expression->field ?? null)) {
            return $this->getFieldType($metadata, $fieldName);
        }

        return null;
    }

    /**
     * @return ClassMetadata<object>|null
     */
    private function getExpressionMetadata(Node $expression): ?ClassMetadata
    {
        if ($expression instanceof PathExpression) {
            return $this->getQueryComponent($expression->identificationVariable)['metadata'];
        }

        return null;
    }

    /**
     * @param ClassMetadata<object> $metadata
     *
     * @return array<array-key,mixed>|null
     */
    private function getFieldMapping(ClassMetadata $metadata, string $fieldName): ?array
    {
        return $metadata->hasField($fieldName) ? $metadata->getFieldMapping($fieldName) : null;
    }

    /**
     * @param ClassMetadata<object> $metadata
     */
    private function getFieldType(ClassMetadata $metadata, string $fieldName): ?string
    {
        return ($fieldMapping = $this->getFieldMapping($metadata, $fieldName)) ? $fieldMapping['type'] : null;
    }

    private function isExpressionEncrypted(Node $expression): bool
    {
        if ($expression instanceof Literal) {
            return false;
        }

        return \in_array($this->getExpressionFieldType($expression), TypeEnum::all(), true);
    }

    /**
     * @param ComparisonExpression $compExpr
     */
    private function processComparisonOfArithmeticExpressions($compExpr): void
    {
        if (
            !$compExpr->leftExpression instanceof ArithmeticExpression
            || !$compExpr->rightExpression instanceof ArithmeticExpression
        ) {
            return;
        }

        /** @var Node|null $leftExpression */
        $leftExpression = $compExpr->leftExpression->simpleArithmeticExpression;
        /** @var Node|null $rightExpression */
        $rightExpression = $compExpr->rightExpression->simpleArithmeticExpression;

        if (!$leftExpression || !$rightExpression) {
            return;
        }

        $isLeftExpressionEncrypted = $this->isExpressionEncrypted($leftExpression);
        $isRightExpressionEncrypted = $this->isExpressionEncrypted($rightExpression);

        $leftExpressionHash = spl_object_hash($leftExpression);
        $rightExpressionHash = spl_object_hash($rightExpression);

        if ($isLeftExpressionEncrypted && $isRightExpressionEncrypted) {
            $this->pathExpressionsWithSkippedDecryption[$leftExpressionHash] = $leftExpression;
            $this->pathExpressionsWithSkippedDecryption[$rightExpressionHash] = $rightExpression;
        }

        if (\in_array($compExpr->operator, [Comparison::EQ, Comparison::NEQ], true)) {
            $this->processEqComparisonOfExpressions(
                $isLeftExpressionEncrypted,
                $leftExpression,
                $leftExpressionHash,
                $rightExpression
            );

            $this->processEqComparisonOfExpressions(
                $isRightExpressionEncrypted,
                $rightExpression,
                $rightExpressionHash,
                $leftExpression
            );
        }
    }

    private function processNullComparisonExpression(NullComparisonExpression $nullCompExpr): void
    {
        /** @var PathExpression|null $expression */
        $expression = $nullCompExpr->expression;
        if (!$expression) {
            return;
        }

        if ($this->isExpressionEncrypted($expression)) {
            $this->pathExpressionsWithSkippedDecryption[spl_object_hash($expression)] = $expression;
        }
    }

    private function processEqComparisonOfExpressions(
        bool $isFirstExpressionEncrypted,
        Node $firstExpression,
        string $firstExpressionHash,
        Node $secondExpression
    ): void {
        if (!$isFirstExpressionEncrypted || !$secondExpression instanceof InputParameter) {
            return;
        }

        $this->pathExpressionsWithSkippedDecryption[$firstExpressionHash] = $firstExpression;

        if ($secondExpression->isNamed) {
            $secondExpressionName = $secondExpression->name;
            $this->parametersWithAdditionalEncryption[$secondExpressionName] = $secondExpressionName;
        }
    }
}
