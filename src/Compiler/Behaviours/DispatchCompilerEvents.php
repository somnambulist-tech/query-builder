<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Behaviours;

use Psr\EventDispatcher\EventDispatcherInterface;
use Somnambulist\Components\QueryBuilder\Query\Query;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use function class_exists;
use function sprintf;
use function ucfirst;

trait DispatchCompilerEvents
{
    use GenerateEventForExpression;

    protected ?EventDispatcherInterface $dispatcher = null;

    public function setDispatcher(EventDispatcherInterface $dispatcher): void
    {
        $this->dispatcher = $dispatcher;
    }

    protected function preCompile(string $partName, mixed $expression, Query $query, ValueBinder $binder): ?string
    {
        $event = sprintf('Somnambulist\Components\QueryBuilder\Compiler\Events\Pre%sExpressionCompile', ucfirst($partName));

        if (class_exists($event)) {
            $event = $this->dispatcher->dispatch(new $event($expression, $query, $binder));

            return $event->getRevisedSql() ?: null;
        }

        return null;
    }

    protected function postCompile(string $partName, string $sql, Query $query, ValueBinder $binder): string
    {
        $event = sprintf('Somnambulist\Components\QueryBuilder\Compiler\Events\Post%sExpressionCompile', ucfirst($partName));

        if (class_exists($event)) {
            return $this->dispatcher->dispatch(new $event($sql, $query, $binder))->getRevisedSql();
        }

        return $sql;
    }
}
