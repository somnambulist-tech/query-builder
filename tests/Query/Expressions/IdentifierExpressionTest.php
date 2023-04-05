<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Tests\Query\Expressions;

use PHPUnit\Framework\TestCase;
use Somnambulist\Components\QueryBuilder\Query\Expressions\IdentifierExpression;
use Somnambulist\Components\QueryBuilder\Tests\Support\QueryCompilerBuilderTrait;
use Somnambulist\Components\QueryBuilder\ValueBinder;

class IdentifierExpressionTest extends TestCase
{
    use QueryCompilerBuilderTrait;

    protected function setUp(): void
    {
        $this->registerTypeCaster();
    }

    public function testGetAndSet(): void
    {
        $expression = new IdentifierExpression('foo');
        $this->assertSame('foo', $expression->getIdentifier());
        $expression->identifier('bar');
        $this->assertSame('bar', $expression->getIdentifier());
    }

    public function testSQL(): void
    {
        $expression = new IdentifierExpression('foo');
        $this->assertSame('foo', $this->buildDelegatingCompiler()->compile($expression, new ValueBinder()));
    }

    public function testCollation(): void
    {
        $expresssion = new IdentifierExpression('test', 'utf8_general_ci');
        $this->assertSame('test COLLATE utf8_general_ci', $this->buildDelegatingCompiler()->compile($expresssion, new ValueBinder()));
    }
}
