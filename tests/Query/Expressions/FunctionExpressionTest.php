<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Tests\Query\Expressions;

use PHPUnit\Framework\TestCase;
use Somnambulist\Components\QueryBuilder\Compiler\Compiler;
use Somnambulist\Components\QueryBuilder\Query\Expressions\FunctionExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\QueryExpression;
use Somnambulist\Components\QueryBuilder\Tests\Support\QueryCompilerBuilderTrait;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use function Somnambulist\Components\QueryBuilder\Resources\select;

class FunctionExpressionTest extends TestCase
{
    use QueryCompilerBuilderTrait;

    protected string $expressionClass = FunctionExpression::class;
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

    public function testArbitraryFunctionWithZeroArgs(): void
    {
        $f = new $this->expressionClass('MyFunction');
        $this->assertSame('MyFunction()', $this->compiler->compile($f, new ValueBinder()));
    }

    public function testArbitraryFunctionWithMultiplePlainValues(): void
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

    public function testLiteralParams(): void
    {
        $binder = new ValueBinder();
        $f = new $this->expressionClass('MyFunction', ['foo' => 'literal', 'bar']);
        $this->assertSame('MyFunction(foo, :param_0)', $this->compiler->compile($f, $binder));
    }

    public function testFunctionNesting(): void
    {
        $binder = new ValueBinder();
        $f = new $this->expressionClass('MyFunction', ['foo', 'bar']);
        $g = new $this->expressionClass('Wrapper', ['bar' => 'literal', $f]);
        $this->assertSame('Wrapper(bar, MyFunction(:param_0, :param_1))', $this->compiler->compile($g, $binder));
    }

    public function testFunctionNestingQueryExpression(): void
    {
        $binder = new ValueBinder();
        $q = new QueryExpression('a');
        $f = new $this->expressionClass('MyFunction', [$q]);
        $this->assertSame('MyFunction(a)', $this->compiler->compile($f, $binder));
    }

    public function testFunctionWithDatabaseQuery(): void
    {
        $query = select(['column']);

        $binder = new ValueBinder();
        $function = new $this->expressionClass('MyFunction', [$query]);
        $this->assertSame(
            'MyFunction((SELECT column))',
            preg_replace('/[`"\[\]]/', '', $this->compiler->compile($function, $binder))
        );
    }

    public function testNumericLiteral(): void
    {
        $binder = new ValueBinder();
        $f = new $this->expressionClass('MyFunction', ['a_field' => 'literal', '32' => 'literal']);
        $this->assertSame('MyFunction(a_field, 32)', $this->compiler->compile($f, $binder));

        $f = new $this->expressionClass('MyFunction', ['a_field' => 'literal', 32 => 'literal']);
        $this->assertSame('MyFunction(a_field, 32)', $this->compiler->compile($f, $binder));
    }

    public function testGetSetReturnType(): void
    {
        $f = new $this->expressionClass('MyFunction');
        $f = $f->setReturnType('foo');
        $this->assertInstanceOf($this->expressionClass, $f);
        $this->assertSame('foo', $f->getReturnType());
    }
}
