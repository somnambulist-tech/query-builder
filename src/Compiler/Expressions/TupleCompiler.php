<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Expressions;

use Somnambulist\Components\QueryBuilder\Query\ExpressionInterface;
use Somnambulist\Components\QueryBuilder\Query\Expressions\TupleComparison;
use Somnambulist\Components\QueryBuilder\ValueBinder;

class TupleCompiler extends AbstractCompiler
{
    public function supports(mixed $expression): bool
    {
        return $expression instanceof TupleComparison;
    }

    public function compile(mixed $expression, ValueBinder $binder): string
    {
        /** @var TupleComparison $expression */
        $template = '(%s) %s (%s)';
        $fields = [];
        $originalFields = $expression->getField();

        if (!is_array($originalFields)) {
            $originalFields = [$originalFields];
        }

        foreach ($originalFields as $field) {
            $fields[] = $field instanceof ExpressionInterface ? $this->expressionCompiler->compile($field, $binder) : $field;
        }

        $values = $this->stringifyValues($expression, $binder);
        $field = implode(', ', $fields);

        return sprintf($template, $field, $expression->getOperator(), $values);
    }

    protected function stringifyValues(TupleComparison $expression, ValueBinder $binder): string
    {
        $values = [];
        $parts = $expression->getValue();

        if ($parts instanceof ExpressionInterface) {
            return $this->expressionCompiler->compile($parts, $binder);
        }

        foreach ($parts as $i => $value) {
            if ($value instanceof ExpressionInterface) {
                $values[] = $this->expressionCompiler->compile($value, $binder);
                continue;
            }

            $type = $expression->getTypes();
            $isMultiOperation = $expression->isMulti();

            if (empty($type)) {
                $type = null;
            }

            if ($isMultiOperation) {
                $bound = [];

                foreach ($value as $k => $val) {
                    $valType = $type && isset($type[$k]) ? $type[$k] : $type;
                    assert($valType === null || is_scalar($valType));
                    $bound[] = $this->bindValue($val, $binder, $valType);
                }

                $values[] = sprintf('(%s)', implode(',', $bound));
                continue;
            }

            $valType = $type && isset($type[$i]) ? $type[$i] : $type;
            assert($valType === null || is_scalar($valType));
            $values[] = $this->bindValue($value, $binder, $valType);
        }

        return implode(', ', $values);
    }

    protected function bindValue(mixed $value, ValueBinder $binder, ?string $type = null): string
    {
        $placeholder = $binder->placeholder('tuple');
        $binder->bind($placeholder, $value, $type);

        return $placeholder;
    }
}
