<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Type;

use Closure;
use Somnambulist\Components\QueryBuilder\Compiler\AbstractCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Behaviours\DispatchCompilerEvents;
use Somnambulist\Components\QueryBuilder\Compiler\Behaviours\GetCompilerForExpression;
use Somnambulist\Components\QueryBuilder\Compiler\Behaviours\IsCompilable;
use Somnambulist\Components\QueryBuilder\Compiler\DispatchesCompilerEvents;
use Somnambulist\Components\QueryBuilder\Query\Query;
use Somnambulist\Components\QueryBuilder\Query\Type\SelectQuery;
use Somnambulist\Components\QueryBuilder\ValueBinder;

class AbstractQueryCompiler extends AbstractCompiler implements DispatchesCompilerEvents
{
    use IsCompilable;
    use DispatchCompilerEvents;
    use GetCompilerForExpression;

    protected array $order = [];
    protected array $supports = [];

    public function compile(mixed $expression, ValueBinder $binder): string
    {
        /** @var SelectQuery $expression */
        $this->assertExpressionIsSupported($expression, $this->supports);

        $sql = '';

        $expression->traverseParts(
            $this->sqlCompiler($sql, $expression, $binder),
            $this->order
        );

        return $sql;
    }

    protected function sqlCompiler(string &$sql, Query $query, ValueBinder $binder): Closure
    {
        return function ($part, $partName) use (&$sql, $query, $binder): void {
            if ($this->isNotCompilable($part)) {
                return;
            }

            if (null !== $ret = $this->preCompile($partName, $part, $query, $binder)) {
                $sql .= $ret;
                $sql = $this->postCompile($partName, $sql, $query, $binder);

                return;
            }

            $sql .= $this->getCompiler($partName, $part)->compile($part, $binder);
            $sql = $this->postCompile($partName, $sql, $query, $binder);
        };
    }
}
