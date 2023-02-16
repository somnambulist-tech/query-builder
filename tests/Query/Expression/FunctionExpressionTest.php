<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Tests\Query\Expression;

use PHPUnit\Framework\TestCase;
use Somnambulist\Components\QueryBuilder\Compiler\CompilerInterface;
use Somnambulist\Components\QueryBuilder\Query\Expressions\FunctionExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\QueryExpression;
use Somnambulist\Components\QueryBuilder\Tests\Support\QueryCompilerBuilderTrait;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use function Somnambulist\Components\QueryBuilder\Resources\select;

class FunctionExpressionTest extends TestCase
{
    use QueryCompilerBuilderTrait;

    protected string $expressionClass = FunctionExpression::class;
    protected ?CompilerInterface $compiler = null;

    protected function setUp(): void
    {
        $this->registerTypeCaster();

        $this->compiler = $this->buildExpressionCompiler();
    }

    protected function tearDown(): void
    {
        $this->compiler = null;
    }

    /**
     * Tests generating a function with no arguments
     */
    public function testArityZero(): void
    {
        $f = new $this->expressionClass('MyFunction');
        $this->assertSame('MyFunction()', $this->compiler->compile($f, new ValueBinder()));
    }

    /**
     * Tests generating a function one or multiple arguments and make sure
     * they are correctly replaced by placeholders
     */
    public function testArityMultiplePlainValues(): void
    {
        $f = new $this->expressionClass('MyFunction', ['foo', 'bar']);
        $binder = new ValueBinder();
        $this->assertSame('MyFunction(:param_0, :param_1)', $this->compiler->compile($f, $binder));

        $this->assertSame('foo', $binder->bindings()[':param_0']->value);
        $this->assertSame('bar', $binder->bindings()[':param_1']->value);

        $binder = new ValueBinder();
        $f = new $this->expressionClass('MyFunction', ['bar']);
        $this->assertSame('MyFunction(:param_0)', $this->compiler->compile($f, $binder));
        $this->assertSame('bar', $binder->bindings()[':param_0']->value);
    }

    /**
     * Tests that it is possible to use literal strings as arguments
     */
    public function testLiteralParams(): void
    {
        $binder = new ValueBinder();
        $f = new $this->expressionClass('MyFunction', ['foo' => 'literal', 'bar']);
        $this->assertSame('MyFunction(foo, :param_0)', $this->compiler->compile($f, $binder));
    }

    /**
     * Tests that it is possible to nest expression objects and pass them as arguments
     * In particular nesting multiple FunctionExpression
     */
    public function testFunctionNesting(): void
    {
        $binder = new ValueBinder();
        $f = new $this->expressionClass('MyFunction', ['foo', 'bar']);
        $g = new $this->expressionClass('Wrapper', ['bar' => 'literal', $f]);
        $this->assertSame('Wrapper(bar, MyFunction(:param_0, :param_1))', $this->compiler->compile($g, $binder));
    }

    /**
     * Tests to avoid regression, prevents double parenthesis
     * In particular nesting with QueryExpression
     */
    public function testFunctionNestingQueryExpression(): void
    {
        $binder = new ValueBinder();
        $q = new QueryExpression('a');
        $f = new $this->expressionClass('MyFunction', [$q]);
        $this->assertSame('MyFunction(a)', $this->compiler->compile($f, $binder));
    }

    /**
     * Tests that passing a database query as an argument wraps the query SQL into parentheses.
     */
    public function testFunctionWithDatabaseQuery(): void
    {
        $this->compiler->add($this->buildCompiler());

        $query = select(['column']);

        $binder = new ValueBinder();
        $function = new $this->expressionClass('MyFunction', [$query]);
        $this->assertSame(
            'MyFunction((SELECT column))',
            preg_replace('/[`"\[\]]/', '', $this->compiler->compile($function, $binder))
        );
    }

    /**
     * Tests that it is possible to use a number as a literal in a function.
     */
    public function testNumericLiteral(): void
    {
        $binder = new ValueBinder();
        $f = new $this->expressionClass('MyFunction', ['a_field' => 'literal', '32' => 'literal']);
        $this->assertSame('MyFunction(a_field, 32)', $this->compiler->compile($f, $binder));

        $f = new $this->expressionClass('MyFunction', ['a_field' => 'literal', 32 => 'literal']);
        $this->assertSame('MyFunction(a_field, 32)', $this->compiler->compile($f, $binder));
    }

    /**
     * Tests setReturnType() and getReturnType()
     */
    public function testGetSetReturnType(): void
    {
        $f = new $this->expressionClass('MyFunction');
        $f = $f->setReturnType('foo');
        $this->assertInstanceOf($this->expressionClass, $f);
        $this->assertSame('foo', $f->getReturnType());
    }
}
