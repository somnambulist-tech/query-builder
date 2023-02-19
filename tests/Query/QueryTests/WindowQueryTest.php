<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Tests\Query\QueryTests;

use Exception;
use PHPUnit\Framework\TestCase;
use Somnambulist\Components\QueryBuilder\Compiler\CompilerInterface;
use Somnambulist\Components\QueryBuilder\Query\Expressions\QueryExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\WindowExpression;
use Somnambulist\Components\QueryBuilder\Query\Type\SelectQuery;
use Somnambulist\Components\QueryBuilder\Tests\Support\QueryAssertsTrait;
use Somnambulist\Components\QueryBuilder\Tests\Support\QueryCompilerBuilderTrait;
use Somnambulist\Components\QueryBuilder\ValueBinder;

/**
 * Tests WindowExpression class
 */
class WindowQueryTest extends TestCase
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
     * Tests window sql generation.
     */
    public function testWindowSql(): void
    {
        $query = new SelectQuery();
        $query
            ->select('*')
            ->window('name', new WindowExpression())
        ;
        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertQueryContains('SELECT \* WINDOW name AS \(\)', $sql);

        $query->window('name2', new WindowExpression('name'));
        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertQueryContains('SELECT \* WINDOW name AS \(\), name2 AS \(name\)', $sql);

        $query
            ->reset('window')
            ->window('name', function ($window, $query) {
                return $window->name('name3');
            })
        ;
        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertEqualsSql('SELECT * WINDOW name AS (name3)', $sql);
    }

    public function testMissingWindow(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(sprintf('You must return a "%s" from a Closure passed to "window()"', WindowExpression::class));
        (new SelectQuery())->window('name', function () {
            return new QueryExpression();
        });
    }

    public function testPartitions(): void
    {
        $query = new SelectQuery();
        $query
            ->select(['num_rows' => $query->func()->count('*')->over()])
            ->from('comments')
        ;
        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertEquals('SELECT (COUNT(*) OVER ()) AS num_rows FROM comments', $sql);

        $query = new SelectQuery();
        $query
            ->select(['num_rows' => $query->func()->count('*')->partition('article_id')])
            ->from('comments')
            ->orderBy(['article_id'])
        ;
        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertEquals('SELECT (COUNT(*) OVER (PARTITION BY article_id)) AS num_rows FROM comments ORDER BY article_id', $sql);

        $query = new SelectQuery();
        $query
            ->select(['num_rows' => $query->func()->count('*')->partition('article_id')->orderBy('updated')])
            ->from('comments')
            ->orderBy(['updated'])
        ;
        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertEquals('SELECT (COUNT(*) OVER (PARTITION BY article_id ORDER BY updated)) AS num_rows FROM comments ORDER BY updated', $sql);
    }

    /**
     * Tests adding named windows to the query.
     */
    public function testNamedWindow(): void
    {
        $query = new SelectQuery();
        $query
            ->select(['num_rows' => $query->func()->count('*')->over('window1')])
            ->from('comments')
            ->window('window1', (new WindowExpression())->partition('article_id'))
            ->orderBy(['article_id'])
        ;
        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertEquals('SELECT (COUNT(*) OVER window1) AS num_rows FROM comments WINDOW window1 AS (PARTITION BY article_id) ORDER BY article_id', $sql);
    }

    public function testWindowChaining(): void
    {
        $query = new SelectQuery();
        $query
            ->select(['num_rows' => $query->func()->count('*')->over('window2')])
            ->from('comments')
            ->window('window1', (new WindowExpression())->partition('article_id'))
            ->window('window2', new WindowExpression('window1'))
            ->orderBy(['article_id'])
        ;
        $sql = $this->compiler->compile($query, new ValueBinder());
        $this->assertEquals('SELECT (COUNT(*) OVER window2) AS num_rows FROM comments WINDOW window1 AS (PARTITION BY article_id), window2 AS (window1) ORDER BY article_id', $sql);
    }
}
