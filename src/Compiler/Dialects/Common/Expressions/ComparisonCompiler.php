<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions;

use Somnambulist\Components\QueryBuilder\Compiler\AbstractCompiler;
use Somnambulist\Components\QueryBuilder\Exceptions\InvalidValueDuringQueryCompilation;
use Somnambulist\Components\QueryBuilder\Query\Expression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\ComparisonExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\IdentifierExpression;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use function assert;
use function is_array;
use function is_string;
use function sprintf;

class ComparisonCompiler extends AbstractCompiler
{
    public function compile(mixed $expression, ValueBinder $binder): string
    {
        /** @var ComparisonExpression $expression */
        $field = $expression->getField();

        if ($field instanceof Expression) {
            $field = $this->compiler->compile($field, $binder);
        }

        if ($expression->getValue() instanceof IdentifierExpression) {
            $template = '%s %s %s';
            $value = $this->compiler->compile($expression->getValue(), $binder);
        } elseif ($expression->getValue() instanceof Expression) {
            $template = '%s %s (%s)';
            $value = $this->compiler->compile($expression->getValue(), $binder);
        } else {
            [$template, $value] = $this->stringExpression($expression, $binder);
        }

        assert(is_string($field));

        return sprintf($template, $field, $expression->getOperator(), $value);
    }

    protected function stringExpression(ComparisonExpression $expression, ValueBinder $binder): array
    {
        $template = '%s ';
        $field = $expression->getField();

        if ($field instanceof Expression && !$field instanceof IdentifierExpression) {
            $template = '(%s) ';
        }

        if ($expression->isMultiple()) {
            $template .= '%s (%s)';
            $type = $expression->getType();

            if ($type !== null) {
                $type = str_replace('[]', '', $type);
            }

            $value = $this->flattenValue($expression, $binder, $type);

            // To avoid SQL errors when comparing a field to a list of empty values, better just throw an exception here
            if ($value === '') {
                $field = $field instanceof Expression ? $this->compiler->compile($field, $binder) : $field;
                /** @psalm-suppress PossiblyInvalidCast */
                throw InvalidValueDuringQueryCompilation::emptyValueForField($field);
            }
        } else {
            $template .= '%s %s';
            $value = $this->bindValue($expression->getValue(), $binder, $expression->getType());
        }

        return [$template, $value];
    }

    protected function bindValue(mixed $value, ValueBinder $binder, ?string $type = null): string
    {
        $placeholder = $binder->placeholder('c');
        $binder->bind($placeholder, $value, $type);

        return $placeholder;
    }

    /**
     * Converts a traversable value into a set of placeholders generated by $binder and separated by `,`
     */
    protected function flattenValue(ComparisonExpression $expression, ValueBinder $binder, ?string $type = null): string
    {
        $parts = [];
        $value = $expression->getValue();

        if (is_array($value)) {
            foreach ($expression->getValueExpressions() as $k => $v) {
                $parts[$k] = $this->compiler->compile($v, $binder);
                unset($value[$k]);
            }
        }

        if (!empty($value)) {
            $parts += $binder->generateManyNamedPlaceholders($value, $type);
        }

        return implode(',', $parts);
    }
}
