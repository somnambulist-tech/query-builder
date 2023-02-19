<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Tests\Query\Query;

use DateTime;
use Exception;
use PHPUnit\Framework\TestCase;
use Somnambulist\Components\QueryBuilder\Compiler\CompilerInterface;
use Somnambulist\Components\QueryBuilder\Query\ExpressionInterface;
use Somnambulist\Components\QueryBuilder\Query\Expressions\IdentifierExpression;
use Somnambulist\Components\QueryBuilder\Query\Type\SelectQuery;
use Somnambulist\Components\QueryBuilder\Query\Type\UpdateQuery;
use Somnambulist\Components\QueryBuilder\Tests\Support\QueryAssertsTrait;
use Somnambulist\Components\QueryBuilder\Tests\Support\QueryCompilerBuilderTrait;
use Somnambulist\Components\QueryBuilder\ValueBinder;

/**
 * Tests UpdateQuery class
 */
class UpdateQueryTest extends TestCase
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
     * Test a simple update.
     */
    public function testUpdateSimple(): void
    {
        $query = new UpdateQuery();
        $query->update('authors')
            ->set('name', 'mark')
            ->where(['id' => 1]);
        $result = $this->compiler->compile($query, new ValueBinder());
        $this->assertQueryContains('UPDATE authors SET name = :', $result);
    }

    /**
     * Test update with multiple fields.
     */
    public function testUpdateMultipleFields(): void
    {
        $query = new UpdateQuery();
        $query->update('articles')
            ->set('title', 'mark', 'string')
            ->set('body', 'some text', 'string')
            ->where(['id' => 1]);
        $result = $this->compiler->compile($query, new ValueBinder());

        $this->assertQueryContains(
            'UPDATE articles SET title = :c_0 , body = :c_1',
            $result
        );

        $this->assertQueryContains(' WHERE id = :c_2$', $result);
    }

    /**
     * Test updating multiple fields with an array.
     */
    public function testUpdateMultipleFieldsArray(): void
    {
        $query = new UpdateQuery();
        $query->update('articles')
            ->set([
                'title' => 'mark',
                'body' => 'some text',
            ], ['title' => 'string', 'body' => 'string'])
            ->where(['id' => 1]);
        $result = $this->compiler->compile($query, new ValueBinder());

        $this->assertQueryContains(
            'UPDATE articles SET title = :c_0 , body = :c_1',
            $result
        );
        $this->assertQueryContains('WHERE id = :', $result);
    }

    /**
     * Test updates with an expression.
     */
    public function testUpdateWithExpression(): void
    {
        $query = new UpdateQuery();

        $expr = $query->newExpr()->equalFields('article_id', 'user_id');

        $query->update('comments')
            ->set($expr)
            ->where(['id' => 1]);
        $result = $this->compiler->compile($query, new ValueBinder());

        $this->assertQueryContains(
            'UPDATE comments SET article_id = user_id WHERE id = :',
            $result
        );
    }

    /**
     * Tests update with subquery that references itself
     */
    public function testUpdateSubquery(): void
    {
        $subquery = new SelectQuery();
        $subquery
            ->select('created')
            ->from('comments', 'c')
            ->where(['c.id' => new IdentifierExpression('comments.id')]);

        $query = new UpdateQuery();
        $query->update('comments')
            ->set('updated', $subquery);

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertEquals(
            'UPDATE comments SET updated = (SELECT created FROM comments c WHERE c.id = comments.id)',
            $sql
        );
    }

    /**
     * Test update with array fields and types.
     */
    public function testUpdateArrayFields(): void
    {
        $query = new UpdateQuery();
        $date = new DateTime();
        $query->update('comments')
            ->set(['comment' => 'mark', 'created' => $date], ['created' => 'date'])
            ->where(['id' => 1]);
        $result = $this->compiler->compile($query, new ValueBinder());

        $this->assertQueryContains(
            'UPDATE comments SET comment = :c_0 , created = :c_1',
            $result
        );

        $this->assertQueryContains(' WHERE id = :c_2$', $result);
    }

    /**
     * Test update with callable in set
     */
    public function testUpdateSetCallable(): void
    {
        $query = new UpdateQuery();
        $date = new DateTime();
        $query->update('comments')
            ->set(function ($exp) use ($date) {
                return $exp
                    ->eq('comment', 'mark')
                    ->eq('created', $date, 'date');
            })
            ->where(['id' => 1]);
        $result = $this->compiler->compile($query, new ValueBinder());

        $this->assertQueryContains(
            'UPDATE comments SET comment = :c_0 , created = :c_1',
            $result
        );

        $this->assertQueryContains(' WHERE id = :c_2$', $result);
    }

    /**
     * Tests that aliases are stripped from update query conditions
     * where possible.
     */
    public function testUpdateStripAliasesFromConditions(): void
    {
        $query = new UpdateQuery();

        $query
            ->update('authors')
            ->set(['name' => 'name'])
            ->where([
                'OR' => [
                    'a.id' => 1,
                    'a.name IS' => null,
                    'a.email IS NOT' => null,
                    'AND' => [
                        'b.name NOT IN' => ['foo', 'bar'],
                        'OR' => [
                            $query->newExpr()->eq(new IdentifierExpression('c.name'), 'zap'),
                            'd.name' => 'baz',
                            (new SelectQuery())->select(['e.name'])->where(['e.name' => 'oof']),
                        ],
                    ],
                ],
            ]);

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertQueryContains(
            'UPDATE authors SET name = :c_0 WHERE \(' .
                'id = :c_1 OR \(name\) IS NULL OR \(email\) IS NOT NULL OR \(' .
                    'name NOT IN \(:c_2,:c_3\) AND \(' .
                        'name = :c_4 OR name = :c_5 OR \(SELECT e\.name WHERE e\.name = :c_6\)' .
                    '\)' .
                '\)' .
            '\)',
            $sql
        );
    }

    /**
     * Tests that update queries that contain joins do trigger a notice,
     * warning about possible incompatibilities with aliases being removed
     * from the conditions.
     */
    public function testUpdateRemovingAliasesCanBreakJoins(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Aliases are being removed from conditions for UPDATE/DELETE queries, this can break references to joined tables.');
        $query = new UpdateQuery();

        $query
            ->update('authors')
            ->set(['name' => 'name'])
            ->innerJoin('art', 'articles')
            ->where(['a.id' => 1]);

        $this->compiler->compile($query, new ValueBinder());
    }

    /**
     * Test that epilog() will actually append a string to an update query
     */
    public function testAppendUpdate(): void
    {
        $query = new UpdateQuery();
        $query
            ->update('articles')
            ->set(['title' => 'foo'])
            ->where(['id' => 1])
            ->epilog('RETURNING id')
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertStringContainsString('UPDATE', $sql);
        $this->assertStringContainsString('SET', $sql);
        $this->assertStringContainsString('WHERE', $sql);
        $this->assertSame(' RETURNING id', substr($sql, -13));
    }

    public function testCloneUpdateExpression(): void
    {
        $query = new UpdateQuery();
        $query->update($query->newExpr('update'));

        $clause = $query->clause('update');
        $clauseClone = (clone $query)->clause('update');

        $this->assertIsArray($clause);

        foreach ($clause as $key => $value) {
            $this->assertEquals($value, $clauseClone[$key]);
            $this->assertNotSame($value, $clauseClone[$key]);
        }
    }

    public function testCloneSetExpression(): void
    {
        $query = new UpdateQuery();
        $query
            ->update('table')
            ->set(['column' => $query->newExpr('value')]);

        $clause = $query->clause('set');
        $clauseClone = (clone $query)->clause('set');

        $this->assertInstanceOf(ExpressionInterface::class, $clause);

        $this->assertEquals($clause, $clauseClone);
        $this->assertNotSame($clause, $clauseClone);
    }

    /**
     * Test use of modifiers in a UPDATE query
     *
     * Testing the generated SQL since the modifiers are usually different per driver
     */
    public function testUpdateModifiers(): void
    {
        $query = new UpdateQuery();
        $query
            ->update('authors')
            ->set('name', 'mark')
            ->modifier('TOP 10 PERCENT');

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertQueryContains('UPDATE TOP 10 PERCENT authors SET name = :c_0', $sql);

        $query = new UpdateQuery();
        $query
            ->update('authors')
            ->set('name', 'mark')
            ->modifier('TOP 10 PERCENT', 'FOO');

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertQueryContains(
            'UPDATE TOP 10 PERCENT FOO authors SET name = :c_0',
            $sql
        );

        $query = new UpdateQuery();
        $query
            ->update('authors')
            ->set('name', 'mark')
            ->modifier($query->newExpr('TOP 10 PERCENT'));

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertQueryContains(
            'UPDATE TOP 10 PERCENT authors SET name = :c_0',
            $sql
        );
    }
}
