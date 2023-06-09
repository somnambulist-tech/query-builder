<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Listeners;

use RuntimeException;
use Somnambulist\Components\QueryBuilder\Compiler\Events\PreDeleteQueryCompile;
use Somnambulist\Components\QueryBuilder\Compiler\Events\PreUpdateQueryCompile;
use Somnambulist\Components\QueryBuilder\Query\Expression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\ComparisonExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\IdentifierExpression;

class StripAliasesFromConditions
{
    public function __invoke(PreUpdateQueryCompile|PreDeleteQueryCompile $event): mixed
    {
        if ($event->query->clause('join')) {
            throw new RuntimeException(
                'Aliases are being removed from conditions for UPDATE/DELETE queries, this can break references to joined tables.'
            );
        }

        $conditions = $event->query->clause('where');

        assert($conditions === null || $conditions instanceof Expression);

        $conditions?->traverse(function ($expression) {
            if ($expression instanceof ComparisonExpression) {
                $field = $expression->getField();

                if (is_string($field) && str_contains($field, '.')) {
                    [, $unaliasedField] = explode('.', $field, 2);
                    $expression->field($unaliasedField);
                }

                return $expression;
            }

            if ($expression instanceof IdentifierExpression) {
                $identifier = $expression->getIdentifier();

                if (str_contains($identifier, '.')) {
                    [, $unaliasedIdentifier] = explode('.', $identifier, 2);
                    $expression->identifier($unaliasedIdentifier);
                }

                return $expression;
            }

            return $expression;
        });

        return $event;
    }
}
