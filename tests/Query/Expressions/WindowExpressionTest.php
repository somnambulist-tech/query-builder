<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Tests\Query\Expressions;

use PHPUnit\Framework\TestCase;
use Somnambulist\Components\QueryBuilder\Compiler\CompilerInterface;
use Somnambulist\Components\QueryBuilder\Query\Expressions\AggregateExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\IdentifierExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\OrderByExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\OrderClauseExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\QueryExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\WindowExpression;
use Somnambulist\Components\QueryBuilder\Query\OrderDirection;
use Somnambulist\Components\QueryBuilder\Tests\Support\QueryAssertsTrait;
use Somnambulist\Components\QueryBuilder\Tests\Support\QueryCompilerBuilderTrait;
use Somnambulist\Components\QueryBuilder\ValueBinder;

/**
 * Tests WindowExpression class
 */
class WindowExpressionTest extends TestCase
{
    use QueryAssertsTrait;
    use QueryCompilerBuilderTrait;

    protected ?CompilerInterface $compiler = null;

    protected function setUp(): void
    {
        $this->registerTypeCaster();

        $this->compiler = $this->buildDelegatingCompiler();
    }

    protected function tearDown(): void
    {
        $this->compiler = null;
    }

    /**
     * Tests an empty window expression
     */
    public function testEmptyWindow(): void
    {
        $w = new WindowExpression();
        $this->assertSame('', $this->compiler->compile($w, new ValueBinder()));

        $w->partition('')->orderBy([]);
        $this->assertSame('', $this->compiler->compile($w, new ValueBinder()));
    }

    /**
     * Tests windows with partitions
     */
    public function testPartitions(): void
    {
        $w = (new WindowExpression())->partition('test');
        $this->assertEqualsSql(
            'PARTITION BY test',
            $this->compiler->compile($w, new ValueBinder())
        );

        $w->partition(new IdentifierExpression('identifier'));
        $this->assertEqualsSql(
            'PARTITION BY test, identifier',
            $this->compiler->compile($w, new ValueBinder())
        );

        $w = (new WindowExpression())->partition(new AggregateExpression('MyAggregate', ['param']));
        $this->assertEqualsSql(
            'PARTITION BY MyAggregate(:param_0)',
            $this->compiler->compile($w, new ValueBinder())
        );

        $w = (new WindowExpression())->partition(function (QueryExpression $expr) {
            return $expr->add(new AggregateExpression('MyAggregate', ['param']));
        });
        $this->assertEqualsSql(
            'PARTITION BY MyAggregate(:param_0)',
            $this->compiler->compile($w, new ValueBinder())
        );
    }

    /**
     * Tests windows with order by
     */
    public function testOrderBy(): void
    {
        $w = (new WindowExpression())->orderBy('test');
        $this->assertEqualsSql(
            'ORDER BY test',
            $this->compiler->compile($w, new ValueBinder())
        );

        $w->orderBy(['test2' => 'DESC']);
        $this->assertEqualsSql(
            'ORDER BY test, test2 DESC',
            $this->compiler->compile($w, new ValueBinder())
        );

        $w->partition('test');
        $this->assertEqualsSql(
            'PARTITION BY test ORDER BY test, test2 DESC',
            $this->compiler->compile($w, new ValueBinder())
        );

        $w = (new WindowExpression())
            ->orderBy(function () {
                return 'test';
            })
            ->orderBy(function (QueryExpression $expr) {
                return [$expr->add('test2'), new OrderClauseExpression(new IdentifierExpression('test3'), OrderDirection::DESC)];
            })
        ;
        $this->assertEqualsSql(
            'ORDER BY test, test2, test3 DESC',
            $this->compiler->compile($w, new ValueBinder())
        );
    }

    public function testOrderDeprecated(): void
    {
        $w = (new WindowExpression())->orderBy('test');
        $this->assertEqualsSql(
            'ORDER BY test',
            $this->compiler->compile($w, new ValueBinder())
        );
    }

