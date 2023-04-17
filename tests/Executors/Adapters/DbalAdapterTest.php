<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Tests\Executors\Adapters;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Result;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Sqlite\CompilerConfigurator;
use Somnambulist\Components\QueryBuilder\Executors\Adapters\DbalAdapter;
use PHPUnit\Framework\TestCase;
use Somnambulist\Components\QueryBuilder\Executors\ExecutableDeleteQuery;
use Somnambulist\Components\QueryBuilder\Executors\ExecutableInsertQuery;
use Somnambulist\Components\QueryBuilder\Executors\ExecutableSelectQuery;
use Somnambulist\Components\QueryBuilder\Executors\ExecutableUpdateQuery;
use Somnambulist\Components\QueryBuilder\Query\Type\SelectQuery;

class DbalAdapterTest extends TestCase
{
    public function testDbalAdapter()
    {
        $adapter = new DbalAdapter(
            DriverManager::getConnection(['url' => 'sqlite:///:memory:']),
            (new CompilerConfigurator())->configure(),
        );

        $this->assertInstanceOf(ExecutableSelectQuery::class, $adapter->select());
        $this->assertInstanceOf(ExecutableInsertQuery::class, $adapter->insert());
        $this->assertInstanceOf(ExecutableUpdateQuery::class, $adapter->update());
        $this->assertInstanceOf(ExecutableDeleteQuery::class, $adapter->delete());
    }

    public function testExecute()
    {
        $adapter = new DbalAdapter(
            $conn = DriverManager::getConnection(['url' => 'sqlite:///:memory:']),
            (new CompilerConfigurator())->configure(),
        );

        $conn->executeStatement('create table users (id integer, name varchar(100))');
        $conn->executeStatement('insert into users values (1, \'bob\'), (2, \'fred\')');

        $results = $adapter->select('*')->from('users')->execute();
        $this->assertInstanceOf(Result::class, $results);

        $results = $results->fetchAllAssociative();
        $this->assertCount(2, $results);
    }
}
