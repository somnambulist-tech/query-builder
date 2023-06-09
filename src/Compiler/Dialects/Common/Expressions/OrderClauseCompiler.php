<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Expressions;

use Somnambulist\Components\QueryBuilder\Compiler\AbstractCompiler;
use Somnambulist\Components\QueryBuilder\Query\Expression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\OrderClauseExpression;
use Somnambulist\Components\QueryBuilder\Query\Query;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use function assert;
use function is_string;
use function sprintf;

class OrderClauseCompiler extends AbstractCompiler
{
    public function compile(mixed $expression, ValueBinder $binder): string
    {
        /** @var OrderClauseExpression $expression */
        $field = $expression->getField();

        if ($field instanceof Query) {
            $field = sprintf('(%s)', $this->compiler->compile($field, $binder));
        } elseif ($field instanceof Expression) {
            $field = $this->compiler->compile($field, $binder);
        }

        assert(is_string($field));

        return sprintf('%s %s', $field, $expression->getDirection()->value);
    }
}
