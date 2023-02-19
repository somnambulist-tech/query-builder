<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Tests\Query\Expressions;

use PHPUnit\Framework\TestCase;
use Somnambulist\Components\QueryBuilder\Query\Expressions\StringExpression;
use Somnambulist\Components\QueryBuilder\Tests\Support\QueryCompilerBuilderTrait;
use Somnambulist\Components\QueryBuilder\ValueBinder;

/**
 * Tests StringExpression class
 */
class StringExpressionTest extends TestCase
{
    use QueryCompilerBuilderTrait;

    public function testCollation(): void
    {
        $this->registerTypeCaster();
        $compiler = $this->buildDelegatingCompiler();

        $expr = new StringExpression('testString', 'utf8_general_ci');

        $binder = new ValueBinder();
        $this->assertSame(':c_0 COLLATE utf8_general_ci', $compiler->compile($expr, $binder));
        $this->assertSame('testString', $binder->bindings()[':c_0']->value);
        $this->assertSame('string', $binder->bindings()[':c_0']->type);
    }
}