    /**
     * Tests windows with range frames
     */
    public function testRange(): void
    {
        $w = (new WindowExpression())->range(null);
        $this->assertEqualsSql(
            'RANGE BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW',
            $this->compiler->compile($w, new ValueBinder())
        );

        $w = (new WindowExpression())->range(0);
        $this->assertEqualsSql(
            'RANGE BETWEEN CURRENT ROW AND CURRENT ROW',
            $this->compiler->compile($w, new ValueBinder())
        );

        $w = (new WindowExpression())->range(2);
        $this->assertEqualsSql(
            'RANGE BETWEEN 2 PRECEDING AND CURRENT ROW',
            $this->compiler->compile($w, new ValueBinder())
        );

        $w = (new WindowExpression())->range(null, null);
        $this->assertEqualsSql(
            'RANGE BETWEEN UNBOUNDED PRECEDING AND UNBOUNDED FOLLOWING',
            $this->compiler->compile($w, new ValueBinder())
        );

        $w = (new WindowExpression())->range(0, null);
        $this->assertEqualsSql(
            'RANGE BETWEEN CURRENT ROW AND UNBOUNDED FOLLOWING',
            $this->compiler->compile($w, new ValueBinder())
        );

        $w = (new WindowExpression())->range(0);
        $this->assertEqualsSql(
            'RANGE BETWEEN CURRENT ROW AND CURRENT ROW',
            $this->compiler->compile($w, new ValueBinder())
        );

        $w = (new WindowExpression())->range(1, 2);
        $this->assertEqualsSql(
            'RANGE BETWEEN 1 PRECEDING AND 2 FOLLOWING',
            $this->compiler->compile($w, new ValueBinder())
        );

        $w = (new WindowExpression())->range("'1 day'", "'10 days'");
        $this->assertQueryContains(
            "RANGE BETWEEN '1 day' PRECEDING AND '10 days' FOLLOWING",
            $this->compiler->compile($w, new ValueBinder())
        );

        $w = (new WindowExpression())->range(new QueryExpression("'1 day'"), new QueryExpression("'10 days'"));
        $this->assertQueryContains(
            "RANGE BETWEEN '1 day' PRECEDING AND '10 days' FOLLOWING",
            $this->compiler->compile($w, new ValueBinder())
        );

        $w = (new WindowExpression())->frame(
            WindowExpression::RANGE,
            2,
            WindowExpression::PRECEDING,
            1,
            WindowExpression::PRECEDING
        );
        $this->assertEqualsSql(
            'RANGE BETWEEN 2 PRECEDING AND 1 PRECEDING',
            $this->compiler->compile($w, new ValueBinder())
        );
    }

    /**
     * Tests windows with rows frames
     */
    public function testRows(): void
    {
        $w = (new WindowExpression())->rows(null);
        $this->assertEqualsSql(
            'ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW',
            $this->compiler->compile($w, new ValueBinder())
        );

        $w = (new WindowExpression())->rows(0);
        $this->assertEqualsSql(
            'ROWS BETWEEN CURRENT ROW AND CURRENT ROW',
            $this->compiler->compile($w, new ValueBinder())
        );

        $w = (new WindowExpression())->rows(2);
        $this->assertEqualsSql(
            'ROWS BETWEEN 2 PRECEDING AND CURRENT ROW',
            $this->compiler->compile($w, new ValueBinder())
        );

        $w = (new WindowExpression())->rows(null, null);
        $this->assertEqualsSql(
            'ROWS BETWEEN UNBOUNDED PRECEDING AND UNBOUNDED FOLLOWING',
            $this->compiler->compile($w, new ValueBinder())
        );

        $w = (new WindowExpression())->rows(0, null);
        $this->assertEqualsSql(
            'ROWS BETWEEN CURRENT ROW AND UNBOUNDED FOLLOWING',
            $this->compiler->compile($w, new ValueBinder())
        );

        $w = (new WindowExpression())->rows(0);
        $this->assertEqualsSql(
            'ROWS BETWEEN CURRENT ROW AND CURRENT ROW',
            $this->compiler->compile($w, new ValueBinder())
        );

        $w = (new WindowExpression())->rows(1, 2);
        $this->assertEqualsSql(
            'ROWS BETWEEN 1 PRECEDING AND 2 FOLLOWING',
            $this->compiler->compile($w, new ValueBinder())
        );

        $w = (new WindowExpression())->frame(
            WindowExpression::ROWS,
            2,
            WindowExpression::PRECEDING,
            1,
            WindowExpression::PRECEDING
        );
        $b = new ValueBinder();
        $this->assertEqualsSql(
            'ROWS BETWEEN 2 PRECEDING AND 1 PRECEDING',
            $this->compiler->compile($w, $b)
        );
    }

