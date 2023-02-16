<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Tests\Query\QueryTests;

use PHPUnit\Framework\TestCase;
use Somnambulist\Components\QueryBuilder\Compiler\CompilerInterface;
use Somnambulist\Components\QueryBuilder\Query\Expressions\QueryExpression;
use Somnambulist\Components\QueryBuilder\Query\OrderDirection;
use Somnambulist\Components\QueryBuilder\Query\Query;
use Somnambulist\Components\QueryBuilder\Query\Type\SelectQuery;
use Somnambulist\Components\QueryBuilder\Query\Type\UpdateQuery;
use Somnambulist\Components\QueryBuilder\Tests\Support\QueryCompilerBuilderTrait;
use Somnambulist\Components\QueryBuilder\ValueBinder;

class CaseExpressionQueryTest extends TestCase
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

    public function testSimpleCase(): void
    {
        $query = $this->query
            ->select(function (Query $query) {
                return [
                    'name',
                    'category_name' => $query
                        ->newExpr()
                        ->case($query->identifier('products.category'))
                        ->when(1)
                        ->then('Touring')
                        ->when(2)
                        ->then('Urban')
                        ->else('Other'),
                ];
            })
            ->from('products')
            ->orderBy('category')
            ->orderBy('name')
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertEquals(
            'SELECT name, (CASE products.category WHEN :c_0 THEN :c_1 WHEN :c_2 THEN :c_3 ELSE :c_4 END) ' .
            'AS category_name FROM products ORDER BY category ASC, name ASC',
            $sql
        );
    }

    public function testSearchedCase(): void
    {
        $query = $this->query
            ->select(function (Query $query) {
                return [
                    'name',
                    'price',
                    'price_range' => $query
                        ->newExpr()
                        ->case()
                        ->when(['price <' => 20])
                        ->then('Under $20')
                        ->when(['price >=' => 20, 'price <' => 30])
                        ->then('Under $30')
                        ->else('$30 and above'),
                ];
            })
            ->from('products')
            ->orderBy('price')
            ->orderBy('name')
        ;
        $query
            ->getTypes()->setTypes([
                'price' => 'integer',
            ])
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertEquals(
            'SELECT name, price, (CASE WHEN price < :c_0 THEN :c_1 WHEN (price >= :c_2 AND price < :c_3) THEN :c_4 ELSE :c_5 END)' .
            ' AS price_range FROM products ORDER BY price ASC, name ASC',
            $sql
        );
    }

    public function testOrderByCase(): void
    {
        $query = $this->query
            ->select(['article_id', 'user_id'])
            ->from('comments')
            ->orderBy('comments.article_id')
            ->orderBy(function (QueryExpression $exp, Query $query) {
                return $query
                    ->newExpr()
                    ->case($query->identifier('comments.article_id'))
                    ->when(1)
                    ->then($query->identifier('comments.user_id'))
                ;
            }, OrderDirection::DESC)
            ->orderBy(function (QueryExpression $exp, Query $query) {
                return $query
                    ->newExpr()
                    ->case($query->identifier('comments.article_id'))
                    ->when(2)
                    ->then($query->identifier('comments.user_id'))
                ;
            })
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertEquals(
            'SELECT article_id, user_id FROM comments ORDER BY comments.article_id ASC, ' .
            'CASE comments.article_id WHEN :c_0 THEN comments.user_id ELSE NULL END DESC, ' .
            'CASE comments.article_id WHEN :c_1 THEN comments.user_id ELSE NULL END ASC',
            $sql
        );
    }

    public function testHavingByCase(): void
    {
        $query = $this->query
            ->select(['articles.title'])
            ->from('articles')
            ->leftJoin('comments', on: ['comments.article_id = articles.id'])
            ->groupBy(['articles.id', 'articles.title'])
            ->having(function (QueryExpression $exp, Query $query) {
                $expression = $query
                    ->newExpr()
                    ->case()
                    ->when(['comments.published' => 'Y'])
                    ->then(1)
                ;

                return $exp->gt(
                    $query->func()->sum($expression),
                    2,
                    'integer'
                );
            })
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertEquals(
            'SELECT articles.title FROM articles LEFT JOIN comments ON comments.article_id = articles.id ' .
            'GROUP BY articles.id, articles.title ' .
            'HAVING (SUM(CASE WHEN comments.published = :c_0 THEN :c_1 ELSE NULL END)) > :c_2',
            $sql
        );
    }

    public function testUpdateFromCase(): void
    {
        $query = (new UpdateQuery())
            ->update('comments')
            ->set([
                'published' =>
                    $this->query
                        ->newExpr()
                        ->case()
                        ->when(['published' => 'Y'])
                        ->then('N')
                        ->else('Y'),
            ])
            ->where(['1 = 1'])
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertEquals(
            'UPDATE comments SET published = (CASE WHEN published = :c_0 THEN :c_1 ELSE :c_2 END) WHERE 1 = 1',
            $sql
        );
    }

    public function bindingValueDataProvider(): array
    {
        return [
            ['1', 3],
            ['2', 4],
        ];
    }

    /**
     * @dataProvider bindingValueDataProvider
     *
     * @param string $when The `WHEN` value.
     * @param int $result The result value.
     */
    public function testBindValues(string $when, int $result): void
    {
        $value = '1';
        $then = '3';
        $else = '4';

        $query = $this->query
            ->select(function (Query $query) {
                return [
                    'val' => $query
                        ->newExpr()
                        ->case($query->newExpr(':value'))
                        ->when($query->newExpr(':when'))
                        ->then($query->newExpr(':then'))
                        ->else($query->newExpr(':else')),
                ];
            })
            ->from('products')
            ->bind(':value', $value, 'integer')
            ->bind(':when', $when, 'integer')
            ->bind(':then', $then, 'integer')
            ->bind(':else', $else, 'integer')
        ;
        $query->getTypes()->setTypes(['val' => 'integer']);

        $sql = $this->compiler->compile($query, $b = new ValueBinder());
        $this->assertEquals(
            'SELECT (CASE :value WHEN :when THEN :then ELSE :else END) AS val FROM products',
            $sql
        );

        foreach ($b as $v) {
            $this->assertEquals('integer', $v->type);
        }
    }
}
