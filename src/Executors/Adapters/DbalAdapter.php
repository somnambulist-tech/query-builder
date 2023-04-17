<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Executors\Adapters;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Somnambulist\Components\QueryBuilder\Compiler\DelegatingSqlCompiler;
use Somnambulist\Components\QueryBuilder\Executors\Behaviours\CreateSelfExecutingQueryObjects;
use Somnambulist\Components\QueryBuilder\Executors\ExecutableDeleteQuery;
use Somnambulist\Components\QueryBuilder\Executors\ExecutableInsertQuery;
use Somnambulist\Components\QueryBuilder\Executors\ExecutableSelectQuery;
use Somnambulist\Components\QueryBuilder\Executors\ExecutableUpdateQuery;
use Somnambulist\Components\QueryBuilder\Executors\QueryExecutor;
use Somnambulist\Components\QueryBuilder\Query\Query;
use Somnambulist\Components\QueryBuilder\Query\Type\DeleteQuery;
use Somnambulist\Components\QueryBuilder\Query\Type\InsertQuery;
use Somnambulist\Components\QueryBuilder\Query\Type\SelectQuery;
use Somnambulist\Components\QueryBuilder\Query\Type\UpdateQuery;
use Somnambulist\Components\QueryBuilder\TypeCasterManager;
use Somnambulist\Components\QueryBuilder\TypeCasters\DbalTypeCaster;
use Somnambulist\Components\QueryBuilder\ValueBinder;

/**
 * Doctrine DBAL adapter that makes integrating easier.
 *
 * Adapter returns instances of self-executing Query objects while ensuring that the type caster
 * is registered and the executable query types are registered to the compiler.
 */
class DbalAdapter implements QueryExecutor
{
    use CreateSelfExecutingQueryObjects;

    public function __construct(
        private Connection $conn,
        private DelegatingSqlCompiler $compiler,
    ) {
        $this->compiler->add(ExecutableSelectQuery::class, $this->compiler->get(SelectQuery::class));
        $this->compiler->add(ExecutableInsertQuery::class, $this->compiler->get(InsertQuery::class));
        $this->compiler->add(ExecutableUpdateQuery::class, $this->compiler->get(UpdateQuery::class));
        $this->compiler->add(ExecutableDeleteQuery::class, $this->compiler->get(DeleteQuery::class));

        if (!TypeCasterManager::isRegistered()) {
            TypeCasterManager::register(new DbalTypeCaster());
        }
    }

    public function compile(Query $query, ValueBinder $binder): string
    {
        return $this->compiler->compile($query, $binder);
    }

    /**
     * @param string $sql
     * @param array $params
     * @param array $types
     *
     * @return mixed|Result
     * @throws \Doctrine\DBAL\Exception
     */
    public function execute(string $sql, array $params = [], array $types = []): mixed
    {
        return $this->conn->executeQuery($sql, $params, $types);
    }
}
