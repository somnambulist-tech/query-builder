<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Executors\Adapters;

use PDO;
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
use Somnambulist\Components\QueryBuilder\TypeCasters\StringTypeCaster;
use Somnambulist\Components\QueryBuilder\ValueBinder;

/**
 * PDO adapter for self executing queries
 *
 * Executes using prepared statements on the passed PDO instance. Statement is executed and all
 * results fetched using `PDO::FETCH_ASSOC`. If the query is unlimited this may generate execessive
 * memory usage. To change this behaviour, extend and re-implement the execution logic or implement
 * your own version.
 *
 * Note: as PDO does not have a "type" system per se, this adapter cannot handle custom types that
 * need to cast to expressions. You will need to implement your own type handling for this or use
 * another DB driver library to provide this for you.
 */
class PdoAdapter implements QueryExecutor
{
    use CreateSelfExecutingQueryObjects;

    public function __construct(
        private PDO $conn,
        private DelegatingSqlCompiler $compiler,
    ) {
        $this->compiler->add(ExecutableSelectQuery::class, $this->compiler->get(SelectQuery::class));
        $this->compiler->add(ExecutableInsertQuery::class, $this->compiler->get(InsertQuery::class));
        $this->compiler->add(ExecutableUpdateQuery::class, $this->compiler->get(UpdateQuery::class));
        $this->compiler->add(ExecutableDeleteQuery::class, $this->compiler->get(DeleteQuery::class));

        if (!TypeCasterManager::isRegistered()) {
            TypeCasterManager::register(new StringTypeCaster());
        }
    }

    public function getConnection(): PDO
    {
        return $this->conn;
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
     * @return mixed|array
     */
    public function execute(string $sql, array $params = [], array $types = []): mixed
    {
        $stmt = $this->conn->prepare($sql);

        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, $types[$k] ?? PDO::PARAM_STR);
        }

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
