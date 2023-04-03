<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Tests\Query\Expressions;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Somnambulist\Components\QueryBuilder\Compiler\Compiler;
use Somnambulist\Components\QueryBuilder\Query\Expressions\QueryExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\TupleComparison;
use Somnambulist\Components\QueryBuilder\Tests\Support\QueryCompilerBuilderTrait;
use Somnambulist\Components\QueryBuilder\ValueBinder;

/**
 * Tests TupleComparison class
 */
class TupleComparisonTest extends TestCase
{
    use QueryCompilerBuilderTrait;

    protected ?Compiler $compiler = null;

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
     * Tests generating a function with no arguments
     */
    public function testsSimpleTuple(): void
    {
        $f = new TupleComparison(['field1', 'field2'], [1, 2], ['integer', 'integer'], '=');
        $binder = new ValueBinder();
        $this->assertSame('(field1, field2) = (:tuple_0, :tuple_1)', $this->compiler->compile($f, $binder));
        $this->assertSame(1, $binder->bindings()[':tuple_0']->value);
        $this->assertSame(2, $binder->bindings()[':tuple_1']->value);
        $this->assertSame('integer', $binder->bindings()[':tuple_0']->type);
        $this->assertSame('integer', $binder->bindings()[':tuple_1']->type);
    }

    /**
     * Tests generating tuples in the fields side containing expressions
     */
    public function testTupleWithExpressionFields(): void
    {
        $field1 = new QueryExpression(['a' => 1]);
        $f = new TupleComparison([$field1, 'field2'], [4, 5], ['integer', 'integer'], '>');
        $binder = new ValueBinder();
        $this->assertSame('(a = :c_0, field2) > (:tuple_1, :tuple_2)', $this->compiler->compile($f, $binder));
        $this->assertSame(1, $binder->bindings()[':c_0']->value);
        $this->assertSame(4, $binder->bindings()[':tuple_1']->value);
        $this->assertSame(5, $binder->bindings()[':tuple_2']->value);
    }

    /**
     * Tests generating tuples in the values side containing expressions
     */
    public function testTupleWithExpressionValues(): void
    {
        $value1 = new QueryExpression(['a' => 1]);
        $f = new TupleComparison(['field1', 'field2'], [$value1, 2], ['integer', 'integer'], '=');
        $binder = new ValueBinder();
        $this->assertSame('(field1, field2) = (a = :c_0, :tuple_1)', $this->compiler->compile($f, $binder));
        $this->assertSame(1, $binder->bindings()[':c_0']->value);
        $this->assertSame(2, $binder->bindings()[':tuple_1']->value);
    }

    /**
     * Tests generating tuples using the IN conjunction
     */
    public function testTupleWithInComparison(): void
    {
        $f = new TupleComparison(
            ['field1', 'field2'],
            [[1, 2], [3, 4]],
            ['integer', 'integer'],
            'IN'
        );
        $binder = new ValueBinder();
        $this->assertSame('(field1, field2) IN ((:tuple_0,:tuple_1), (:tuple_2,:tuple_3))', $this->compiler->compile($f, $binder));
        $this->assertSame(1, $binder->bindings()[':tuple_0']->value);
        $this->assertSame(2, $binder->bindings()[':tuple_1']->value);
        $this->assertSame(3, $binder->bindings()[':tuple_2']->value);
        $this->assertSame(4, $binder->bindings()[':tuple_3']->value);
    }

    /**
     * Tests traversing
     */
    public function testTraverse(): void
    {
        $value1 = new QueryExpression(['a' => 1]);
        $field2 = new QueryExpression(['b' => 2]);
        $f = new TupleComparison(['field1', $field2], [$value1, 2], ['integer', 'integer'], '=');
        $expressions = [];

        $collector = function ($e) use (&$expressions): void {
            $expressions[] = $e;
        };

        $f->traverse($collector);
        $this->assertCount(4, $expressions);
        $this->assertSame($field2, $expressions[0]);
        $this->assertSame($value1, $expressions[2]);

        $f = new TupleComparison(
            ['field1', $field2],
            [[1, 2], [3, $value1]],
            ['integer', 'integer'],
            'IN'
        );
        $expressions = [];
        $f->traverse($collector);
        $this->assertCount(4, $expressions);
        $this->assertSame($field2, $expressions[0]);
        $this->assertSame($value1, $expressions[2]);
    }

    /**
     * Tests that a single ExpressionInterface can be used as the value for
     * comparison
     */
    public function testValueAsSingleExpression(): void
    {
        $value = new QueryExpression('SELECT 1, 1');
        $f = new TupleComparison(['field1', 'field2'], $value);
        $binder = new ValueBinder();
        $this->assertSame('(field1, field2) = (SELECT 1, 1)', $this->compiler->compile($f, $binder));
    }

    /**
     * Tests that a single ExpressionInterface can be used as the field for
     * comparison
     */
    public function testFieldAsSingleExpression(): void
    {
        $value = [1, 1];
        $f = new TupleComparison(new QueryExpression('a, b'), $value);
        $binder = new ValueBinder();
        $this->assertSame('(a, b) = (:tuple_0, :tuple_1)', $this->compiler->compile($f, $binder));
    }

    public function testMultiTupleComparisonRequiresMultiTupleValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Multi-tuple comparisons require a multi-tuple value, single-tuple given.');

        new TupleComparison(
            ['field1', 'field2'],
            [1, 1],
            ['integer', 'integer'],
            'IN'
        );
    }

    public function testSingleTupleComparisonRequiresSingleTupleValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Single-tuple comparisons require a single-tuple value, multi-tuple given.');

        new TupleComparison(
            ['field1', 'field2'],
            [[1, 1], [2, 2]],
            ['integer', 'integer'],
            '='
        );
    }
}
