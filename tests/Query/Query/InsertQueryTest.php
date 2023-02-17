<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Tests\Query\Query;

use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Somnambulist\Components\QueryBuilder\Compiler\QueryCompiler;
use Somnambulist\Components\QueryBuilder\Query\ExpressionInterface;
use Somnambulist\Components\QueryBuilder\Query\Type\InsertQuery;
use Somnambulist\Components\QueryBuilder\Query\Type\SelectQuery;
use Somnambulist\Components\QueryBuilder\Tests\Support\QueryAssertsTrait;
use Somnambulist\Components\QueryBuilder\Tests\Support\QueryCompilerBuilderTrait;
use Somnambulist\Components\QueryBuilder\ValueBinder;

/**
 * Tests InsertQuery class
 */
class InsertQueryTest extends TestCase
{
    use QueryAssertsTrait;
    use QueryCompilerBuilderTrait;

    protected ?QueryCompiler $compiler = null;

    public function setUp(): void
    {
        $this->compiler = $this->buildCompiler();
    }

    public function tearDown(): void
    {
        $this->compiler = null;
    }

    /**
     * You cannot call values() before insert() it causes all sorts of pain.
     */
    public function testInsertValuesBeforeInsertFailure(): void
    {
        $this->expectException(Exception::class);
        $query = new InsertQuery();
        $query->values([
            'id' => 1,
            'title' => 'mark',
            'body' => 'test insert',
        ]);
    }

