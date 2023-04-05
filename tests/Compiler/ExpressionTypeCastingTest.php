<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Tests\Compiler;

use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\TestCase;
use Somnambulist\Components\QueryBuilder\Compiler\Compiler;
use Somnambulist\Components\QueryBuilder\Query\Expressions\BetweenExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\ComparisonExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\FunctionExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\ValuesExpression;
use Somnambulist\Components\QueryBuilder\Tests\Support\Fixtures\Types\TestType;
use Somnambulist\Components\QueryBuilder\Tests\Support\QueryCompilerBuilderTrait;
use Somnambulist\Components\QueryBuilder\TypeMap;
use Somnambulist\Components\QueryBuilder\ValueBinder;

/**
 * Tests to ensure that values that are expressions are correctly cast to SQL strings
 *
 * This is for types that return ExpressionInterface instances instead of a discrete value.
 */
class ExpressionTypeCastingTest extends TestCase
{
    use QueryCompilerBuilderTrait;

    private ?Compiler $compiler = null;

    public function setUp(): void
    {
        parent::setUp();

        if (!Type::getTypeRegistry()->has('test_type')) {
            Type::getTypeRegistry()->register('test_type', new TestType());
        }

        $this->compiler = $this->buildCompiler();
    }

    public function testComparisonSimple(): void
    {
        $comparison = new ComparisonExpression('field', 'the thing', 'test_type', '=');
        $binder = new ValueBinder();
        $sql = $this->compiler->compile($comparison, $binder);

        $this->assertSame('field = (CONCAT(:param_0, :param_1))', $sql);
        $this->assertSame('the thing', $binder->bindings()[':param_0']->value);

        $found = false;
        $comparison->traverse(function ($exp) use (&$found): void {
            $found = $exp instanceof FunctionExpression;
        });
        $this->assertTrue($found, 'The expression is not included in the tree');
    }

    public function testComparisonMultiple(): void
    {
        $comparison = new ComparisonExpression('field', ['2', '3'], 'test_type[]', 'IN');
        $binder = new ValueBinder();
        $sql = $this->compiler->compile($comparison, $binder);
        $this->assertSame('field IN (CONCAT(:param_0, :param_1),CONCAT(:param_2, :param_3))', $sql);
        $this->assertSame('2', $binder->bindings()[':param_0']->value);
        $this->assertSame('3', $binder->bindings()[':param_2']->value);

        $found = false;
        $comparison->traverse(function ($exp) use (&$found): void {
            $found = $exp instanceof FunctionExpression;
        });
        $this->assertTrue($found, 'The expression is not included in the tree');
    }

    public function testBetween(): void
    {
        $between = new BetweenExpression('field', 'from', 'to', 'test_type');
        $binder = new ValueBinder();
        $sql = $this->compiler->compile($between, $binder);
        $this->assertSame('field BETWEEN CONCAT(:param_0, :param_1) AND CONCAT(:param_2, :param_3)', $sql);
        $this->assertSame('from', $binder->bindings()[':param_0']->value);
        $this->assertSame('to', $binder->bindings()[':param_2']->value);

        $expressions = [];
        $between->traverse(function ($exp) use (&$expressions): void {
            $expressions[] = $exp instanceof FunctionExpression ? 1 : 0;
        });

        $result = array_sum($expressions);
        $this->assertSame(2, $result, 'Missing expressions in the tree');
    }

    public function testFunctionExpression(): void
    {
        $function = new FunctionExpression('DATE', ['2016-01'], ['test_type']);
        $binder = new ValueBinder();
        $sql = $this->compiler->compile($function, $binder);
        $this->assertSame('DATE(CONCAT(:param_0, :param_1))', $sql);
        $this->assertSame('2016-01', $binder->bindings()[':param_0']->value);

        $expressions = [];
        $function->traverse(function ($exp) use (&$expressions): void {
            $expressions[] = $exp instanceof FunctionExpression ? 1 : 0;
        });

        $result = array_sum($expressions);
        $this->assertSame(1, $result, 'Missing expressions in the tree');
    }

    public function testValuesExpression(): void
    {
        $values = new ValuesExpression(['title'], new TypeMap(['title' => 'test_type']));
        $values->add(['title' => 'foo']);
        $values->add(['title' => 'bar']);

        $binder = new ValueBinder();
        $sql = $this->compiler->compile($values, $binder);
        $this->assertSame(
            ' VALUES ((CONCAT(:param_0, :param_1))), ((CONCAT(:param_2, :param_3)))',
            $sql
        );
        $this->assertSame('foo', $binder->bindings()[':param_0']->value);
        $this->assertSame('bar', $binder->bindings()[':param_2']->value);

        $expressions = [];
        $values->traverse(function ($exp) use (&$expressions): void {
            $expressions[] = $exp instanceof FunctionExpression ? 1 : 0;
        });

        $result = array_sum($expressions);
        $this->assertSame(2, $result, 'Missing expressions in the tree');
    }
}