    /**
     * Tests windows with groups frames
     */
    public function testGroups(): void
    {
        $w = (new WindowExpression())->groups(null);
        $this->assertEqualsSql(
            'GROUPS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW',
            $this->compiler->compile($w, new ValueBinder())
        );

        $w = (new WindowExpression())->groups(0);
        $this->assertEqualsSql(
            'GROUPS BETWEEN CURRENT ROW AND CURRENT ROW',
            $this->compiler->compile($w, new ValueBinder())
        );

        $w = (new WindowExpression())->groups(2);
        $this->assertEqualsSql(
            'GROUPS BETWEEN 2 PRECEDING AND CURRENT ROW',
            $this->compiler->compile($w, new ValueBinder())
        );

        $w = (new WindowExpression())->groups(null, null);
        $this->assertEqualsSql(
            'GROUPS BETWEEN UNBOUNDED PRECEDING AND UNBOUNDED FOLLOWING',
            $this->compiler->compile($w, new ValueBinder())
        );

        $w = (new WindowExpression())->groups(0, null);
        $this->assertEqualsSql(
            'GROUPS BETWEEN CURRENT ROW AND UNBOUNDED FOLLOWING',
            $this->compiler->compile($w, new ValueBinder())
        );

        $w = (new WindowExpression())->groups(0);
        $this->assertEqualsSql(
            'GROUPS BETWEEN CURRENT ROW AND CURRENT ROW',
            $this->compiler->compile($w, new ValueBinder())
        );

        $w = (new WindowExpression())->groups(1, 2);
        $this->assertEqualsSql(
            'GROUPS BETWEEN 1 PRECEDING AND 2 FOLLOWING',
            $this->compiler->compile($w, new ValueBinder())
        );

        $w = (new WindowExpression())->frame(
            WindowExpression::GROUPS,
            2,
            WindowExpression::PRECEDING,
            1,
            WindowExpression::PRECEDING
        );
        $b = new ValueBinder();
        $this->assertEqualsSql(
            'GROUPS BETWEEN 2 PRECEDING AND 1 PRECEDING',
            $this->compiler->compile($w, $b)
        );
    }

    /**
     * Tests windows with frame exclusion
     */
    public function testExclusion(): void
    {
        $w = (new WindowExpression())->excludeCurrent();
        $this->assertEqualsSql(
            '',
            $this->compiler->compile($w, new ValueBinder())
        );

        $w = (new WindowExpression())->range(null)->excludeCurrent();
        $this->assertEqualsSql(
            'RANGE BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW EXCLUDE CURRENT ROW',
            $this->compiler->compile($w, new ValueBinder())
        );

        $w = (new WindowExpression())->range(null)->excludeGroup();
        $this->assertEqualsSql(
            'RANGE BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW EXCLUDE GROUP',
            $this->compiler->compile($w, new ValueBinder())
        );

        $w = (new WindowExpression())->range(null)->excludeTies();
        $this->assertEqualsSql(
            'RANGE BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW EXCLUDE TIES',
            $this->compiler->compile($w, new ValueBinder())
        );
    }

