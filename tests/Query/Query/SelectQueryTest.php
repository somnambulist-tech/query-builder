<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Tests\Query\Query;

use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Somnambulist\Components\Models\Types\DateTime\DateTime;
use Somnambulist\Components\QueryBuilder\Compiler\CompilerInterface;
use Somnambulist\Components\QueryBuilder\Query\ExpressionInterface;
use Somnambulist\Components\QueryBuilder\Query\Expressions\CommonTableExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\DistinctExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\FromExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\GroupByExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\IdentifierExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\JoinExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\ModifierExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\QueryExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\SelectClauseExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\StringExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\UnionExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\WindowClauseExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\WindowExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\WithExpression;
use Somnambulist\Components\QueryBuilder\Query\OrderDirection;
use Somnambulist\Components\QueryBuilder\Query\Query;
use Somnambulist\Components\QueryBuilder\Query\Type\SelectQuery;
use Somnambulist\Components\QueryBuilder\Tests\Support\QueryAssertsTrait;
use Somnambulist\Components\QueryBuilder\Tests\Support\QueryCompilerBuilderTrait;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use function is_null;
use function Somnambulist\Components\QueryBuilder\Resources\select;

/**
 * Tests SelectQuery class
 */
class SelectQueryTest extends TestCase
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
     * Tests that it is possible to obtain expression results from a query
     */
    public function testSelectFieldsOnly(): void
    {
        $query = new SelectQuery();
        $query->select('1 + 1');

        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertEquals('SELECT 1 + 1', $sql);
    }

    /**
     * Tests that it is possible to pass a closure as fields in select()
     */
    public function testSelectClosure(): void
    {
        $query = new SelectQuery();
        $query->select(function ($q) use ($query) {
            $this->assertSame($query, $q);

            return ['1 + 2', '1 + 5'];
        });

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT 1 + 2, 1 + 5', $sql);
    }

    /**
     * Tests it is possible to select fields from tables with no conditions
     */
    public function testSelectFieldsFromTable(): void
    {
        $query = new SelectQuery();
        $query->select(['body', 'author_id'])->from('articles');

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT body, author_id FROM articles', $sql);

        //Append more tables to next execution
        $query->select('name')->from('authors')->orderBy(['name' => 'DESC', 'articles.id' => 'ASC']);

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT body, author_id, name FROM articles, authors ORDER BY name DESC, articles.id ASC', $sql);

        // Overwrite tables and only fetch from authors
        $query->reset('select', 'from', 'order')->select('name')->from('authors')->orderBy(['name' => 'DESC']);

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT name FROM authors ORDER BY name DESC', $sql);
    }

    /**
     * Tests it is possible to select aliased fields
     */
    public function testSelectAliasedFieldsFromTable(): void
    {
        $query = new SelectQuery();
        $query->select(['text' => 'comment', 'article_id'])->from('comments');

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT comment AS text, article_id FROM comments', $sql);

        $query = new SelectQuery();
        $query->select(['text' => 'comment', 'article' => 'article_id'])->from('comments');

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT comment AS text, article_id AS article FROM comments', $sql);

        $query = new SelectQuery();
        $query->select(['text' => 'comment'])->select(['article_id', 'foo' => 'comment']);

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT comment AS text, article_id, comment AS foo', $sql);

        $query = new SelectQuery();
        $exp = $query->newExpr('1 + 1');
        $comp = $query->newExpr(['article_id +' => 2]);
        $query->select(['text' => 'comment', 'two' => $exp, 'three' => $comp])->from('comments');

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT comment AS text, (1 + 1) AS two, (article_id + :c_0) AS three FROM comments', $sql);
    }

    public function testSelectAliasedFieldsFromStringContainingAs(): void
    {
        $query = new SelectQuery();
        $query->select(['comment AS text', 'article_id as id'])->from('comments');

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT comment AS text, article_id AS id FROM comments', $sql);

        $query = new SelectQuery();
        $query->select(['cast(to_char(reporting_month, \'YYYY\') AS integer) AS quarter'])->from('reports');

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT cast(to_char(reporting_month, \'YYYY\') AS integer) AS quarter FROM reports', $sql);
    }

    /**
     * Tests that tables can also be aliased and referenced in the select clause using such alias
     */
    public function testSelectAliasedTables(): void
    {
        $query = new SelectQuery();
        $query->select(['text' => 'a.body', 'a.author_id'])->from('articles', 'a');

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT a.body AS text, a.author_id FROM articles a', $sql);
    }

    /**
     * Tests it is possible to add joins to a select query
     */
    public function testSelectWithJoins(): void
    {
        $query = new SelectQuery();
        $query
            ->select(['title', 'name'])
            ->from('articles')
            ->join('authors', 'a', $query->newExpr()->equalFields('author_id', 'a.id'))
            ->orderBy(['title' => 'ASC'])
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT title, name FROM articles INNER JOIN authors a ON author_id = a.id ORDER BY title ASC', $sql);

        $query->join('authors');

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT title, name FROM articles INNER JOIN authors a ON author_id = a.id INNER JOIN authors ON 1 = 1 ORDER BY title ASC', $sql);

        $query->reset('join')->join('authors', on: $query->newExpr()->equalFields('author_id', 'authors.id'));

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT title, name FROM articles INNER JOIN authors ON author_id = authors.id ORDER BY title ASC', $sql);
    }

    /**
     * Tests it is possible to add joins to a select query using array or expression as conditions
     */
    public function testSelectWithJoinsConditions(): void
    {
        $query = new SelectQuery();
        $time = new DateTime('2007-03-18 10:45:23');
        $types = ['created' => 'datetime'];

        $query
            ->select(['title', 'comment' => 'c.comment'])
            ->from('articles')
            ->join('comments', 'c', ['created' => $time], types: $types)
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT title, c.comment AS comment FROM articles INNER JOIN comments c ON created = :c_0', $sql);
    }

    /**
     * Tests that joins can be aliased using array keys
     */
    public function testSelectAliasedJoins(): void
    {
        $query = select(['title', 'comment' => 'c.comment'])
            ->from('articles')
            ->join('comments', 'c')
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT title, c.comment AS comment FROM articles INNER JOIN comments c ON 1 = 1', $sql);
    }

    /**
     * Tests the leftJoin method
     */
    public function testSelectLeftJoin(): void
    {
        $query = select(['title', 'comment' => 'c.comment'])
            ->from('articles')
            ->leftJoin('comments', 'c')
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT title, c.comment AS comment FROM articles LEFT JOIN comments c ON 1 = 1', $sql);
    }

    /**
     * Tests the innerJoin method
     */
    public function testSelectInnerJoin(): void
    {
        $query = select(['title', 'comment' => 'c.comment'])
            ->from('articles')
            ->innerJoin('comments', 'c')
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT title, c.comment AS comment FROM articles INNER JOIN comments c ON 1 = 1', $sql);
    }

    /**
     * Tests the rightJoin method
     */
    public function testSelectRightJoin(): void
    {
        $query = select(['title', 'comment' => 'c.comment'])
            ->from('articles')
            ->rightJoin('comments', 'c')
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT title, c.comment AS comment FROM articles RIGHT JOIN comments c ON 1 = 1', $sql);
    }

    /**
     * Tests that it is possible to pass a callable as conditions for a join
     */
    public function testSelectJoinWithCallback(): void
    {
        $query = new SelectQuery();
        $query
            ->select(['title', 'name' => 'c.comment'])
            ->from('articles')
            ->innerJoin('comments', 'c', function ($exp, $q) use ($query) {
                $this->assertSame($q, $query);
                $exp->add(['created <' => new DateTime('2007-03-18 10:45:23')], ['created' => 'datetime']);

                return $exp;
            })
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT title, c.comment AS name FROM articles INNER JOIN comments c ON created < :c_0', $sql);
    }

    /**
     * Tests that it is possible to pass a callable as conditions for a join
     */
    public function testSelectJoinWithCallback2(): void
    {
        $query = new SelectQuery();
        $types = ['created' => 'datetime'];
        $query
            ->select(['name', 'commentary' => 'comments.comment'])
            ->from('authors')
            ->innerJoin('comments', on: function ($exp, $q) use ($query, $types) {
                $this->assertSame($q, $query);
                $exp->add(['created' => new DateTime('2007-03-18 10:47:23')], $types);

                return $exp;
            })
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT name, comments.comment AS commentary FROM authors INNER JOIN comments ON created = :c_0', $sql);
    }

    /**
     * Tests it is possible to filter a query by using simple AND joined conditions
     */
    public function testSelectSimpleWhere(): void
    {
        $query = new SelectQuery();
        $query
            ->select(['title'])
            ->from('articles')
            ->where(['id' => 1, 'title' => 'First Article'])
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT title FROM articles WHERE (id = :c_0 AND title = :c_1)', $sql);

        $query = new SelectQuery();
        $query
            ->select(['title'])
            ->from('articles')
            ->where(['id' => 100], ['id' => 'integer'])
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT title FROM articles WHERE id = :c_0', $sql);
    }

    /**
     * Tests using where conditions with operators and scalar values works
     */
    public function testSelectWhereOperatorMoreThan(): void
    {
        $query = new SelectQuery();
        $query
            ->select(['comment'])
            ->from('comments')
            ->where(['id >' => 4])
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT comment FROM comments WHERE id > :c_0', $sql);
    }

    /**
     * Tests using where conditions with operators and scalar values works
     */
    public function testSelectWhereOperatorLessThan(): void
    {
        $query = new SelectQuery();
        $query
            ->select(['comment'])
            ->from('comments')
            ->where(['id <' => 4])
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT comment FROM comments WHERE id < :c_0', $sql);
    }

    /**
     * Tests using where conditions with operators and scalar values works
     */
    public function testSelectWhereOperatorLessThanEqual(): void
    {
        $query = new SelectQuery();
        $query
            ->select(['comment'])
            ->from('comments')
            ->where(['id <=' => 4])
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT comment FROM comments WHERE id <= :c_0', $sql);
    }

    /**
     * Tests using where conditions with operators and scalar values works
     */
    public function testSelectWhereOperatorMoreThanEqual(): void
    {
        $query = new SelectQuery();
        $query
            ->select(['comment'])
            ->from('comments')
            ->where(['id >=' => 4])
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT comment FROM comments WHERE id >= :c_0', $sql);
    }

    /**
     * Tests using where conditions with operators and scalar values works
     */
    public function testSelectWhereOperatorNotEqual(): void
    {
        $query = new SelectQuery();
        $query
            ->select(['comment'])
            ->from('comments')
            ->where(['id !=' => 4])
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT comment FROM comments WHERE id != :c_0', $sql);
    }

    /**
     * Tests using where conditions with operators and scalar values works
     */
    public function testSelectWhereOperatorLike(): void
    {
        $query = new SelectQuery();
        $query
            ->select(['title'])
            ->from('articles')
            ->where(['title LIKE' => 'something'])
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT title FROM articles WHERE title LIKE :c_0', $sql);
    }

    /**
     * Tests using where conditions with operators and scalar values works
     */
    public function testSelectWhereOperatorLikeExpansion(): void
    {
        $query = new SelectQuery();
        $query
            ->select(['title'])
            ->from('articles')
            ->where(['title LIKE' => '%something%'])
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT title FROM articles WHERE title LIKE :c_0', $sql);
    }

    /**
     * Tests using where conditions with operators and scalar values works
     */
    public function testSelectWhereOperatorNotLike(): void
    {
        $query = new SelectQuery();
        $query
            ->select(['title'])
            ->from('articles')
            ->where(['title NOT LIKE' => '%something%'])
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT title FROM articles WHERE title NOT LIKE :c_0', $sql);
    }

    /**
     * Test that unary expressions in selects are built correctly.
     */
    public function testSelectWhereUnary(): void
    {
        $query = new SelectQuery();
        $query
            ->select(['id'])
            ->from('articles')
            ->where([
                'title is not' => null,
                'user_id is'   => null,
            ])
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertQueryContains(
            'SELECT id FROM articles WHERE \(\(title\) IS NOT NULL AND \(user_id\) IS NULL\)',
            $sql
        );
    }

    /**
     * Tests selecting with conditions and specifying types for those
     */
    public function testSelectWhereTypes(): void
    {
        $query = new SelectQuery();
        $query
            ->select(['id'])
            ->from('comments')
            ->where(
                [
                    'created >' => new DateTime('2007-03-18 10:40:00'),
                    'created <' => new DateTime('2007-03-18 10:46:00'),
                ],
                ['created' => 'datetime']
            )
        ;

        $sql = $this->compiler->compile($query, $b = new ValueBinder());

        $this->assertCount(2, $b);
        $this->assertEquals('SELECT id FROM comments WHERE (created > :c_0 AND created < :c_1)', $sql);
    }

    /**
     * Tests Query::whereNull()
     */
    public function testSelectWhereNull(): void
    {
        $query = new SelectQuery();
        $query
            ->select(['id', 'parent_id'])
            ->from('menu_link_trees')
            ->whereNull(['parent_id'])
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT id, parent_id FROM menu_link_trees WHERE (parent_id) IS NULL', $sql);

        $query = new SelectQuery();
        $query
            ->select(['id'])
            ->from('menu_link_trees')
            ->whereNull((new SelectQuery())->select('parent_id'))
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT id FROM menu_link_trees WHERE (SELECT parent_id) IS NULL', $sql);

        $query = new SelectQuery();
        $query
            ->select(['id', 'parent_id'])
            ->from('menu_link_trees')
            ->whereNull('parent_id')
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT id, parent_id FROM menu_link_trees WHERE (parent_id) IS NULL', $sql);
    }

    /**
     * Tests Query::whereNotNull()
     */
    public function testSelectWhereNotNull(): void
    {
        $query = new SelectQuery();
        $query
            ->select(['id', 'parent_id'])
            ->from('menu_link_trees')
            ->whereNotNull(['parent_id'])
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT id, parent_id FROM menu_link_trees WHERE (parent_id) IS NOT NULL', $sql);

        $query = new SelectQuery();
        $query
            ->select(['id'])
            ->from('menu_link_trees')
            ->whereNotNull((new SelectQuery())->select('parent_id'))
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT id FROM menu_link_trees WHERE (SELECT parent_id) IS NOT NULL', $sql);

        $query = new SelectQuery();
        $query
            ->select(['id'])
            ->from('menu_link_trees')
            ->whereNotNull('parent_id')
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT id FROM menu_link_trees WHERE (parent_id) IS NOT NULL', $sql);
    }

    /**
     * Tests that passing an array type to any where condition will replace
     * the passed array accordingly as a proper IN condition
     */
    public function testSelectWhereArrayType(): void
    {
        $query = new SelectQuery();
        $query
            ->select(['id'])
            ->from('comments')
            ->where(['id' => ['1', '3']], ['id' => 'integer[]'])
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT id FROM comments WHERE id IN (:c_0,:c_1)', $sql);
    }

    /**
     * Tests that passing an empty array type to any where condition will not
     * result in a SQL error, but an internal exception
     */
    public function testSelectWhereArrayTypeEmpty(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Impossible to generate condition with empty list of values for field');

        $query = new SelectQuery();
        $query
            ->select(['id'])
            ->from('comments')
            ->where(['id' => []], ['id' => 'integer[]'])
        ;

        $this->compiler->compile($query, new ValueBinder());
    }

    /**
     * Tests exception message for impossible condition when using an expression
     */
    public function testSelectWhereArrayTypeEmptyWithExpression(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('with empty list of values for field (SELECT 1)');

        $query = new SelectQuery();
        $query
            ->select(['id'])
            ->from('comments')
            ->where(function ($exp, $q) {
                return $exp->in($q->newExpr('SELECT 1'), []);
            })
        ;

        $this->compiler->compile($query, new ValueBinder());
    }

    /**
     * Tests that Query::andWhere() can be used to concatenate conditions with AND
     */
    public function testSelectAndWhere(): void
    {
        $query = new SelectQuery();
        $query
            ->select(['id'])
            ->from('comments')
            ->where(['created' => new DateTime('2007-03-18 10:45:23')], ['created' => 'datetime'])
            ->andWhere(['id' => 1])
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT id FROM comments WHERE (created = :c_0 AND id = :c_1)', $sql);

        $query = new SelectQuery();
        $query
            ->select(['id'])
            ->from('comments')
            ->where(['created' => new DateTime('2007-03-18 10:50:55')], ['created' => 'datetime'])
            ->andWhere(['id' => 2])
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT id FROM comments WHERE (created = :c_0 AND id = :c_1)', $sql);
    }

    /**
     * Tests that Query::andWhere() can be used to concatenate conditions with AND
     */
    public function testSelectOrWhere(): void
    {
        $query = new SelectQuery();
        $query
            ->select(['id'])
            ->from('comments')
            ->where(['created' => new DateTime('2007-03-18 10:45:23')], ['created' => 'datetime'])
            ->orWhere(['id' => 1])
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT id FROM comments WHERE (created = :c_0 OR id = :c_1)', $sql);

        $query = new SelectQuery();
        $query
            ->select(['id'])
            ->from('comments')
            ->where(['created' => new DateTime('2007-03-18 10:50:55')], ['created' => 'datetime'])
            ->orWhere(['id' => 2])
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT id FROM comments WHERE (created = :c_0 OR id = :c_1)', $sql);
    }

    /**
     * Tests that Query::andWhere() can be used without calling where() before
     */
    public function testSelectAndWhereNoPreviousCondition(): void
    {
        $query = new SelectQuery();
        $query
            ->select(['id'])
            ->from('comments')
            ->andWhere(['created' => new DateTime('2007-03-18 10:45:23')], ['created' => 'datetime'])
            ->andWhere(['id' => 1])
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT id FROM comments WHERE (created = :c_0 AND id = :c_1)', $sql);
    }

    /**
     * Tests that Query::andWhere() can be used without calling where() before
     */
    public function testSelectOrWhereNoPreviousCondition(): void
    {
        $query = new SelectQuery();
        $query
            ->select(['id'])
            ->from('comments')
            ->orWhere(['created' => new DateTime('2007-03-18 10:45:23')], ['created' => 'datetime'])
            ->orWhere(['id' => 1])
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT id FROM comments WHERE (created = :c_0 OR id = :c_1)', $sql);
    }

    /**
     * Tests that it is possible to pass a closure to where() to build a set of
     * conditions and return the expression to be used
     */
    public function testSelectWhereUsingClosure(): void
    {
        $query = new SelectQuery();
        $query
            ->select(['id'])
            ->from('comments')
            ->where(function ($exp) {
                return $exp->eq('id', 1);
            })
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT id FROM comments WHERE id = :c_0', $sql);

        $query = new SelectQuery();
        $query
            ->select(['id'])
            ->from('comments')
            ->where(function ($exp) {
                return $exp
                    ->eq('id', 1)
                    ->eq('created', new DateTime('2007-03-18 10:45:23'), 'datetime')
                ;
            })
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT id FROM comments WHERE (id = :c_0 AND created = :c_1)', $sql);

        $query = new SelectQuery();
        $query
            ->select(['id'])
            ->from('comments')
            ->where(function ($exp) {
                return $exp
                    ->eq('id', 1)
                    ->eq('created', new DateTime('2021-12-30 15:00'), 'datetime')
                ;
            })
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT id FROM comments WHERE (id = :c_0 AND created = :c_1)', $sql);
    }

    /**
     * Tests generating tuples in the values side containing closure expressions
     */
    public function testTupleWithClosureExpression(): void
    {
        $query = new SelectQuery();
        $query->select(['id'])
              ->from('comments')
              ->where([
                  'OR' => [
                      'id' => 1,
                      function ($exp) {
                          return $exp->eq('id', 2);
                      },
                  ],
              ])
        ;

        $result = $this->compiler->compile($query, new ValueBinder());
        $this->assertQueryContains(
            'SELECT id FROM comments WHERE \(id = :c_0 OR id = :c_1\)',
            $result
        );
    }

    /**
     * Tests that it is possible to pass a closure to andWhere() to build a set of
     * conditions and return the expression to be used
     */
    public function testSelectAndWhereUsingClosure(): void
    {
        $query = new SelectQuery();
        $query
            ->select(['id'])
            ->from('comments')
            ->where(['id' => '1'])
            ->andWhere(function ($exp) {
                return $exp->eq('created', new DateTime('2007-03-18 10:45:23'), 'datetime');
            })
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT id FROM comments WHERE (id = :c_0 AND created = :c_1)', $sql);

        $query = new SelectQuery();
        $query
            ->select(['id'])
            ->from('comments')
            ->where(['id' => '1'])
            ->andWhere(function ($exp) {
                return $exp->eq('created', new DateTime('2022-12-21 12:00'), 'datetime');
            })
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT id FROM comments WHERE (id = :c_0 AND created = :c_1)', $sql);
    }

    /**
     * Tests that expression objects can be used as the field in a comparison
     * and the values will be bound correctly to the query
     */
    public function testSelectWhereUsingExpressionInField(): void
    {
        $query = new SelectQuery();
        $query
            ->select(['id'])
            ->from('comments')
            ->where(function ($exp) {
                $field = clone $exp;
                $field->add('SELECT MIN(id) FROM comments');

                return $exp
                    ->eq($field, 100, 'integer')
                ;
            })
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT id FROM comments WHERE (SELECT MIN(id) FROM comments) = :c_0', $sql);
    }

    public static function methodProvider(): array
    {
        return [
            ['gt', '>', 1],
            ['lt', '<', 2],
            ['lte', '<=', 2],
            ['gte', '>=', 1],
            ['notEq', '!=', 1],
            ['like', 'LIKE', '%something%'],
            ['isNull', 'IS NULL', null],
            ['isNotNull', 'IS NOT NULL', null],
            ['in', 'IN', ['Y', 'N']],
            ['notIn', 'NOT IN', ['Y', 'N']],
        ];
    }

    /**
     * Tests using where conditions with operator methods
     *
     * @dataProvider methodProvider
     */
    public function testSelectWhereOperatorMethods(string $method, string $operator, mixed $value): void
    {
        $query = new SelectQuery();
        $query
            ->select(['title'])
            ->from('articles')
            ->where(function ($exp) use ($method, $value) {
                return $exp->$method('id', $value);
            })
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        // Unary expressions get wrapped in () not sure why, it's from Cake, must be a reason...
        $field = is_null($value) ? '(id)' : 'id';

        $this->assertQueryStartsWith(sprintf('SELECT title FROM articles WHERE %s %s', $field, $operator), $sql);
    }

    /**
     * Tests that IN clauses generate correct placeholders
     */
    public function testInClausePlaceholderGeneration(): void
    {
        $query = new SelectQuery();
        $query
            ->select(['id'])
            ->from('comments')
            ->where(['id IN' => [1, 2]])
        ;

        $this->compiler->compile($query, $b = new ValueBinder());

        $bindings = $b->bindings();
        $this->assertArrayHasKey(':c_0', $bindings);
        $this->assertSame('c_0', $bindings[':c_0']->placeholder);
        $this->assertArrayHasKey(':c_1', $bindings);
        $this->assertSame('c_1', $bindings[':c_1']->placeholder);
    }

    /**
     * Tests where() with callable types.
     */
    public function testWhereCallables(): void
    {
        $query = new SelectQuery();
        $query
            ->select(['id'])
            ->from('articles')
            ->where([
                'id'        => 'Debugger::dump',
                'title'     => ['Debugger', 'dump'],
                'author_id' => function ($exp) {
                    return 1;
                },
            ])
        ;

        $this->assertQueryContains(
            'SELECT id FROM articles WHERE \(id = :c_0 AND title = :c_1 AND author_id = :c_2\)',
            $this->compiler->compile($query, $b = new ValueBinder())
        );
    }

    /**
     * Tests that empty values don't set where clauses.
     */
    public function testWhereEmptyValues(): void
    {
        $query = new SelectQuery();
        $query->from('comments')->where('');

        $this->assertCount(0, $query->clause('where'));

        $query->where([]);
        $this->assertCount(0, $query->clause('where'));
    }

    /**
     * Tests that it is possible to use a between expression in a where condition
     */
    public function testWhereWithBetween(): void
    {
        $query = new SelectQuery();
        $query
            ->select(['id'])
            ->from('comments')
            ->where(function ($exp) {
                return $exp->between('id', 5, 6, 'integer');
            })
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT id FROM comments WHERE id BETWEEN :c_0 AND :c_1', $sql);
    }

    /**
     * Tests that it is possible to use a between expression in a where condition with a complex data type
     */
    public function testWhereWithBetweenComplex(): void
    {
        $query = new SelectQuery();
        $query
            ->select(['id'])
            ->from('comments')
            ->where(function ($exp) {
                $from = new DateTime('2007-03-18 10:51:00');
                $to = new DateTime('2007-03-18 10:54:00');

                return $exp->between('created', $from, $to, 'datetime');
            })
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT id FROM comments WHERE created BETWEEN :c_0 AND :c_1', $sql);
    }

    /**
     * Tests that it is possible to use an expression object as the field for a between expression
     */
    public function testWhereWithBetweenWithExpressionField(): void
    {
        $query = new SelectQuery();
        $query
            ->select(['id'])
            ->from('comments')
            ->where(function ($exp, $q) {
                $field = $q->func()->coalesce([new IdentifierExpression('id'), 1 => 'literal']);

                return $exp->between($field, 5, 6, 'integer');
            })
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT id FROM comments WHERE COALESCE(id, 1) BETWEEN :c_0 AND :c_1', $sql);
    }

    /**
     * Tests that it is possible to use an expression object as any of the parts of the between expression
     */
    public function testWhereWithBetweenWithExpressionParts(): void
    {
        $query = new SelectQuery();
        $query
            ->select(['id'])
            ->from('comments')
            ->where(function ($exp, $q) {
                $from = $q->newExpr("'2007-03-18 10:51:00'");
                $to = $q->newExpr("'2007-03-18 10:54:00'");

                return $exp->between('created', $from, $to);
            })
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT id FROM comments WHERE created BETWEEN \'2007-03-18 10:51:00\' AND \'2007-03-18 10:54:00\'', $sql);
    }

    /**
     * Tests nesting query expressions both using arrays and closures
     */
    public function testSelectExpressionComposition(): void
    {
        $query = new SelectQuery();
        $query
            ->select(['id'])
            ->from('comments')
            ->where(function ($exp) {
                $and = $exp->and(['id' => 2, 'id >' => 1]);

                return $exp->add($and);
            })
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT id FROM comments WHERE (id = :c_0 AND id > :c_1)', $sql);

        $query = new SelectQuery();
        $query
            ->select(['id'])
            ->from('comments')
            ->where(function ($exp) {
                $and = $exp->and(function ($and) {
                    return $and->eq('id', 1)->gt('id', 0);
                });

                return $exp->add($and);
            })
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT id FROM comments WHERE (id = :c_0 AND id > :c_1)', $sql);

        $query = new SelectQuery();
        $query
            ->select(['id'])
            ->from('comments')
            ->where(function ($exp) {
                $or = $exp->or(['id' => 1]);
                $and = $exp->and(['id >' => 2, 'id <' => 4]);

                return $or->add($and);
            })
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT id FROM comments WHERE (id = :c_0 OR (id > :c_1 AND id < :c_2))', $sql);

        $query = new SelectQuery();
        $query
            ->select(['id'])
            ->from('comments')
            ->where(function ($exp) {
                return $exp->or(function ($or) {
                    return $or->eq('id', 1)->eq('id', 2);
                });
            })
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT id FROM comments WHERE (id = :c_0 OR id = :c_1)', $sql);
    }

    /**
     * Tests that conditions can be nested with a unary operator using the array notation
     * and the not() method
     */
    public function testSelectWhereNot(): void
    {
        $query = new SelectQuery();
        $query
            ->select(['id'])
            ->from('comments')
            ->where(function ($exp) {
                return $exp->not(
                    $exp->and(['id' => 2, 'created' => new DateTime('2007-03-18 10:47:23')], ['created' => 'datetime'])
                );
            })
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT id FROM comments WHERE NOT ((id = :c_0 AND created = :c_1))', $sql);

        $query = new SelectQuery();
        $query
            ->select(['id'])
            ->from('comments')
            ->where(function ($exp) {
                return $exp->not(
                    $exp->and(['id' => 2, 'created' => new DateTime('2012-12-21 12:00')], ['created' => 'datetime'])
                );
            })
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT id FROM comments WHERE NOT ((id = :c_0 AND created = :c_1))', $sql);
    }

    /**
     * Tests that conditions can be nested with a unary operator using the array notation
     * and the not() method
     */
    public function testSelectWhereNot2(): void
    {
        $query = new SelectQuery();
        $query
            ->select(['id'])
            ->from('articles')
            ->where([
                'not' => ['or' => ['id' => 1, 'id >' => 2], 'id' => 3],
            ])
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals('SELECT id FROM articles WHERE NOT (((id = :c_0 OR id > :c_1) AND id = :c_2))', $sql);
    }

    /**
     * Tests whereInArray() and its input types.
     */
    public function testWhereInArray(): void
    {
        $query = new SelectQuery();
        $query
            ->select(['id'])
            ->from('articles')
            ->whereInList('id', [2, 3])
            ->orderBy(['id'])
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertQueryContains(
            'SELECT id FROM articles WHERE id IN \\(:c_0,:c_1\\)',
            $sql
        );
    }

    /**
     * Tests whereInArray() and empty array input.
     */
    public function testWhereInArrayEmpty(): void
    {
        $query = new SelectQuery();
        $query
            ->select(['id'])
            ->from('articles')
            ->whereInList('id', [], ['allowEmpty' => true])
        ;

        $this->assertQueryContains(
            'SELECT id FROM articles WHERE 1=0',
            $this->compiler->compile($query, new ValueBinder())
        );
    }

    /**
     * Tests whereNotInList() and its input types.
     */
    public function testWhereNotInList(): void
    {
        $query = new SelectQuery();
        $query
            ->select(['id'])
            ->from('articles')
            ->whereNotInList('id', [1, 3])
        ;

        $this->assertQueryContains(
            'SELECT id FROM articles WHERE id NOT IN \\(:c_0,:c_1\\)',
            $this->compiler->compile($query, new ValueBinder())
        );
    }

    /**
     * Tests whereNotInList() and empty array input.
     */
    public function testWhereNotInListEmpty(): void
    {
        $query = new SelectQuery();
        $query
            ->select(['id'])
            ->from('articles')
            ->whereNotInList('id', [], ['allowEmpty' => true])
        ;

        $this->assertQueryContains(
            'SELECT id FROM articles WHERE \(id\) IS NOT NULL',
            $this->compiler->compile($query, new ValueBinder())
        );
    }

    /**
     * Tests whereNotInListOrNull() and its input types.
     */
    public function testWhereNotInListOrNull(): void
    {
        $query = new SelectQuery();
        $query
            ->select(['id'])
            ->from('articles')
            ->whereNotInListOrNull('id', [1, 3])
        ;

        $this->assertQueryContains(
            'SELECT id FROM articles WHERE \\(id NOT IN \\(:c_0,:c_1\\) OR \\(id\\) IS NULL\\)',
            $this->compiler->compile($query, new ValueBinder())
        );
    }

    /**
     * Tests whereNotInListOrNull() and empty array input.
     */
    public function testWhereNotInListOrNullEmpty(): void
    {
        $query = new SelectQuery();
        $query
            ->select(['id'])
            ->from('articles')
            ->whereNotInListOrNull('id', [], ['allowEmpty' => true])
        ;

        $this->assertQueryContains(
            'SELECT id FROM articles WHERE \(id\) IS NOT NULL',
            $this->compiler->compile($query, new ValueBinder())
        );
    }

    /**
     * Tests orderBy() method both with simple fields and expressions
     */
    public function testSelectOrderBy(): void
    {
        $query = new SelectQuery();
        $query
            ->select(['id'])
            ->from('comments')
            ->orderBy(['id' => 'desc'])
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertQueryContains('ORDER BY id desc', $sql);

        $query->orderBy(['id' => 'asc']);

        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertQueryContains('ORDER BY id asc', $sql);

        $query->orderBy(['comment' => 'asc']);

        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertQueryContains('ORDER BY id asc, comment asc', $sql);

        $query->reset('order');
        $this->assertNull($query->clause('order'));

        $query->orderBy(['user_id' => 'asc', 'created' => 'desc']);

        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertQueryContains('ORDER BY user_id asc, created desc', $sql);

        $query
            ->reset('order')
            ->orderBy([$query->newExpr(['(id + :offset) % 2']), 'id' => 'desc'])
            ->bind(':offset', 1)
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertQueryContains('ORDER BY \(id \+ \:offset\) % 2, id desc$', $sql);
    }

    /**
     * Test that orderBy() being a string works.
     */
    public function testSelectOrderByString(): void
    {
        $query = new SelectQuery();
        $query
            ->select(['id'])
            ->from('articles')
            ->orderBy('id asc')
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertQueryContains('ORDER BY id asc', $sql);
    }

    /**
     * Test exception for orderBy() with an associative array which contains extra values.
     */
    public function testSelectOrderByAssociativeArrayContainingExtraExpressions(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Passing extra expressions by associative array ("id => desc -- Comment") ' .
                'is not allowed to avoid potential SQL injection. ' .
                'Use "%s" or numeric array instead.',
                QueryExpression::class
            )
        );

        $query = new SelectQuery();
        $query
            ->select(['id'])
            ->from('articles')
            ->orderBy([
                'id' => 'desc -- Comment',
            ])
        ;
    }

    /**
     * Tests that orderBy() works with closures.
     */
    public function testSelectOrderByClosure(): void
    {
        $query = new SelectQuery();
        $query
            ->select('*')
            ->from('articles')
            ->orderBy(function ($exp, $q) use ($query) {
                $this->assertInstanceOf(QueryExpression::class, $exp);
                $this->assertSame($query, $q);

                return ['id' => 'ASC'];
            })
        ;

        $this->assertQueryContains(
            'SELECT \* FROM articles ORDER BY id ASC',
            $this->compiler->compile($query, new ValueBinder())
        );

        $query = new SelectQuery();
        $query
            ->select('*')
            ->from('articles')
            ->orderBy(function ($exp) {
                return [$exp->add(['id % 2 = 0']), 'title' => 'ASC'];
            })
        ;

        $this->assertQueryContains(
            'SELECT \* FROM articles ORDER BY id % 2 = 0, title ASC',
            $this->compiler->compile($query, new ValueBinder())
        );

        $query = new SelectQuery();
        $query
            ->select('*')
            ->from('articles')
            ->orderBy(function ($exp) {
                return $exp->add('a + b');
            })
        ;

        $this->assertQueryContains(
            'SELECT \* FROM articles ORDER BY a \+ b',
            $this->compiler->compile($query, new ValueBinder())
        );

        $query = new SelectQuery();
        $query
            ->select('*')
            ->from('articles')
            ->orderBy(function ($exp, $q) {
                return $q->func()->sum('a');
            })
        ;

        $this->assertQueryContains(
            'SELECT \* FROM articles ORDER BY SUM\(a\)',
            $this->compiler->compile($query, new ValueBinder())
        );
    }

    public function testSelectOrderByComplexExpression(): void
    {
        $query = new SelectQuery();
        $query->select(['id'])
              ->from('articles')
              ->orderBy(function (QueryExpression $exp, Query $query) {
                  return $exp
                      ->case()
                      ->when(['author_id' => 1])
                      ->then(1)
                      ->else($query->identifier('id'))
                  ;
              })
              ->orderBy('id', OrderDirection::ASC)
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertQueryContains(
            'ORDER BY CASE WHEN author_id = :c_0 THEN :c_1 ELSE id END ASC, id ASC$',
            $sql
        );
    }

    /**
     * Tests that group by fields can be passed similar to select fields
     * and that it sends the correct query to the database
     */
    public function testSelectGroupBy(): void
    {
        $query = new SelectQuery();
        $result = $query
            ->select(['total' => 'count(author_id)', 'author_id'])
            ->from('articles')
            ->join('authors', 'a', 'author_id = a.id')
            ->groupBy('author_id')
            ->orderBy(['total' => 'desc'])
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertQueryContains('GROUP BY author_id ORDER BY total desc$', $sql);
    }

    /**
     * Tests that it is possible to select distinct rows
     */
    public function testSelectDistinct(): void
    {
        $query = new SelectQuery();
        $query
            ->select(['author_id'])
            ->from('articles', 'a')
            ->distinct()
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertQueryContains('SELECT DISTINCT author_id', $sql);
    }

    /**
     * Tests distinct on a specific column reduces rows based on that column.
     */
    public function testSelectDistinctON(): void
    {
        $query = new SelectQuery();
        $query
            ->select(['author_id'])
            ->distinct('author_id')
            ->from('articles', 'a')
            ->orderBy(['author_id' => 'ASC'])
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertQueryContains('SELECT DISTINCT ON \(author_id\)', $sql);
    }

    /**
     * Test use of modifiers in the query
     */
    public function testSelectModifiers(): void
    {
        $query = new SelectQuery();
        $query
            ->select(['city', 'state', 'country'])
            ->from('addresses')
            ->modifier('DISTINCTROW')
        ;

        $this->assertQueryContains(
            'SELECT DISTINCTROW city, state, country FROM addresses',
            $this->compiler->compile($query, new ValueBinder())
        );

        $query = new SelectQuery();
        $query
            ->select(['city', 'state', 'country'])
            ->from('addresses')
            ->modifier('DISTINCTROW', 'SQL_NO_CACHE')
        ;

        $this->assertQueryContains(
            'SELECT DISTINCTROW SQL_NO_CACHE city, state, country FROM addresses',
            $this->compiler->compile($query, new ValueBinder())
        );

        $query = new SelectQuery();
        $query
            ->select(['city', 'state', 'country'])
            ->from('addresses')
            ->modifier('DISTINCTROW')
            ->modifier('SQL_NO_CACHE')
        ;

        $this->assertQueryContains(
            'SELECT DISTINCTROW SQL_NO_CACHE city, state, country FROM addresses',
            $this->compiler->compile($query, new ValueBinder())
        );

        $query = new SelectQuery();
        $query
            ->select(['city', 'state', 'country'])
            ->from('addresses')
            ->modifier('TOP 10')
        ;

        $this->assertQueryContains(
            'SELECT TOP 10 city, state, country FROM addresses',
            $this->compiler->compile($query, new ValueBinder())
        );

        $query = new SelectQuery();
        $query
            ->select(['city', 'state', 'country'])
            ->from('addresses')
            ->modifier($query->newExpr('EXPRESSION'))
        ;

        $this->assertQueryContains(
            'SELECT EXPRESSION city, state, country FROM addresses',
            $this->compiler->compile($query, new ValueBinder())
        );
    }

    /**
     * Tests that having() behaves pretty much the same as the where() method
     */
    public function testSelectHaving(): void
    {
        $query = new SelectQuery();
        $query
            ->select(['total' => 'count(author_id)', 'author_id'])
            ->from('articles')
            ->join('authors', 'a', $query->newExpr()->equalFields('author_id', 'a.id'))
            ->groupBy('author_id')
            ->having(['count(author_id) <' => 2], ['count(author_id)' => 'integer'])
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertStringEndsWith('HAVING count(author_id) < :c_0', $sql);

        $query->reset('having')->having(['count(author_id)' => 2], ['count(author_id)' => 'integer']);

        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertQueryContains('HAVING count\(author_id\) = :c_0', $sql);

        $query->reset('having')->having(function ($e) {
            return $e->add('count(author_id) = 1 + 1');
        });

        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertQueryContains('HAVING count\(author_id\) \= 1 \+ 1', $sql);
    }

    /**
     * Tests that Query::andHaving() can be used to concatenate conditions with AND
     * in the having clause
     */
    public function testSelectAndHaving(): void
    {
        $query = new SelectQuery();
        $query
            ->select(['total' => 'count(author_id)', 'author_id'])
            ->from('articles')
            ->groupBy('author_id')
            ->having(['count(author_id) >' => 2], ['count(author_id)' => 'integer'])
            ->andHaving(['count(author_id) <' => 2], ['count(author_id)' => 'integer'])
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertStringEndsWith('HAVING (count(author_id) > :c_0 AND count(author_id) < :c_1)', $sql);

        $query = new SelectQuery();
        $query
            ->select(['total' => 'count(author_id)', 'author_id'])
            ->from('articles')
            ->groupBy('author_id')
            ->andHaving(function ($e) {
                return $e->add('count(author_id) = 2 - 1');
            })
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertStringEndsWith('HAVING count(author_id) = 2 - 1', $sql);
    }

    /**
     * Test having casing with string expressions
     */
    public function testHavingAliasCasingStringExpression(): void
    {
        $query = new SelectQuery();
        $query
            ->select(['id'])
            ->from('authors', 'Authors')
            ->where([
                'FUNC( Authors.id) ='      => 1,
                'FUNC( Authors.id) IS NOT' => null,
            ])
            ->having(['COUNT(DISTINCT Authors.id) =' => 1])
        ;

        $this->assertSame(
            'SELECT id FROM authors Authors WHERE ' .
            '(FUNC( Authors.id) = :c_0 AND (FUNC( Authors.id)) IS NOT NULL) ' .
            'HAVING COUNT(DISTINCT Authors.id) = :c_1',
            trim($this->compiler->compile($query, new ValueBinder()))
        );
    }

    /**
     * Tests selecting rows using a limit clause
     */
    public function testSelectLimit(): void
    {
        $query = new SelectQuery();
        $query->select('id')->from('articles')->limit(1);
        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertStringEndsWith('LIMIT 1', $sql);
    }

    /**
     * Tests selecting rows combining a limit and offset clause
     */
    public function testSelectOffset(): void
    {
        $query = new SelectQuery();
        $query->select('id')->from('comments')->limit(1)->offset(0);
        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertStringEndsWith('LIMIT 1 OFFSET 0', $sql);
    }

    /**
     * Test Pages number.
     */
    public function testPageShouldStartAtOne(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Pages must start at 1.');

        $query = new SelectQuery();
        $query->from('comments')->page(0);
    }

    /**
     * Test selecting rows using the page() method.
     */
    public function testSelectPage(): void
    {
        $query = new SelectQuery();
        $query->select('id')->from('comments')->limit(1)->page(1);
        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertStringEndsWith('LIMIT 1 OFFSET 0', $sql);

        $query = new SelectQuery();
        $query->select('id')->from('comments')->limit(1)->page(2);
        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertStringEndsWith('LIMIT 1 OFFSET 1', $sql);

        $query = new SelectQuery();
        $query->select('id')->from('comments')->page(3, 10);
        $this->assertEquals(10, $query->clause('limit'));
        $this->assertEquals(20, $query->clause('offset'));

        $query = new SelectQuery();
        $query->select('id')->from('comments')->page(1);
        $this->assertEquals(25, $query->clause('limit'));
        $this->assertEquals(0, $query->clause('offset'));

        $query->select('id')->from('comments')->page(2);
        $this->assertEquals(25, $query->clause('limit'));
        $this->assertEquals(25, $query->clause('offset'));
    }

    /**
     * Tests that Query objects can be included inside the select clause
     * and be used as a normal field, including binding any passed parameter
     */
    public function testSubqueryInSelect(): void
    {
        $query = new SelectQuery();
        $subquery = (new SelectQuery())
            ->select('name')
            ->from('authors', 'b')
            ->where([$query->newExpr()->equalFields('b.id', 'a.id')])
        ;
        $query
            ->select(['id', 'name' => $subquery])
            ->from('comments', 'a')
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertEquals('SELECT id, (SELECT name FROM authors b WHERE b.id = a.id) AS name FROM comments a', $sql);
    }

    /**
     * Tests that Query objects can be included inside the from clause
     * and be used as a normal table, including binding any passed parameter
     */
    public function testSuqueryInFrom(): void
    {
        $query = new SelectQuery();
        $subquery = (new SelectQuery())
            ->select(['id', 'comment'])
            ->from('comments')
            ->where(['created >' => new DateTime('2007-03-18 10:45:23')], ['created' => 'datetime'])
        ;
        $query
            ->select(['say' => 'comment'])
            ->from($subquery, 'b')
            ->where(['id !=' => 3])
        ;

        $sql = $this->compiler->compile($query, $b = new ValueBinder());
        $this->assertEquals('SELECT comment AS say FROM (SELECT id, comment FROM comments WHERE created > :c_0) b WHERE id != :c_1', $sql);
        $this->assertCount(2, $b);
    }

    /**
     * Tests that Query objects can be included inside the where clause
     * and be used as a normal condition, including binding any passed parameter
     */
    public function testSubqueryInWhere(): void
    {
        $query = new SelectQuery();
        $subquery = (new SelectQuery())
            ->select(['id'])
            ->from('authors')
            ->where(['id' => 1])
        ;
        $query
            ->select(['name'])
            ->from('authors')
            ->where(['id !=' => $subquery])
        ;

        $sql = $this->compiler->compile($query, $b = new ValueBinder());
        $this->assertStringEndsWith('SELECT name FROM authors WHERE id != (SELECT id FROM authors WHERE id = :c_0)', $sql);
        $this->assertCount(1, $b);

        $query = new SelectQuery();
        $subquery = (new SelectQuery())
            ->select(['id'])
            ->from('comments')
            ->where(['created >' => new DateTime('2007-03-18 10:45:23')], ['created' => 'datetime'])
        ;
        $query
            ->select(['name'])
            ->from('authors')
            ->where(['id not in' => $subquery])
        ;

        $sql = $this->compiler->compile($query, $b = new ValueBinder());
        $this->assertEquals('SELECT name FROM authors WHERE id NOT IN (SELECT id FROM comments WHERE created > :c_0)', $sql);
        $this->assertCount(1, $b);
    }

    /**
     * Tests that Query objects can be included inside the where clause
     * and be used as a EXISTS and NOT EXISTS conditions
     */
    public function testSubqueryExistsWhere(): void
    {
        $query = new SelectQuery();
        $subQuery = (new SelectQuery())
            ->select(['id'])
            ->from('articles')
            ->where(function ($exp) {
                return $exp->equalFields('authors.id', 'articles.author_id');
            })
        ;
        $query
            ->select(['id'])
            ->from('authors')
            ->where(function ($exp) use ($subQuery) {
                return $exp->exists($subQuery);
            })
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertEquals('SELECT id FROM authors WHERE EXISTS (SELECT id FROM articles WHERE authors.id = articles.author_id)', $sql);

        $query = new SelectQuery();
        $subQuery = (new SelectQuery())
            ->select(['id'])
            ->from('articles')
            ->where(function ($exp) {
                return $exp->equalFields('authors.id', 'articles.author_id');
            })
        ;
        $query
            ->select(['id'])
            ->from('authors')
            ->where(function ($exp) use ($subQuery) {
                return $exp->notExists($subQuery);
            })
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertEquals('SELECT id FROM authors WHERE NOT EXISTS (SELECT id FROM articles WHERE authors.id = articles.author_id)', $sql);
    }

    /**
     * Tests that it is possible to use a subquery in a join clause
     */
    public function testSubqueryInJoin(): void
    {
        $subquery = (new SelectQuery())->select('*')->from('authors');

        $query = new SelectQuery();
        $query
            ->select(['title', 'name'])
            ->from('articles')
            ->join($subquery, 'b')
        ;
        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertEquals('SELECT title, name FROM articles INNER JOIN (SELECT * FROM authors) b ON 1 = 1', $sql);

        $query
            ->reset('join')
            ->join($subquery, 'a', $query->newExpr()->equalFields('b.id', 'articles.id'))
        ;
        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertEquals('SELECT title, name FROM articles INNER JOIN (SELECT * FROM authors) a ON b.id = articles.id', $sql);
    }

    /**
     * Tests that it is possible to one or multiple UNION statements in a query
     */
    public function testUnion(): void
    {
        $union = (new SelectQuery())->select(['id', 'title'])->from('articles', 'a');
        $query = new SelectQuery();
        $query->select(['id', 'comment'])
              ->from('comments', 'c')
              ->union($union)
        ;
        $sql = $this->compiler->compile($query, $b = new ValueBinder());
        $this->assertQueryContains('UNION \(SELECT id', $sql);

        $union->select(['foo' => 'id', 'bar' => 'title']);
        $union = (new SelectQuery())
            ->select(['id', 'name', 'other' => 'id', 'nameish' => 'name'])
            ->from('authors', 'b')
            ->where(['id ' => 1])
            ->orderBy(['id' => 'desc'])
        ;

        $query->select(['foo' => 'id', 'bar' => 'comment'])->union($union);
        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertEquals(
            "(SELECT id, comment, id AS foo, comment AS bar FROM comments c)\n" .
            "UNION (SELECT id, title, id AS foo, title AS bar FROM articles a)\n" .
            "UNION (SELECT id, name, id AS other, name AS nameish FROM authors b WHERE id = :c_0 ORDER BY id desc)",
            $sql
        );

        $union = (new SelectQuery())
            ->select(['id', 'title'])
            ->from('articles', 'c')
        ;
        $query
            ->reset('select', 'union')
            ->select(['id', 'comment'])
            ->union($union)
        ;
        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertEquals("(SELECT id, comment FROM comments c)\nUNION (SELECT id, title FROM articles c)", $sql);
    }

    /**
     * Tests that it is possible to add one or multiple INTERSECT statements in a query
     */
    public function testIntersect(): void
    {
        $intersect = (new SelectQuery())->select(['id', 'title'])->from('articles', 'a');
        $query = new SelectQuery();
        $query->select(['id', 'comment'])
              ->from('comments', 'c')
              ->intersect($intersect)
        ;
        $sql = $this->compiler->compile($query, $b = new ValueBinder());
        $this->assertQueryContains('INTERSECT \(SELECT id', $sql);

        $intersect->select(['foo' => 'id', 'bar' => 'title']);
        $intersect = (new SelectQuery())
            ->select(['id', 'name', 'other' => 'id', 'nameish' => 'name'])
            ->from('authors', 'b')
            ->where(['id ' => 1])
            ->orderBy(['id' => 'desc'])
        ;

        $query->select(['foo' => 'id', 'bar' => 'comment'])->intersect($intersect);
        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertEquals(
            "(SELECT id, comment, id AS foo, comment AS bar FROM comments c)\n" .
            "INTERSECT (SELECT id, title, id AS foo, title AS bar FROM articles a)\n" .
            "INTERSECT (SELECT id, name, id AS other, name AS nameish FROM authors b WHERE id = :c_0 ORDER BY id desc)",
            $sql
        );

        $intersect = (new SelectQuery())
            ->select(['id', 'title'])
            ->from('articles', 'c')
        ;
        $query
            ->reset('select', 'intersect')
            ->select(['id', 'comment'])
            ->intersect($intersect)
        ;
        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertEquals("(SELECT id, comment FROM comments c)\nINTERSECT (SELECT id, title FROM articles c)", $sql);
    }

    /**
     * Tests that it is possible to add one or multiple EXCEPT statements in a query
     */
    public function testExcept(): void
    {
        $except = (new SelectQuery())->select(['id', 'title'])->from('articles', 'a');
        $query = new SelectQuery();
        $query->select(['id', 'comment'])
              ->from('comments', 'c')
              ->except($except)
        ;
        $sql = $this->compiler->compile($query, $b = new ValueBinder());
        $this->assertQueryContains('EXCEPT \(SELECT id', $sql);

        $except->select(['foo' => 'id', 'bar' => 'title']);
        $except = (new SelectQuery())
            ->select(['id', 'name', 'other' => 'id', 'nameish' => 'name'])
            ->from('authors', 'b')
            ->where(['id ' => 1])
            ->orderBy(['id' => 'desc'])
        ;

        $query->select(['foo' => 'id', 'bar' => 'comment'])->except($except);
        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertEquals(
            "(SELECT id, comment, id AS foo, comment AS bar FROM comments c)\n" .
            "EXCEPT (SELECT id, title, id AS foo, title AS bar FROM articles a)\n" .
            "EXCEPT (SELECT id, name, id AS other, name AS nameish FROM authors b WHERE id = :c_0 ORDER BY id desc)",
            $sql
        );

        $except = (new SelectQuery())
            ->select(['id', 'title'])
            ->from('articles', 'c')
        ;
        $query
            ->reset('select', 'except')
            ->select(['id', 'comment'])
            ->except($except)
        ;
        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertEquals("(SELECT id, comment FROM comments c)\nEXCEPT (SELECT id, title FROM articles c)", $sql);
    }

    /**
     * Tests that it is possible to run unions with order by statements
     */
    public function testUnionOrderBy(): void
    {
        $union = (new SelectQuery())
            ->select(['id', 'title'])
            ->from('articles', 'a')
            ->orderBy(['a.id' => 'asc'])
        ;

        $query = new SelectQuery();
        $query
            ->select(['id', 'comment'])
            ->from('comments', 'c')
            ->orderBy(['c.id' => 'asc'])
            ->union($union)
        ;
        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertQueryContains('ORDER BY c.id', $sql);
    }

    /**
     * Tests that UNION ALL can be built
     */
    public function testUnionAll(): void
    {
        $union = (new SelectQuery())->select(['id', 'title'])->from('articles', 'a');
        $query = new SelectQuery();
        $query
            ->select(['id', 'comment'])
            ->from('comments', 'c')
            ->union($union)
        ;

        $union->select(['foo' => 'id', 'bar' => 'title']);
        $union = (new SelectQuery())
            ->select(['id', 'name', 'other' => 'id', 'nameish' => 'name'])
            ->from('authors', 'b')
            ->where(['id ' => 1])
            ->orderBy(['id' => 'desc'])
        ;

        $query->select(['foo' => 'id', 'bar' => 'comment'])->unionAll($union);

        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertQueryContains('UNION ALL \(SELECT', $sql);
    }

    /**
     * Tests that functions are correctly transformed and their parameters are bound
     *
     * @group FunctionExpression
     */
    public function testSQLFunctions(): void
    {
        $query = new SelectQuery();
        $query
            ->select(
                function ($q) {
                    return ['total' => $q->func()->count('*')];
                }
            )
            ->from('comments')
        ;
        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertEquals('SELECT (COUNT(*)) AS total FROM comments', $sql);

        $query = new SelectQuery();
        $query
            ->select([
                'c' => $query->func()->concat(['comment' => 'literal', ' is appended']),
            ])
            ->from('comments')
            ->orderBy(['c' => 'ASC'])
            ->limit(1)
        ;
        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertEquals('SELECT (CONCAT(comment, :param_0)) AS c FROM comments ORDER BY c ASC LIMIT 1', $sql);

        $query = new SelectQuery();
        $query
            ->select(['d' => $query->func()->dateDiff(['2012-01-05', '2012-01-02'])])
        ;
        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertEquals('SELECT (DATEDIFF(:param_0, :param_1)) AS d', $sql);

        $query = new SelectQuery();
        $query
            ->select(['d' => $query->func()->now('date')])
        ;
        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertEquals('SELECT (CURRENT_DATE()) AS d', $sql);

        $query = new SelectQuery();
        $query
            ->select(['d' => $query->func()->now('time')])
        ;
        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertEquals('SELECT (CURRENT_TIME()) AS d', $sql);

        $query = new SelectQuery();
        $query
            ->select([
                'd'              => $query->func()->datePart('day', 'created'),
                'm'              => $query->func()->datePart('month', 'created'),
                'y'              => $query->func()->datePart('year', 'created'),
                'de'             => $query->func()->extract('day', 'created'),
                'me'             => $query->func()->extract('month', 'created'),
                'ye'             => $query->func()->extract('year', 'created'),
                'wd'             => $query->func()->weekday('created'),
                'dow'            => $query->func()->dayOfWeek('created'),
                'addDays'        => $query->func()->dateAdd('created', 2, 'day'),
                'substractYears' => $query->func()->dateAdd('created', -2, 'year'),
            ])
            ->from('comments')
            ->where(['created' => '2007-03-18 10:45:23'])
        ;
        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertEquals(
            'SELECT (EXTRACT(DAY FROM created)) AS d, (EXTRACT(MONTH FROM created)) AS m, ' .
            '(EXTRACT(YEAR FROM created)) AS y, (EXTRACT(DAY FROM created)) AS de, (EXTRACT(MONTH FROM created)) AS me, ' .
            '(EXTRACT(YEAR FROM created)) AS ye, (DAYOFWEEK(created)) AS wd, (DAYOFWEEK(created)) AS dow, ' .
            '(DATE_ADD(created, INTERVAL 2 DAY)) AS addDays, (DATE_ADD(created, INTERVAL -2 YEAR)) AS substractYears ' .
            'FROM comments WHERE created = :c_0',
            $sql
        );
    }

    /**
     * Tests parameter binding
     */
    public function testBind(): void
    {
        $query = new SelectQuery();
        $query
            ->select(['id', 'comment'])
            ->from('comments')
            ->where(['created BETWEEN :foo AND :bar'])
            ->bind(':foo', new DateTime('2007-03-18 10:50:00'), 'datetime')
            ->bind(':bar', new DateTime('2007-03-18 10:52:00'), 'datetime')
        ;

        $this->assertCount(2, $query->getBinder());
        $this->assertArrayHasKey(':foo', $query->getBinder()->bindings());
        $this->assertArrayHasKey(':bar', $query->getBinder()->bindings());
    }

    /**
     * Test that epilog() will actually append a string to a select query
     */
    public function testAppendSelect(): void
    {
        $query = new SelectQuery();
        $query
            ->select(['id', 'title'])
            ->from('articles')
            ->where(['id' => 1])
            ->epilog('FOR UPDATE')
        ;
        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertStringContainsString('SELECT', $sql);
        $this->assertStringContainsString('FROM', $sql);
        $this->assertStringContainsString('WHERE', $sql);
        $this->assertSame(' FOR UPDATE', substr($sql, -11));
    }

    /**
     * Tests that it is possible to pass ExpressionInterface to isNull and isNotNull
     */
    public function testIsNullWithExpressions(): void
    {
        $query = new SelectQuery();
        $subquery = (new SelectQuery())
            ->select(['id'])
            ->from('authors')
            ->where(['id' => 1])
        ;

        $query
            ->select(['name'])
            ->from('authors')
            ->where(function ($exp) use ($subquery) {
                return $exp->isNotNull($subquery);
            })
        ;
        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertEquals('SELECT name FROM authors WHERE (SELECT id FROM authors WHERE id = :c_0) IS NOT NULL', $sql);

        $query = (new SelectQuery())
            ->select(['name'])
            ->from('authors')
            ->where(function ($exp) use ($subquery) {
                return $exp->isNull($subquery);
            })
        ;
        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertEquals('SELECT name FROM authors WHERE (SELECT id FROM authors WHERE id = :c_0) IS NULL', $sql);
    }

    /**
     * Tests that using the IS operator will automatically translate to the best
     * possible operator depending on the passed value
     */
    public function testDirectIsNull(): void
    {
        $query = (new SelectQuery())
            ->select(['name'])
            ->from('authors')
            ->where(['name IS' => null])
        ;
        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertQueryContains('WHERE \(name\) IS NULL', $sql);

        $query = (new SelectQuery())
            ->select(['name'])
            ->from('authors')
            ->where(['name IS' => 'larry'])
        ;
        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertQueryContains('WHERE name = :c_0', $sql);
    }

    /**
     * Tests that using the wrong NULL operator will throw meaningful exception instead of
     * cloaking as always-empty result set.
     */
    public function testIsNullInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expression "name" is missing operator (IS, IS NOT) with "null" value.');

        $query = (new SelectQuery())
            ->select(['name'])
            ->from('authors')
            ->where(['name' => null])
        ;
        $this->compiler->compile($query, new ValueBinder());
    }

    /**
     * Tests that using the wrong NULL operator will throw meaningful exception instead of
     * cloaking as always-empty result set.
     */
    public function testIsNotNullInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new SelectQuery())
            ->select(['name'])
            ->from('authors')
            ->where(['name !=' => null])
        ;
    }

    /**
     * Tests that using the IS NOT operator will automatically translate to the best
     * possible operator depending on the passed value
     */
    public function testDirectIsNotNull(): void
    {
        $query = (new SelectQuery())
            ->select(['name'])
            ->from('authors')
            ->where(['name IS NOT' => null])
        ;
        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertQueryContains('WHERE \(name\) IS NOT NULL', $sql);
    }

    public function testCloneWithExpression(): void
    {
        $query = new SelectQuery();
        $query
            ->with(
                new CommonTableExpression(
                    'cte',
                    new SelectQuery()
                )
            )
            ->with(function (CommonTableExpression $cte, Query $query) {
                return $cte
                    ->name('cte2')
                    ->query($query)
                ;
            })
        ;

        $clause = $query->clause('with');
        $clauseClone = (clone $query)->clause('with');

        $this->assertInstanceOf(WithExpression::class, $clause);

        foreach ($clause as $key => $value) {
            $this->assertEquals($value, $clauseClone->get($key));
            $this->assertNotSame($value, $clauseClone->get($key));
        }
    }

    public function testCloneSelectExpression(): void
    {
        $query = new SelectQuery();
        $query
            ->select($query->newExpr('select'))
            ->select(['alias' => $query->newExpr('select')])
        ;

        $clause = $query->clause('select');
        $clauseClone = (clone $query)->clause('select');

        $this->assertInstanceOf(SelectClauseExpression::class, $clause);

        foreach ($clause->fields() as $key => $value) {
            $this->assertEquals($value, $clauseClone->fields()->get($key));
            $this->assertNotSame($value, $clauseClone->fields()->get($key));
        }
    }

    public function testCloneDistinctExpression(): void
    {
        $query = new SelectQuery();
        $query->distinct($query->newExpr('distinct'));

        $clause = $query->clause('select')->distinct();
        $clauseClone = (clone $query)->clause('select')->distinct();

        $this->assertInstanceOf(DistinctExpression::class, $clause);

        $this->assertEquals($clause, $clauseClone);
        $this->assertNotSame($clause, $clauseClone);
    }

    public function testCloneModifierExpression(): void
    {
        $query = new SelectQuery();
        $query->modifier($query->newExpr('modifier'));

        $clause = $query->clause('select')->modifier();
        $clauseClone = (clone $query)->clause('select')->modifier();

        $this->assertInstanceOf(ModifierExpression::class, $clause);

        foreach ($clause->all() as $key => $value) {
            $this->assertEquals($value, $clauseClone->get($key));
            $this->assertNotSame($value, $clauseClone->get($key));
        }
    }

    public function testCloneFromExpression(): void
    {
        $query = new SelectQuery();
        $query->from(new SelectQuery(), 'alias');

        $clause = $query->clause('from');
        $clauseClone = (clone $query)->clause('from');

        $this->assertInstanceOf(FromExpression::class, $clause);

        foreach ($clause as $key => $value) {
            $this->assertEquals($value, $clauseClone->get($key));
            $this->assertNotSame($value, $clauseClone->get($key));
        }
    }

    public function testCloneJoinExpression(): void
    {
        $query = new SelectQuery();
        $query
            ->innerJoin(new SelectQuery(), 'alias_inner', ['alias_inner.fk = parent.pk'])
            ->leftJoin(new SelectQuery(), 'alias_left', ['alias_left.fk = parent.pk'])
            ->rightJoin(new SelectQuery(), 'alias_right', ['alias_right.fk = parent.pk'])
        ;

        $clause = $query->clause('join');
        $clauseClone = (clone $query)->clause('join');

        $this->assertInstanceOf(JoinExpression::class, $clause);

        foreach ($clause as $key => $value) {
            $this->assertEquals($value->getTable(), $clauseClone->get($key)->getTable());
            $this->assertNotSame($value->getTable(), $clauseClone->get($key)->getTable());

            $this->assertEquals($value->getConditions(), $clauseClone->get($key)->getConditions());
            $this->assertNotSame($value->getConditions(), $clauseClone->get($key)->getConditions());
        }
    }

    public function testCloneWhereExpression(): void
    {
        $query = new SelectQuery();
        $query
            ->where($query->newExpr('where'))
            ->where(['field' => $query->newExpr('where')])
        ;

        $clause = $query->clause('where');
        $clauseClone = (clone $query)->clause('where');

        $this->assertInstanceOf(ExpressionInterface::class, $clause);

        $this->assertEquals($clause, $clauseClone);
        $this->assertNotSame($clause, $clauseClone);
    }

    public function testCloneGroupExpression(): void
    {
        $query = new SelectQuery();
        $query->groupBy($query->newExpr('group'));

        $clause = $query->clause('group');
        $clauseClone = (clone $query)->clause('group');

        $this->assertInstanceOf(GroupByExpression::class, $clause);

        foreach ($clause as $key => $value) {
            $this->assertEquals($value, $clauseClone->get($key));
            $this->assertNotSame($value, $clauseClone->get($key));
        }
    }

    public function testCloneHavingExpression(): void
    {
        $query = new SelectQuery();
        $query->having($query->newExpr('having'));

        $clause = $query->clause('having');
        $clauseClone = (clone $query)->clause('having');

        $this->assertInstanceOf(ExpressionInterface::class, $clause);

        $this->assertEquals($clause, $clauseClone);
        $this->assertNotSame($clause, $clauseClone);
    }

    public function testCloneWindowExpression(): void
    {
        $query = new SelectQuery();
        $query
            ->window('window1', new WindowExpression())
            ->window('window2', function (WindowExpression $window) {
                return $window;
            })
        ;

        $clause = $query->clause('window');
        $clauseClone = (clone $query)->clause('window');

        $this->assertInstanceOf(WindowClauseExpression::class, $clause);

        foreach ($clause as $key => $value) {
            $this->assertEquals($value->getName(), $clauseClone->get($key)->getName());
            $this->assertNotSame($value->getName(), $clauseClone->get($key)->getName());

            $this->assertEquals($value->getWindow(), $clauseClone->get($key)->getWindow());
            $this->assertNotSame($value->getWindow(), $clauseClone->get($key)->getWindow());
        }
    }

    public function testCloneOrderExpression(): void
    {
        $query = new SelectQuery();
        $query
            ->orderBy($query->newExpr('order'))
            ->orderBy($query->newExpr('order_asc'), OrderDirection::ASC)
            ->orderBy($query->newExpr('order_desc'), OrderDirection::DESC)
        ;

        $clause = $query->clause('order');
        $clauseClone = (clone $query)->clause('order');

        $this->assertInstanceOf(ExpressionInterface::class, $clause);

        $this->assertEquals($clause, $clauseClone);
        $this->assertNotSame($clause, $clauseClone);
    }

    public function testCloneLimitExpression(): void
    {
        $query = new SelectQuery();
        $query->limit($query->newExpr('1'));

        $clause = $query->clause('limit');
        $clauseClone = (clone $query)->clause('limit');

        $this->assertInstanceOf(ExpressionInterface::class, $clause);

        $this->assertEquals($clause, $clauseClone);
        $this->assertNotSame($clause, $clauseClone);
    }

    public function testCloneOffsetExpression(): void
    {
        $query = new SelectQuery();
        $query->offset($query->newExpr('1'));

        $clause = $query->clause('offset');
        $clauseClone = (clone $query)->clause('offset');

        $this->assertInstanceOf(ExpressionInterface::class, $clause);

        $this->assertEquals($clause, $clauseClone);
        $this->assertNotSame($clause, $clauseClone);
    }

    public function testCloneUnionExpression(): void
    {
        $query = new SelectQuery();
        $query->where(['id' => 1]);

        $query2 = new SelectQuery();
        $query2->where(['id' => 2]);

        $query->union($query2);

        $clause = $query->clause('union');
        $clauseClone = (clone $query)->clause('union');

        $this->assertInstanceOf(UnionExpression::class, $clause);

        foreach ($clause->all() as $key => $value) {
            $this->assertEquals($value->getQuery(), $clauseClone->get($key)->getQuery());
            $this->assertNotSame($value->getQuery(), $clauseClone->get($key)->getQuery());
        }
    }

    public function testCloneEpilogExpression(): void
    {
        $query = new SelectQuery();
        $query->epilog($query->newExpr('epilog'));

        $clause = $query->clause('epilog');
        $clauseClone = (clone $query)->clause('epilog');

        $this->assertInstanceOf(ExpressionInterface::class, $clause);

        $this->assertEquals($clause, $clauseClone);
        $this->assertNotSame($clause, $clauseClone);
    }

    /**
     * Test that cloning goes deep.
     */
    public function testDeepClone(): void
    {
        $query = new SelectQuery();
        $query->select(['id', 'title' => $query->func()->concat(['title' => 'literal', 'test'])])
              ->from('articles')
              ->where(['Articles.id' => 1])
              ->offset(10)
              ->limit(1)
              ->orderBy(['Articles.id' => 'DESC'])
        ;
        $dupe = clone $query;

        $this->assertEquals($query->clause('where'), $dupe->clause('where'));
        $this->assertNotSame($query->clause('where'), $dupe->clause('where'));
        $dupe->where(['Articles.title' => 'thinger']);
        $this->assertNotEquals($query->clause('where'), $dupe->clause('where'));

        $this->assertNotSame(
            $query->clause('select')->fields()->get('title'),
            $dupe->clause('select')->fields()->get('title')
        );
        $this->assertEquals($query->clause('order'), $dupe->clause('order'));
        $this->assertNotSame($query->clause('order'), $dupe->clause('order'));

        $query->orderBy(['Articles.title' => 'ASC']);
        $this->assertNotEquals($query->clause('order'), $dupe->clause('order'));
    }

    /**
     * Test removeJoin().
     */
    public function testRemoveJoin(): void
    {
        $query = new SelectQuery();
        $query
            ->select(['id', 'title'])
            ->from('articles')
            ->join('authors', 'authors', on: ['articles.author_id = authors.id'])
        ;
        $this->assertTrue($query->clause('join')->has('authors'));

        $this->assertSame($query, $query->removeJoin('authors'));
        $this->assertFalse($query->clause('join')->has('authors'));
    }

    /**
     * Tests that query expressions can be used for ordering.
     */
    public function testOrderBySubquery(): void
    {
        $subquery = new SelectQuery();
        $subquery
            ->select(
                $subquery->newExpr()->case()->when(['a.published' => 'N'])->then(1)->else(0)
            )
            ->from('articles', 'a')
            ->where([
                'a.id = articles.id',
            ])
        ;

        $query = select()
            ->select(['id'])
            ->from('articles')
            ->orderBy($subquery, OrderDirection::DESC)
            ->orderBy('id')
        ;

        $this->assertQueryContains(
            'SELECT id FROM articles ORDER BY \(' .
            'SELECT \(CASE WHEN a\.published = \:c_0 THEN \:c_1 ELSE \:c_2 END\) ' .
            'FROM articles a ' .
            'WHERE a\.id = articles\.id' .
            '\) DESC, id ASC',
            $this->compiler->compile($query, new ValueBinder())
        );
    }

    /**
     * Test that reusing expressions will duplicate bindings
     */
    public function testReusingExpressions(): void
    {
        $query = new SelectQuery();

        $subqueryA = new SelectQuery();
        $subqueryA
            ->select('count(*)')
            ->from('articles', 'a')
            ->where([
                'a.id = articles.id',
                'a.published' => 'Y',
            ])
        ;

        $subqueryB = new SelectQuery();
        $subqueryB
            ->select('count(*)')
            ->from('articles', 'b')
            ->where([
                'b.id = articles.id',
                'b.published' => 'N',
            ])
        ;

        $query
            ->select([
                'id',
                'computedA' => $subqueryA,
                'computedB' => $subqueryB,
            ])
            ->from('articles')
            ->orderBy($subqueryB, OrderDirection::DESC)
            ->orderBy('id')
        ;

        $this->assertQueryContains(
            'SELECT id, ' .
            '\(SELECT count\(\*\) FROM articles a WHERE \(a\.id = articles\.id AND a\.published = :c_0\)\) AS computedA, ' .
            '\(SELECT count\(\*\) FROM articles b WHERE \(b\.id = articles\.id AND b\.published = :c_1\)\) AS computedB ' .
            'FROM articles ' .
            'ORDER BY \(' .
            'SELECT count\(\*\) FROM articles b WHERE \(b\.id = articles\.id AND b\.published = :c_2\)' .
            '\) DESC, id ASC',
            $this->compiler->compile($query, $b = new ValueBinder())
        );

        $this->assertCount(3, $b->bindings());
        $this->assertArrayHasKey(':c_0', $b->bindings());
        $this->assertArrayHasKey(':c_1', $b->bindings());
        $this->assertArrayHasKey(':c_2', $b->bindings());
    }

    /**
     * Tests creating StringExpression.
     */
    public function testStringExpression(): void
    {
        $collation = 'en_US.utf8';

        $query = new SelectQuery();
        $query->select(['test_string' => new StringExpression('testString', $collation)]);

        $expected = "SELECT (:c_0 COLLATE $collation) AS test_string";

        $this->assertEquals($expected, $this->compiler->compile($query, new ValueBinder()));
    }

    /**
     * Tests setting identifier collation.
     */
    public function testIdentifierCollation(): void
    {
        $collation = 'en_US.utf8';

        $query = (new SelectQuery())
            ->select(['test_string' => new IdentifierExpression('title', $collation)])
            ->from('articles')
            ->where(['id' => 1])
        ;

        $expected = "SELECT \(title COLLATE $collation\) AS test_string";

        $this->assertQueryContains($expected, $this->compiler->compile($query, new ValueBinder()));
    }

    /**
     * Simple expressions from the point of view of the query expression
     * object are expressions where the field contains one space at most.
     */
    public function testOperatorsInSimpleConditionsAreCaseInsensitive(): void
    {
        $query = (new SelectQuery())
            ->select('id')
            ->from('articles')
            ->where(['id in' => [1, 2, 3]])
        ;

        $this->assertQueryContains(
            'SELECT id FROM articles WHERE id IN \(:c_0,:c_1,:c_2\)',
            $this->compiler->compile($query, new ValueBinder())
        );

        $query = (new SelectQuery())
            ->select('id')
            ->from('articles')
            ->where(['id IN' => [1, 2, 3]])
        ;

        $this->assertQueryContains(
            'SELECT id FROM articles WHERE id IN \(:c_0,:c_1,:c_2\)',
            $this->compiler->compile($query, new ValueBinder())
        );
    }

    /**
     * Complex expressions from the point of view of the query expression
     * object are expressions where the field contains multiple spaces.
     */
    public function testOperatorsInComplexConditionsAreCaseInsensitive(): void
    {
        $query = (new SelectQuery())
            ->select('id')
            ->from('profiles')
            ->where(['CONCAT(first_name, " ", last_name) in' => ['foo bar', 'baz 42']])
        ;

        $this->assertSame(
            'SELECT id FROM profiles WHERE CONCAT(first_name, " ", last_name) IN (:c_0,:c_1)',
            $this->compiler->compile($query, new ValueBinder())
        );

        $query = (new SelectQuery())
            ->select('id')
            ->from('profiles')
            ->where(['CONCAT(first_name, " ", last_name) IN' => ['foo bar', 'baz 42']])
        ;

        $this->assertSame(
            'SELECT id FROM profiles WHERE CONCAT(first_name, " ", last_name) IN (:c_0,:c_1)',
            $this->compiler->compile($query, new ValueBinder())
        );

        $query = (new SelectQuery())
            ->select('id')
            ->from('profiles')
            ->where(['CONCAT(first_name, " ", last_name) not in' => ['foo bar', 'baz 42']])
        ;

        $this->assertSame(
            'SELECT id FROM profiles WHERE CONCAT(first_name, " ", last_name) NOT IN (:c_0,:c_1)',
            $this->compiler->compile($query, new ValueBinder())
        );

        $query = (new SelectQuery())
            ->select('id')
            ->from('profiles')
            ->where(['CONCAT(first_name, " ", last_name) NOT IN' => ['foo bar', 'baz 42']])
        ;

        $this->assertSame(
            'SELECT id FROM profiles WHERE CONCAT(first_name, " ", last_name) NOT IN (:c_0,:c_1)',
            $this->compiler->compile($query, new ValueBinder())
        );
    }
}
