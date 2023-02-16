<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Expressions;

use Somnambulist\Components\QueryBuilder\Builder\ExpressionInterface;
use Somnambulist\Components\QueryBuilder\Builder\Expressions\ValuesExpression;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use function array_fill_keys;
use function implode;
use function is_int;
use function is_string;
use function sprintf;

class ValuesCompiler extends AbstractCompiler
{
    public function supports(mixed $expression): bool
    {
        return $expression instanceof ValuesExpression;
    }

    public function compile(mixed $expression, ValueBinder $binder): string
    {
        /** @var ValuesExpression $expression */
        if (empty($expression->getValues()) && empty($expression->getQuery())) {
            return '';
        }

        $values = $expression->getValues();
        $columns = $expression->getColumnNames();
        $typeMap = $expression->getTypeMap();
        $defaults = array_fill_keys($columns, null);
        $placeholders = [];
        $types = [];

        foreach ($defaults as $col => $v) {
            $types[$col] = $typeMap->type($col);
        }

        foreach ($values as $row) {
            $row += $defaults;
            $rowPlaceholders = [];

            foreach ($columns as $column) {
                $value = $row[$column];

                if ($value instanceof ExpressionInterface) {
                    $rowPlaceholders[] = sprintf('(%s)', $this->expressionCompiler->compile($value, $binder));
                    continue;
                }

                $placeholder = $binder->placeholder('c');
                $rowPlaceholders[] = $placeholder;
                $binder->bind($placeholder, $value, $types[$column]);
            }

            $placeholders[] = implode(', ', $rowPlaceholders);
        }

        if (null !== $query = $expression->getQuery()) {
            return ' ' . $this->expressionCompiler->compile($query, $binder);
        }

        return sprintf(' VALUES (%s)', implode('), (', $placeholders));
    }
}
