<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Expressions;

use LogicException;
use Somnambulist\Components\QueryBuilder\Builder\ExpressionInterface;
use Somnambulist\Components\QueryBuilder\Builder\Expressions\WhenThenExpression;
use Somnambulist\Components\QueryBuilder\Builder\Type\AbstractQuery as Query;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use function is_string;
use function sprintf;

class WhenThenCompiler extends AbstractCompiler
{
    public function supports(mixed $expression): bool
    {
        return $expression instanceof WhenThenExpression;
    }

    public function compile(mixed $expression, ValueBinder $binder): string
    {
        /** @var WhenThenExpression $expression */
        if (is_null($expression->getWhen())) {
            throw new LogicException('Case expression has incomplete when clause. Missing "when()".');
        }

        if (!$expression->hasThenBeenDefined()) {
            throw new LogicException('Case expression has incomplete when clause. Missing "then()" after "when()".');
        }

        $when = $expression->getWhen();
        $whenType = $expression->getWhenType();

        if (is_string($whenType) && !$when instanceof ExpressionInterface) {
            $when = $this->castToExpression($when, $whenType);
        }

        if ($when instanceof Query) {
            $when = sprintf('(%s)', $this->expressionCompiler->compile($when, $binder));
        } elseif ($when instanceof ExpressionInterface) {
            $when = $this->expressionCompiler->compile($when, $binder);
        } else {
            $placeholder = $binder->placeholder('c');

            if (!is_string($whenType)) {
                $whenType = null;
            }

            $binder->bind($placeholder, $when, $whenType);
            $when = $placeholder;
        }

        $then = $this->compileNullableValue($binder, $expression->getThen(), $expression->getThenType());

        return sprintf('WHEN %s THEN %s', $when, $then);
    }
}
