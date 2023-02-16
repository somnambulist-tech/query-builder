<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Tests\Query\QueryTests;

use PHPUnit\Framework\TestCase;
use Somnambulist\Components\QueryBuilder\Compiler\CompilerInterface;
use Somnambulist\Components\QueryBuilder\Query\Expressions\CommonTableExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\QueryExpression;
use Somnambulist\Components\QueryBuilder\Query\Query;
use Somnambulist\Components\QueryBuilder\Query\Type\SelectQuery;
use Somnambulist\Components\QueryBuilder\Tests\Support\QueryAssertsTrait;
use Somnambulist\Components\QueryBuilder\Tests\Support\QueryCompilerBuilderTrait;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use function Somnambulist\Components\QueryBuilder\Resources\delete;
use function Somnambulist\Components\QueryBuilder\Resources\insert;
use function Somnambulist\Components\QueryBuilder\Resources\select;
use function Somnambulist\Components\QueryBuilder\Resources\update;

class CommonTableExpressionQueryTest extends TestCase
{
    use QueryAssertsTrait;
    use QueryCompilerBuilderTrait;

    protected ?CompilerInterface $compiler = null;

    public function setUp(): void
    {
        $this->compiler = $this->buildCompiler();
    }

    public function tearDown(): void
    {
        $this->compiler = null;
    }

    /**
     * Tests with() sql generation.
     */
    public function testWithCte(): void
    {
        $query = select()
            ->with(new CommonTableExpression('cte', function () {
                return select(fields: ['col' => 1]);
            }))
            ->select('col')
            ->from('cte');

        $this->assertQueryContains(
            'WITH cte AS \(SELECT 1 AS col\) SELECT col FROM cte',
            $this->compiler->compile($query, new ValueBinder())
        );
    }

    /**
     * Tests recursive CTE.
     */
    public function testWithRecursiveCte(): void
    {
        $query = select()
            ->with(function (CommonTableExpression $cte, SelectQuery $query) {
                $anchorQuery = $query->select(1);

                $recursiveQuery = select(function (Query $query) {
                        return $query->newExpr('col + 1');
                    }, 'cte')
                    ->where(['col !=' => 3], ['col' => 'integer']);

                $cteQuery = $anchorQuery->unionAll($recursiveQuery);

                return $cte
                    ->name('cte')
                    ->field(['col'])
                    ->query($cteQuery)
                    ->recursive();
            })
            ->select('col')
            ->from('cte');
        
        $expectedSql =
            'WITH RECURSIVE cte(col) AS ' .
                "((SELECT 1)\nUNION ALL (SELECT (col + 1) FROM cte WHERE col != :c_0)) " .
                    'SELECT col FROM cte';
        
        $this->assertEqualsSql(
            $expectedSql,
            $this->compiler->compile($query, new ValueBinder())
        );
    }

    /**
     * Test inserting from CTE.
     */
    public function testWithInsertQuery(): void
    {
        $query = insert()
            ->with(function (CommonTableExpression $cte, SelectQuery $query) {
                return $cte
                    ->name('cte')
                    ->field(['title', 'body'])
                    ->query($query->newExpr("SELECT 'Fourth Article', 'Fourth Article Body'"));
            })
            ->insert(['title', 'body'])
            ->into('articles')
            ->values(
                select(fields: '*', from: 'cte')
            );

        $this->assertQueryContains(
            "WITH cte\(title, body\) AS \(SELECT 'Fourth Article', 'Fourth Article Body'\) " .
                'INSERT INTO articles \(title, body\)',
            $this->compiler->compile($query, new ValueBinder())
        );
    }

    /**
     * Tests inserting from CTE as values list.
     */
    public function testWithInInsertWithValuesQuery(): void
    {
        $query = insert(into: 'articles')
            ->insert(['title', 'body'])
            ->values(
                select()
                    ->with(function (CommonTableExpression $cte, SelectQuery $query) {
                        return $cte
                            ->name('cte')
                            ->field(['title', 'body'])
                            ->query($query->newExpr("SELECT 'Fourth Article', 'Fourth Article Body'"));
                    })
                    ->select('*')
                    ->from('cte')
            );

        $this->assertQueryContains(
            'INSERT INTO articles \(title, body\) ' .
                "WITH cte\(title, body\) AS \(SELECT 'Fourth Article', 'Fourth Article Body'\) SELECT \* FROM cte",
            $this->compiler->compile($query, new ValueBinder())
        );
    }

    /**
     * Tests updating from CTE.
     */
    public function testWithInUpdateQuery(): void
    {
        $query = update()
            ->with(function (CommonTableExpression $cte, SelectQuery $query) {
                $cteQuery = $query
                    ->select('articles.id')
                    ->from('articles')
                    ->where(['articles.id !=' => 1]);

                return $cte
                    ->name('cte')
                    ->query($cteQuery);
            })
            ->update('articles')
            ->set('published', 'N')
            ->where(function (QueryExpression $exp, Query $query) {
                return $exp->in(
                    'articles.id',
                    select('cte.id', 'cte')
                );
            });

        $this->assertEqualsSql(
            'WITH cte AS (SELECT articles.id FROM articles WHERE articles.id != :c_0) ' .
                'UPDATE articles SET published = :c_1 WHERE id IN (SELECT cte.id FROM cte)',
            $this->compiler->compile($query, new ValueBinder())
        );
    }

    /**
     * Tests deleting from CTE.
     */
    public function testWithInDeleteQuery(): void
    {
        $query = delete()
            ->with(function (CommonTableExpression $cte, SelectQuery $query) {
                $query->select('articles.id')
                    ->from('articles')
                    ->where(['articles.id !=' => 1]);

                return $cte
                    ->name('cte')
                    ->query($query);
            })
            ->from('articles', 'a')
            ->where(function (QueryExpression $exp, Query $query) {
                return $exp->in(
                    'a.id',
                    select('cte.id', 'cte')
                );
            });

        $this->assertEqualsSql(
            'WITH cte AS (SELECT articles.id FROM articles WHERE articles.id != :c_0) ' .
                'DELETE FROM articles WHERE id IN (SELECT cte.id FROM cte)',
            $this->compiler->compile($query, new ValueBinder())
        );
    }
}
