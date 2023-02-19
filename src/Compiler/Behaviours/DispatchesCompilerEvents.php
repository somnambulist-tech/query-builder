<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Behaviours;

use Psr\EventDispatcher\EventDispatcherInterface;
use Somnambulist\Components\QueryBuilder\Query\Query;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use function class_exists;
use function dirname;
use function file_put_contents;
use function sprintf;
use function str_starts_with;
use function ucfirst;

trait DispatchesCompilerEvents
{
    protected ?EventDispatcherInterface $dispatcher = null;

    public function setDispatcher(EventDispatcherInterface $dispatcher): void
    {
        $this->dispatcher = $dispatcher;
    }

    protected function preCompile(string $partName, mixed $part, Query $query, ValueBinder $binder): void
    {
        $event = sprintf('Somnambulist\Components\QueryBuilder\Compiler\Events\Pre%sExpressionCompile', ucfirst($partName));

        if (class_exists($event)) {
            $this->dispatcher->dispatch(new $event($part, $query, $binder));
        }
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
