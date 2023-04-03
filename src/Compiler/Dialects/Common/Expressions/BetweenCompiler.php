<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions;

use Somnambulist\Components\QueryBuilder\Compiler\AbstractCompiler;
use Somnambulist\Components\QueryBuilder\Query\Expression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\BetweenExpression;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use function assert;
use function is_string;
use function sprintf;

class BetweenCompiler extends AbstractCompiler
{
    public function compile(mixed $expression, ValueBinder $binder): string
    {
        /** @var BetweenExpression $expression */
        $parts = [
            'from' => $expression->getFrom(),
            'to' => $expression->getTo(),
        ];

        $field = $expression->getField();

        if ($field instanceof Expression) {
            $field = $this->compiler->compile($field, $binder);
        }

        foreach ($parts as $name => $part) {
            if ($part instanceof Expression) {
                $parts[$name] = $this->compiler->compile($part, $binder);
                continue;
            }

            $binder->bind($placeholder = $binder->placeholder('c'), $part, $expression->getType());
            $parts[$name] = $placeholder;
        }

        assert(is_string($field));

        return sprintf('%s BETWEEN %s AND %s', $field, $parts['from'], $parts['to']);
    }
}
