<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Tests\Query\Expressions;

use Somnambulist\Components\QueryBuilder\Query\Expressions\AggregateExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\IdentifierExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\QueryExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\WindowExpression;
use Somnambulist\Components\QueryBuilder\Tests\Support\QueryAssertsTrait;
use Somnambulist\Components\QueryBuilder\ValueBinder;

/**
 * Tests FunctionExpression class
 */
class AggregateExpressionTest extends FunctionExpressionTest
{
    use QueryAssertsTrait;

    protected string $expressionClass = AggregateExpression::class;

    /**
     * Tests annotating an aggregate with an empty window expression
     */
    public function testEmptyWindow(): void
    {
        $f = (new AggregateExpression('MyFunction'))->over();
        $this->assertSame('MyFunction() OVER ()', $this->compiler->compile($f, new ValueBinder()));

        $f = (new AggregateExpression('MyFunction'))->over('name');
        $this->assertEqualsSql(
            'MyFunction() OVER name',
            $this->compiler->compile($f, new ValueBinder())
        );
    }

    /**
     * Tests filter() clauses.
     */
    public function testFilter(): void
    {
        $f = (new AggregateExpression('MyFunction'))->filter(['this' => new IdentifierExpression('that')]);
        $this->assertEqualsSql(
            'MyFunction() FILTER (WHERE this = that)',
            $this->compiler->compile($f, new ValueBinder())
        );

        $f->filter(function (QueryExpression $q) {
            return $q->add(['this2' => new IdentifierExpression('that2')]);
        });
        $this->assertEqualsSql(
            'MyFunction() FILTER (WHERE (this = that AND this2 = that2))',
            $this->compiler->compile($f, new ValueBinder())
        );

        $f->over();
        $this->assertEqualsSql(
            'MyFunction() FILTER (WHERE (this = that AND this2 = that2)) OVER ()',
            $this->compiler->compile($f, new ValueBinder())
        );
    }

    /**
     * Tests WindowInterface calls are passed to the WindowExpression
     */
    public function testWindowInterface(): void
    {
        $binder = new ValueBinder();
        $f = (new AggregateExpression('MyFunction'))->partition('test');
        $this->assertEqualsSql(
            'MyFunction() OVER (PARTITION BY test)',
            $this->compiler->compile($f, $binder)
        );

        $f = (new AggregateExpression('MyFunction'))->orderBy('test');
        $this->assertEqualsSql(
            'MyFunction() OVER (ORDER BY test)',
            $this->compiler->compile($f, $binder)
        );

        $f = (new AggregateExpression('MyFunction'))->range(null);
        $this->assertEqualsSql(
            'MyFunction() OVER (RANGE BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW)',
            $this->compiler->compile($f, $binder)
        );

        $f = (new AggregateExpression('MyFunction'))->range(null, null);
        $this->assertEqualsSql(
            'MyFunction() OVER (RANGE BETWEEN UNBOUNDED PRECEDING AND UNBOUNDED FOLLOWING)',
            $this->compiler->compile($f, $binder)
        );

        $f = (new AggregateExpression('MyFunction'))->rows(null);
        $this->assertEqualsSql(
            'MyFunction() OVER (ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW)',
            $this->compiler->compile($f, $binder)
        );

        $f = (new AggregateExpression('MyFunction'))->rows(null, null);
        $this->assertEqualsSql(
            'MyFunction() OVER (ROWS BETWEEN UNBOUNDED PRECEDING AND UNBOUNDED FOLLOWING)',
            $this->compiler->compile($f, $binder)
        );

        $f = (new AggregateExpression('MyFunction'))->groups(null);
        $this->assertEqualsSql(
            'MyFunction() OVER (GROUPS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW)',
            $this->compiler->compile($f, $binder)
        );

        $f = (new AggregateExpression('MyFunction'))->groups(null, null);
        $this->assertEqualsSql(
            'MyFunction() OVER (GROUPS BETWEEN UNBOUNDED PRECEDING AND UNBOUNDED FOLLOWING)',
            $this->compiler->compile($f, $binder)
        );

        $f = (new AggregateExpression('MyFunction'))->frame(
            AggregateExpression::RANGE,
            2,
            AggregateExpression::PRECEDING,
            1,
            AggregateExpression::PRECEDING
        );
        $this->assertEqualsSql(
            'MyFunction() OVER (RANGE BETWEEN 2 PRECEDING AND 1 PRECEDING)',
            $this->compiler->compile($f, $binder)
        );

        $f = (new AggregateExpression('MyFunction'))->excludeCurrent();
        $this->assertEqualsSql(
            'MyFunction() OVER ()',
            $this->compiler->compile($f, $binder)
        );

        $f = (new AggregateExpression('MyFunction'))->range(null)->excludeCurrent();
        $this->assertEqualsSql(
            'MyFunction() OVER (RANGE BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW EXCLUDE CURRENT ROW)',
            $this->compiler->compile($f, $binder)
        );

        $f = (new AggregateExpression('MyFunction'))->range(null)->excludeGroup();
        $this->assertEqualsSql(
            'MyFunction() OVER (RANGE BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW EXCLUDE GROUP)',
            $this->compiler->compile($f, $binder)
        );

        $f = (new AggregateExpression('MyFunction'))->range(null)->excludeTies();
        $this->assertEqualsSql(
            'MyFunction() OVER (RANGE BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW EXCLUDE TIES)',
            $this->compiler->compile($f, $binder)
        );
    }

    /**
     * Tests traversing aggregate expressions.
     */
    public function testTraverse(): void
    {
        $w = (new AggregateExpression('MyFunction'))
            ->filter(['this' => true])
            ->over();

        $expressions = [];
        $w->traverse(function ($expression) use (&$expressions): void {
            $expressions[] = $expression;
        });

        $this->assertEquals(new QueryExpression(['this' => true]), $expressions[0]);
        $this->assertEquals(new WindowExpression(), $expressions[2]);
    }

    /**
     * Tests cloning aggregate expressions
     */
    public function testCloning(): void
    {
        $a1 = (new AggregateExpression('MyFunction'))->partition('test');
        $a2 = (clone $a1)->partition('new');
        $this->assertNotSame(
            $this->compiler->compile($a1, new ValueBinder()),
            $this->compiler->compile($a2, new ValueBinder()),
        );
    }
}
