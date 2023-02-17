<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Tests\Query\Expressions;

use PHPUnit\Framework\TestCase;
use Somnambulist\Components\QueryBuilder\Query\Expressions\IdentifierExpression;
use Somnambulist\Components\QueryBuilder\Tests\Support\QueryCompilerBuilderTrait;
use Somnambulist\Components\QueryBuilder\ValueBinder;

/**
 * Tests IdentifierExpression class
 */
class IdentifierExpressionTest extends TestCase
{
    use QueryCompilerBuilderTrait;

    protected function setUp(): void
    {
        $this->registerTypeCaster();
    }

    /**
     * Tests getting and setting the field
     */
    public function testGetAndSet(): void
    {
        $expression = new IdentifierExpression('foo');
        $this->assertSame('foo', $expression->getIdentifier());
        $expression->setIdentifier('bar');
        $this->assertSame('bar', $expression->getIdentifier());
    }

    /**
     * Tests converting to sql
     */
    public function testSQL(): void
    {
        $expression = new IdentifierExpression('foo');
        $this->assertSame('foo', $this->buildExpressionCompiler()->compile($expression, new ValueBinder()));
    }

    /**
     * Tests setting collation.
     */
    public function testCollation(): void
    {
        $expresssion = new IdentifierExpression('test', 'utf8_general_ci');
        $this->assertSame('test COLLATE utf8_general_ci', $this->buildExpressionCompiler()->compile($expresssion, new ValueBinder()));
    }
}
