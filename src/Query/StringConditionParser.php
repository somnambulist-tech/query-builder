<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Query;

use InvalidArgumentException;
use Somnambulist\Components\QueryBuilder\Query\Expressions\ComparisonExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\IdentifierExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\UnaryExpression;
use Somnambulist\Components\QueryBuilder\TypeMap;
use function array_pop;
use function explode;
use function implode;
use function in_array;
use function is_string;
use function preg_match;
use function sprintf;
use function str_contains;
use function strtoupper;
use function substr_count;
use function trim;

class StringConditionParser
{
    public function __construct(
        protected TypeMap $typeMap,
        protected string $conjunction = 'AND'
    ) {
    }

    public function setTypeMap(TypeMap $typeMap): self
    {
        $this->typeMap = $typeMap;

        return $this;
    }

    public function setConjunction(string $conjunction): self
    {
        $this->conjunction = $conjunction;

        return $this;
    }

    /**
     * Parses a string of conditions by trying to extract the operator inside it if any.
     *
     * Will return either an adequate QueryExpression object or a plain string representation of the condition.
     * This class is responsible for generating the placeholders and replacing the values by them, while storing
     * the value elsewhere for future binding.
     *
     * @param string $condition
     * @param mixed $value
     *
     * @return Expression|string
     */
    public function parse(string $condition, mixed $value): Expression|string
    {
        $expression = trim($condition);
        $operator = '=';

        $spaces = substr_count($expression, ' ');
        // Handle expression values that contain multiple spaces, such as
        // operators with a space in them like `field IS NOT` and
        // `field NOT LIKE`, or combinations with function expressions
        // like `CONCAT(first_name, ' ', last_name) IN`.
        if ($spaces > 1) {
            $parts = explode(' ', $expression);
            if (preg_match('/(is not|not \w+)$/i', $expression)) {
                $last = array_pop($parts);
                $second = array_pop($parts);
                $parts[] = "$second $last";
            }
            $operator = array_pop($parts);
            $expression = implode(' ', $parts);
        } elseif ($spaces == 1) {
            $parts = explode(' ', $expression, 2);
            [$expression, $operator] = $parts;
        }
        $operator = strtoupper(trim($operator));

        $type = $this->typeMap->type($expression);
        $typeMultiple = (is_string($type) && str_contains($type, '[]'));

        if (in_array($operator, ['IN', 'NOT IN']) || $typeMultiple) {
            $type = $type ?: 'string';
            if (!$typeMultiple) {
                $type .= '[]';
            }
            $operator = $operator === '=' ? 'IN' : $operator;
            $operator = $operator === '!=' ? 'NOT IN' : $operator;
            $typeMultiple = true;
        }

        /** @psalm-suppress RedundantCondition */
        if ($typeMultiple) {
            $value = $value instanceof Expression ? $value : (array)$value;
        }

        if ($operator === 'IS' && $value === null) {
            return new UnaryExpression(
                'IS NULL',
                new IdentifierExpression($expression),
                UnaryExpression::POSTFIX
            );
        }

        if ($operator === 'IS NOT' && $value === null) {
            return new UnaryExpression(
                'IS NOT NULL',
                new IdentifierExpression($expression),
                UnaryExpression::POSTFIX
            );
        }

        if ($operator === 'IS' && $value !== null) {
            $operator = '=';
        }

        if ($operator === 'IS NOT' && $value !== null) {
            $operator = '!=';
        }

        if ($value === null && $this->conjunction !== ',') {
            throw new InvalidArgumentException(
                sprintf('Expression "%s" is missing operator (IS, IS NOT) with "null" value.', $expression)
            );
        }

        return new ComparisonExpression($expression, $value, $type, $operator);
    }
}