    /**
     * Tests windows with partition, order and frames
     */
    public function testCombined(): void
    {
        $w = (new WindowExpression())->partition('test')->range(null);
        $this->assertEqualsSql(
            'PARTITION BY test RANGE BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW',
            $this->compiler->compile($w, new ValueBinder())
        );

        $w = (new WindowExpression())->orderBy('test')->range(null);
        $this->assertEqualsSql(
            'ORDER BY test RANGE BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW',
            $this->compiler->compile($w, new ValueBinder())
        );

        $w = (new WindowExpression())->partition('test')->orderBy('test')->range(null);
        $this->assertEqualsSql(
            'PARTITION BY test ORDER BY test RANGE BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW',
            $this->compiler->compile($w, new ValueBinder())
        );

        $w = (new WindowExpression())->partition('test')->orderBy('test')->range(null)->excludeCurrent();
        $this->assertEqualsSql(
            'PARTITION BY test ORDER BY test RANGE BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW EXCLUDE CURRENT ROW',
            $this->compiler->compile($w, new ValueBinder())
        );
    }

    /**
     * Tests named windows
     */
    public function testNamedWindow(): void
    {
        $w = new WindowExpression();
        $this->assertFalse($w->isNamedOnly());

        $w->name('name');
        $this->assertTrue($w->isNamedOnly());
        $this->assertEqualsSql(
            'name',
            $this->compiler->compile($w, new ValueBinder())
        );

        $w->name('new_name');
        $this->assertEqualsSql(
            'new_name',
            $this->compiler->compile($w, new ValueBinder())
        );

        $w->orderBy('test');
        $this->assertFalse($w->isNamedOnly());
        $this->assertEqualsSql(
            'new_name ORDER BY test',
            $this->compiler->compile($w, new ValueBinder())
        );
    }

    /**
     * Tests traversing window expressions.
     */
    public function testTraverse(): void
    {
        $w = (new WindowExpression('test1'))
            ->partition('test2')
            ->orderBy('test3')
            ->range(new QueryExpression("'1 day'"))
        ;

        $expressions = [];
        $w->traverse(function ($expression) use (&$expressions): void {
            $expressions[] = $expression;
        });

        $this->assertEquals(new IdentifierExpression('test1'), $expressions[0]);
        $this->assertEquals(new IdentifierExpression('test2'), $expressions[1]);
        $this->assertEquals((new OrderByExpression())->add('test3'), $expressions[2]);
        $this->assertEquals(new QueryExpression("'1 day'"), $expressions[3]);

        $w->range(new QueryExpression("'1 day'"), new QueryExpression("'10 days'"));

        $expressions = [];
        $w->traverse(function ($expression) use (&$expressions): void {
            $expressions[] = $expression;
        });

        $this->assertEquals(new QueryExpression("'1 day'"), $expressions[3]);
        $this->assertEquals(new QueryExpression("'10 days'"), $expressions[4]);
    }

    /**
     * Tests cloning window expressions
     */
    public function testCloning(): void
    {
        $w1 = (new WindowExpression())->name('test');
        $w2 = (clone $w1)->name('test2');
        $this->assertNotSame($this->compiler->compile($w1, new ValueBinder()), $this->compiler->compile($w2, new ValueBinder()));

        $w1 = (new WindowExpression())->partition('test');
        $w2 = (clone $w1)->partition('new');
        $this->assertNotSame($this->compiler->compile($w1, new ValueBinder()), $this->compiler->compile($w2, new ValueBinder()));

        $w1 = (new WindowExpression())->orderBy('test');
        $w2 = (clone $w1)->orderBy('new');
        $this->assertNotSame($this->compiler->compile($w1, new ValueBinder()), $this->compiler->compile($w2, new ValueBinder()));

        $w1 = (new WindowExpression())->rows(0, null);
        $w2 = (clone $w1)->rows(0);
        $this->assertNotSame($this->compiler->compile($w1, new ValueBinder()), $this->compiler->compile($w2, new ValueBinder()));
    }
}
