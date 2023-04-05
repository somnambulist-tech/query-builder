<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Tests\Query\Query;

use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Somnambulist\Components\QueryBuilder\Compiler\Compiler;
use Somnambulist\Components\QueryBuilder\Query\Expression;
use Somnambulist\Components\QueryBuilder\Query\Type\InsertQuery;
use Somnambulist\Components\QueryBuilder\Query\Type\SelectQuery;
use Somnambulist\Components\QueryBuilder\Tests\Support\QueryAssertsTrait;
use Somnambulist\Components\QueryBuilder\Tests\Support\QueryCompilerBuilderTrait;
use Somnambulist\Components\QueryBuilder\ValueBinder;

class InsertQueryTest extends TestCase
{
    use QueryAssertsTrait;
    use QueryCompilerBuilderTrait;

    protected ?Compiler $compiler = null;

    public function setUp(): void
    {
        $this->compiler = $this->buildCompiler();
    }

    public function tearDown(): void
    {
        $this->compiler = null;
    }

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

    public function testInsertNothing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('At least 1 column is required to perform an insert.');
        $query = new InsertQuery();
        $query->insert([]);
    }

    public function testInsertNoInto(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Could not compile insert query. No table was specified');
        $query = new InsertQuery();
        $query->insert(['title', 'body']);

        $this->compiler->compile($query, new ValueBinder());
    }

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

    public function testInsertExpressionValues(): void
    {
        $query = new InsertQuery();
        $query->insert(['title', 'author_id'])
            ->into('articles')
            ->values(['title' => $query->newExpr("SELECT 'jose'"), 'author_id' => 99]);

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertQueryContains('\(SELECT \'jose\'\)', $sql);
    }

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

        $this->assertInstanceOf(Expression::class, $clause);

        $this->assertEquals($clause, $clauseClone);
        $this->assertNotSame($clause, $clauseClone);
    }

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
