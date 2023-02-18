<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Tests\Query\Expressions;

use PHPUnit\Framework\TestCase;
use Somnambulist\Components\QueryBuilder\Compiler\CompilerInterface;
use Somnambulist\Components\QueryBuilder\Query\Expressions\CommonTableExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\IdentifierExpression;
use Somnambulist\Components\QueryBuilder\Tests\Support\QueryAssertsTrait;
use Somnambulist\Components\QueryBuilder\Tests\Support\QueryCompilerBuilderTrait;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use function Somnambulist\Components\QueryBuilder\Resources\select;

class CommonTableExpressionTest extends TestCase
{
    use QueryAssertsTrait;
    use QueryCompilerBuilderTrait;
    
    protected ?CompilerInterface $compiler = null;

    public function setUp(): void
    {
        $this->compiler = $this->buildExpressionCompiler();
        $this->compiler->add($this->buildCompiler());
    }

    public function tearDown(): void
    {
        $this->compiler = null;
    }

    /**
     * Tests constructing CommonTableExpressions.
     */
    public function testCteConstructor(): void
    {
        $cte = new CommonTableExpression('test', select());
        $this->assertEqualsSql('test AS ()', $this->compiler->compile($cte, new ValueBinder()));

        $cte = (new CommonTableExpression())
            ->name('test')
            ->query(select());
        $this->assertEqualsSql('test AS ()', $this->compiler->compile($cte, new ValueBinder()));
    }

    /**
     * Tests setting fields.
     */
    public function testFields(): void
    {
        $cte = (new CommonTableExpression('test', select()))
            ->field('col1')
            ->field(new IdentifierExpression('col2'));
        $this->assertEqualsSql('test(col1, col2) AS ()', $this->compiler->compile($cte, new ValueBinder()));
    }

    /**
     * Tests setting CTE materialized
     */
    public function testMaterialized(): void
    {
        $cte = (new CommonTableExpression('test', select()))
            ->materialized();
        $this->assertEqualsSql('test AS MATERIALIZED ()', $this->compiler->compile($cte, new ValueBinder()));

        $cte->notMaterialized();
        $this->assertEqualsSql('test AS NOT MATERIALIZED ()', $this->compiler->compile($cte, new ValueBinder()));
    }

    /**
     * Tests setting CTE as recursive.
     */
    public function testRecursive(): void
    {
        $cte = (new CommonTableExpression('test', select()))
            ->recursive();
        $this->assertTrue($cte->isRecursive());
    }

    /**
     * Tests setting query using closures.
     */
    public function testQueryClosures(): void
    {
        $cte = new CommonTableExpression('test', function () {
            return select();
        });
        $this->assertEqualsSql('test AS ()', $this->compiler->compile($cte, new ValueBinder()));

        $cte->query(function () {
            return select('1');
        });
        $this->assertEqualsSql('test AS (SELECT 1)', $this->compiler->compile($cte, new ValueBinder()));
    }

    /**
     * Tests traversing CommonTableExpression.
     */
    public function testTraverse(): void
    {
        $query = select('1');
        $cte = (new CommonTableExpression('test', $query))->field('field');

        $expressions = [];
        $cte->traverse(function ($expression) use (&$expressions): void {
            $expressions[] = $expression;
        });

        $this->assertEquals(new IdentifierExpression('test'), $expressions[0]);
        $this->assertEquals(new IdentifierExpression('field'), $expressions[1]);
        $this->assertEquals($query, $expressions[2]);
    }

    /**
     * Tests cloning CommonTableExpression
     */
    public function testClone(): void
    {
        $cte = new CommonTableExpression('test', function () {
            return select('1');
        });
        $cte2 = (clone $cte)->name('test2');
        $this->assertNotSame(
            $this->compiler->compile($cte, new ValueBinder()),
            $this->compiler->compile($cte2, new ValueBinder()),
        );

        $cte2 = (clone $cte)->field('col1');
        $this->assertNotSame(
            $this->compiler->compile($cte, new ValueBinder()),
            $this->compiler->compile($cte2, new ValueBinder()),
        );
    }
}
