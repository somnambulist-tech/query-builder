<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Builder\Expressions;

use Somnambulist\Components\QueryBuilder\Builder\ExpressionInterface;
use Somnambulist\Components\QueryBuilder\Builder\OrderDirection;
use Somnambulist\Components\QueryBuilder\Exceptions\InvalidValueForExpression;
use Somnambulist\Components\QueryBuilder\TypeMap;

/**
 * An expression object for ORDER BY clauses
 */
class OrderByExpression extends QueryExpression
{
    /**
     * @param ExpressionInterface|array|string $conditions The sort columns
     * @param TypeMap|null $types The types for each column.
     * @param string $conjunction The glue used to join conditions together.
     */
    public function __construct(
        ExpressionInterface|array|string $conditions = [],
        TypeMap $types = null,
        string $conjunction = ''
    ) {
        parent::__construct($conditions, $types, $conjunction);
    }

    /**
     * Auxiliary function used for decomposing a nested array of conditions and
     * building a tree structure inside this object to represent the full SQL expression.
     *
     * New order by expressions are merged to existing ones
     *
     * @param array $conditions list of order by expressions
     * @param array $types list of types associated on fields referenced in $conditions
     */
    protected function addConditions(array $conditions, array $types): void
    {
        foreach ($conditions as $key => $val) {
            if (is_string($key) && is_string($val) && !OrderDirection::isValid($val)) {
                throw InvalidValueForExpression::possibleSQLInjectionVulnerability($key, $val);
            }
        }

        $this->conditions = array_merge($this->conditions, $conditions);
    }
}
