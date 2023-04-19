<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Tests\Executors\Adapters;

use PDO;
use PHPUnit\Framework\TestCase;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Sqlite\CompilerConfigurator;
use Somnambulist\Components\QueryBuilder\Executors\Adapters\PdoAdapter;
use Somnambulist\Components\QueryBuilder\Executors\ExecutableDeleteQuery;
use Somnambulist\Components\QueryBuilder\Executors\ExecutableInsertQuery;
use Somnambulist\Components\QueryBuilder\Executors\ExecutableSelectQuery;
use Somnambulist\Components\QueryBuilder\Executors\ExecutableUpdateQuery;

class PdoAdapterTest extends TestCase
{
    public function testAdapter()
    {
        $adapter = new PdoAdapter(
            new PDO('sqlite::memory:'),
            (new CompilerConfigurator())->configure(),
        );

        $this->assertInstanceOf(ExecutableSelectQuery::class, $adapter->select());
        $this->assertInstanceOf(ExecutableInsertQuery::class, $adapter->insert());
        $this->assertInstanceOf(ExecutableUpdateQuery::class, $adapter->update());
        $this->assertInstanceOf(ExecutableDeleteQuery::class, $adapter->delete());
    }

    public function testExecute()
    {
        $adapter = new PdoAdapter(
            $conn = new PDO('sqlite::memory:'),
            (new CompilerConfigurator())->configure(),
        );

        $conn->exec('create table users (id integer, name varchar(100))');
        $conn->exec('insert into users values (1, \'bob\'), (2, \'fred\')');

        $results = $adapter->select('*')->from('users')->execute();
        $this->assertIsArray($results);
        $this->assertCount(2, $results);
    }
}
