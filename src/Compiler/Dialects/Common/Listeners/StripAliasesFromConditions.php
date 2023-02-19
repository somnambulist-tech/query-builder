<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Listeners;

use RuntimeException;
use Somnambulist\Components\QueryBuilder\Compiler\Events\PreQueryCompile;
use Somnambulist\Components\QueryBuilder\Query\ExpressionInterface;
use Somnambulist\Components\QueryBuilder\Query\Expressions\ComparisonExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\IdentifierExpression;

class StripAliasesFromConditions
{
    public function __invoke(PreQueryCompile $event): PreQueryCompile
    {
        if (!in_array($event->query->getType(), ['update', 'delete'])) {
            return $event;
        }

        if ($event->query->clause('join')) {
            throw new RuntimeException(
                'Aliases are being removed from conditions for UPDATE/DELETE queries, this can break references to joined tables.'
            );
        }

        $conditions = $event->query->clause('where');

        assert($conditions === null || $conditions instanceof ExpressionInterface);

        $conditions?->traverse(function ($expression) {
            if ($expression instanceof ComparisonExpression) {
                $field = $expression->getField();

                if (is_string($field) && str_contains($field, '.')) {
                    [, $unaliasedField] = explode('.', $field, 2);
                    $expression->setField($unaliasedField);
                }

                return $expression;
            }

            if ($expression instanceof IdentifierExpression) {
                $identifier = $expression->getIdentifier();

                if (str_contains($identifier, '.')) {
                    [, $unaliasedIdentifier] = explode('.', $identifier, 2);
                    $expression->setIdentifier($unaliasedIdentifier);
                }

                return $expression;
            }

            return $expression;
        });

        return $event;
    }
}
