<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Tests\Query\QueryTests;

use PHPUnit\Framework\TestCase;
use Somnambulist\Components\QueryBuilder\Compiler\CompilerInterface;
use Somnambulist\Components\QueryBuilder\Compiler\QueryCompiler;
use Somnambulist\Components\QueryBuilder\Query\Expressions\TupleComparison;
use Somnambulist\Components\QueryBuilder\Query\Type\SelectQuery;
use Somnambulist\Components\QueryBuilder\Tests\Support\QueryCompilerBuilderTrait;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use function Somnambulist\Components\QueryBuilder\Resources\select;

class TupleComparisonQueryTest extends TestCase
{
    use QueryCompilerBuilderTrait;

    protected ?SelectQuery $query = null;
    protected ?CompilerInterface $compiler = null;

    public function setUp(): void
    {
        $this->compiler = $this->buildCompiler();
        $this->query = new SelectQuery();
    }

    public function tearDown(): void
    {
        $this->query = null;
        $this->compiler = null;
    }

    public function testTransformWithInvalidOperator(): void
    {
        $query = $this->query
            ->select(['articles.id', 'articles.author_id'])
            ->from('articles')
            ->where([
                new TupleComparison(
                    ['articles.id', 'articles.author_id'],
                    select(
                        ['ArticlesAlias.id', 'ArticlesAlias.author_id']
                    )
                    ->from('articles', 'ArticlesAlias')
                    ->where(['ArticlesAlias.author_id' => 1]),
                    [],
                    'NOT IN'
                ),
            ])
            ->orderBy('articles.id')
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertEquals(
            'SELECT articles.id, articles.author_id FROM articles WHERE (articles.id, articles.author_id) '.
            'NOT IN (SELECT ArticlesAlias.id, ArticlesAlias.author_id FROM articles ArticlesAlias '.
            'WHERE ArticlesAlias.author_id = :c_0) ORDER BY articles.id ASC',
            $sql
        );
    }

    public function testInWithMultiResultSubquery(): void
    {
        $query = $this->query
            ->select(['articles.id', 'articles.author_id'])
            ->from('articles')
            ->where([
                new TupleComparison(
                    ['articles.id', 'articles.author_id'],
                    select(
                        ['ArticlesAlias.id', 'ArticlesAlias.author_id'],
                    )
                    ->from('articles', 'ArticlesAlias')
                    ->where(['ArticlesAlias.author_id' => 1]),
                    [],
                    'IN'
                ),
            ])
            ->orderBy('articles.id')
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertEquals(
            'SELECT articles.id, articles.author_id FROM articles WHERE (articles.id, articles.author_id) '.
            'IN (SELECT ArticlesAlias.id, ArticlesAlias.author_id FROM articles ArticlesAlias '.
            'WHERE ArticlesAlias.author_id = :c_0) ORDER BY articles.id ASC',
            $sql
        );
    }

    public function testInWithSingleResultSubquery(): void
    {
        $query = $this->query
            ->select(['articles.id', 'articles.author_id'])
            ->from('articles')
            ->where([
                new TupleComparison(
                    ['articles.id', 'articles.author_id'],
                    select(
                        ['ArticlesAlias.id', 'ArticlesAlias.author_id'],
                    )
                    ->from('articles', 'ArticlesAlias')
                    ->where(['ArticlesAlias.id' => 1]),
                    [],
                    'IN'
                ),
            ])
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertEquals(
            'SELECT articles.id, articles.author_id FROM articles WHERE (articles.id, articles.author_id) '.
            'IN (SELECT ArticlesAlias.id, ArticlesAlias.author_id FROM articles ArticlesAlias '.
            'WHERE ArticlesAlias.id = :c_0)',
            $sql
        );
    }

    public function testInWithMultiArrayValues(): void
    {
        $query = $this->query
            ->select(['articles.id', 'articles.author_id'])
            ->from('articles')
            ->where([
                new TupleComparison(
                    ['articles.id', 'articles.author_id'],
                    [[1, 1], [3, 1]],
                    ['integer', 'integer'],
                    'IN'
                ),
            ])
            ->orderBy('articles.id')
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertEquals(
            'SELECT articles.id, articles.author_id FROM articles WHERE (articles.id, articles.author_id) '.
            'IN ((:tuple_0,:tuple_1), (:tuple_2,:tuple_3)) ORDER BY articles.id ASC',
            $sql
        );
    }

    public function testEqualWithMultiResultSubquery(): void
    {
        $query = $this->query
            ->select(['articles.id', 'articles.author_id'])
            ->from('articles')
            ->where([
                new TupleComparison(
                    ['articles.id', 'articles.author_id'],
                    select(
                        ['ArticlesAlias.id', 'ArticlesAlias.author_id']
                    )
                    ->from('articles', 'ArticlesAlias')
                    ->where(['ArticlesAlias.author_id' => 1]),
                    [],
                    '='
                ),
            ])
            ->orderBy('articles.id')
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertEquals(
            'SELECT articles.id, articles.author_id FROM articles WHERE (articles.id, articles.author_id) '.
            '= (SELECT ArticlesAlias.id, ArticlesAlias.author_id FROM articles ArticlesAlias '.
            'WHERE ArticlesAlias.author_id = :c_0) ORDER BY articles.id ASC',
            $sql
        );
    }

    public function testEqualWithSingleResultSubquery(): void
    {
        $query = $this->query
            ->select(['articles.id', 'articles.author_id'])
            ->from('articles')
            ->where([
                new TupleComparison(
                    ['articles.id', 'articles.author_id'],
                    select(
                        fields: ['ArticlesAlias.id', 'ArticlesAlias.author_id'],
                    )
                    ->from('articles', 'ArticlesAlias')
                    ->where(['ArticlesAlias.id' => 1]),
                    [],
                    '='
                ),
            ])
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertEquals(
            'SELECT articles.id, articles.author_id FROM articles WHERE (articles.id, articles.author_id) '.
            '= (SELECT ArticlesAlias.id, ArticlesAlias.author_id FROM articles ArticlesAlias '.
            'WHERE ArticlesAlias.id = :c_0)',
            $sql
        );
    }

    public function testEqualWithSingleArrayValue(): void
    {
        $query = $this->query
            ->select(['articles.id', 'articles.author_id'])
            ->from('articles')
            ->where([
                new TupleComparison(
                    ['articles.id', 'articles.author_id'],
                    [1, 1],
                    ['integer', 'integer'],
                    '='
                ),
            ])
            ->orderBy('articles.id')
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertEquals(
            'SELECT articles.id, articles.author_id FROM articles WHERE (articles.id, articles.author_id) ='.
            ' (:tuple_0, :tuple_1) ORDER BY articles.id ASC',
            $sql
        );
    }
}