    /**
     * Inserting nothing should not generate an error.
     */
    public function testInsertNothing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('At least 1 column is required to perform an insert.');
        $query = new InsertQuery();
        $query->insert([]);
    }

    /**
     * Test insert() with no into()
     */
    public function testInsertNoInto(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Could not compile insert query. No table was specified');
        $query = new InsertQuery();
        $query->insert(['title', 'body']);

        $this->compiler->compile($query, new ValueBinder());
    }

    /**
     * Test insert overwrites values
     */
    public function testInsertOverwritesValues(): void
    {
        $query = new InsertQuery();
        $query
            ->insert(['title', 'body'])
            ->insert(['title'])
            ->into('articles')
            ->values([
                'title' => 'mark',
            ])
        ;

        $result = $this->compiler->compile($query, new ValueBinder());

        $this->assertQueryContains(
            'INSERT INTO articles \(title\) (OUTPUT INSERTED\.\* )?' .
            'VALUES \(:c_0\)',
            $result
        );
    }

    /**
     * Test inserting a single row.
     */
    public function testInsertSimple(): void
    {
        $query = new InsertQuery();
        $query->insert(['title', 'body'])
            ->into('articles')
            ->values([
                'title' => 'mark',
                'body' => 'test insert',
            ]);
        $result = $this->compiler->compile($query, new ValueBinder());
        $this->assertQueryContains(
            'INSERT INTO articles \(title, body\) (OUTPUT INSERTED\.\* )?' .
            'VALUES \(:c_0, :c_1\)',
            $result
        );
    }

    /**
     * Test insert queries quote integer column names
     */
    public function testInsertQuoteColumns(): void
    {
        $query = new InsertQuery();
        $query->insert([123])
            ->into('articles')
            ->values([
                '123' => 'mark',
            ]);
        $result = $this->compiler->compile($query, new ValueBinder());
        $this->assertQueryContains(
            'INSERT INTO articles \(123\) (OUTPUT INSERTED\.\* )?' .
            'VALUES \(:c_0\)',
            $result
        );
    }

    /**
     * Test an insert when not all the listed fields are provided.
     * Columns should be matched up where possible.
     */
    public function testInsertSparseRow(): void
    {
        $query = new InsertQuery();
        $query->insert(['title', 'body'])
            ->into('articles')
            ->values([
                'title' => 'mark',
            ]);
        $result = $this->compiler->compile($query, new ValueBinder());
        $this->assertQueryContains(
            'INSERT INTO articles \(title, body\) (OUTPUT INSERTED\.\* )?' .
            'VALUES \(:c_0, :c_1\)',
            $result
        );
    }

    /**
     * Test inserting multiple rows with sparse data.
     */
    public function testInsertMultipleRowsSparse(): void
    {
        $query = new InsertQuery();
        $query->insert(['title', 'body'])
            ->into('articles')
            ->values([
                'body' => 'test insert',
            ])
            ->values([
                'title' => 'jose',
            ]);

        $sql = $this->compiler->compile($query, $v = new ValueBinder());

        $this->assertQueryContains('INSERT INTO articles \(title, body\) VALUES \(:c_0, :c_1\), \(:c_2, :c_3\)', $sql);
        $this->assertCount(4, $v);
    }

    /**
     * Test that INSERT INTO ... SELECT works.
     */
    public function testInsertFromSelect(): void
    {
        $select = (new SelectQuery())->select(['name', "'some text'", 99])
            ->from('authors')
            ->where(['id' => 1]);

        $query = new InsertQuery();
        $query->insert(
            ['title', 'body', 'author_id'],
            ['title' => 'string', 'body' => 'string', 'author_id' => 'integer']
        )
        ->into('articles')
        ->values($select);

        $result = $this->compiler->compile($query, new ValueBinder());

        $this->assertQueryContains(
            'INSERT INTO articles \(title, body, author_id\) (OUTPUT INSERTED\.\* )?SELECT',
            $result
        );
        $this->assertQueryContains(
            'SELECT name, \'some text\', 99 FROM authors',
            $result
        );
    }

    /**
     * Test that an exception is raised when mixing query + array types.
     */
    public function testInsertFailureMixingTypesArrayFirst(): void
    {
        $this->expectException(Exception::class);
        $query = new InsertQuery();
        $query
            ->insert(['name'])
            ->into('articles')
            ->values(['name' => 'mark'])
            ->values(new InsertQuery())
        ;
    }

    /**
     * Test that an exception is raised when mixing query + array types.
     */
    public function testInsertFailureMixingTypesQueryFirst(): void
    {
        $this->expectException(Exception::class);
        $query = new InsertQuery();
        $query
            ->insert(['name'])
            ->into('articles')
            ->values(new InsertQuery())
            ->values(['name' => 'mark'])
        ;
    }

    /**
     * Test that insert can use expression objects as values.
     */
    public function testInsertExpressionValues(): void
    {
        $query = new InsertQuery();
        $query->insert(['title', 'author_id'])
            ->into('articles')
            ->values(['title' => $query->newExpr("SELECT 'jose'"), 'author_id' => 99]);

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertQueryContains('\(SELECT \'jose\'\)', $sql);
    }

    /**
     * Test use of modifiers in a INSERT query
     *
     * Testing the generated SQL since the modifiers are usually different per driver
     */
    public function testInsertModifiers(): void
    {
        $query = new InsertQuery();
        $query
            ->insert(['title'])
            ->into('articles')
            ->values(['title' => 'foo'])
            ->modifier('IGNORE')
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertQueryContains(
            'INSERT IGNORE INTO articles \(title\) (OUTPUT INSERTED\.\* )?',
            $sql
        );

        $query = new InsertQuery();
        $query
            ->insert(['title'])
            ->into('articles')
            ->values(['title' => 'foo'])
            ->modifier('IGNORE', 'LOW_PRIORITY')
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertQueryContains(
            'INSERT IGNORE LOW_PRIORITY INTO articles \(title\) (OUTPUT INSERTED\.\* )?',
            $sql
        );
    }

    public function testCloneValuesExpression(): void
    {
        $query = new InsertQuery();
        $query
            ->insert(['column'])
            ->into('table')
            ->values(['column' => $query->newExpr('value')]);

        $clause = $query->clause('values');
        $clauseClone = (clone $query)->clause('values');

        $this->assertInstanceOf(ExpressionInterface::class, $clause);

        $this->assertEquals($clause, $clauseClone);
        $this->assertNotSame($clause, $clauseClone);
    }

    /**
     * Test that epilog() will actually append a string to an insert query
     */
    public function testAppendInsert(): void
    {
        $query = new InsertQuery();
        $query
            ->insert(['id', 'title'])
            ->into('articles')
            ->values([1, 'a title'])
            ->epilog('RETURNING id')
        ;

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertStringContainsString('INSERT', $sql);
        $this->assertStringContainsString('INTO', $sql);
        $this->assertStringContainsString('VALUES', $sql);
        $this->assertSame(' RETURNING id', substr($sql, -13));
    }
}
