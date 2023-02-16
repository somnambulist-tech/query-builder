<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Tests\Builder\Expression;

use PHPUnit\Framework\TestCase;
use Somnambulist\Components\QueryBuilder\Builder\Expressions\ComparisonExpression;
use Somnambulist\Components\QueryBuilder\Builder\Expressions\IdentifierExpression;
use Somnambulist\Components\QueryBuilder\Builder\Expressions\QueryExpression;
use Somnambulist\Components\QueryBuilder\Tests\Support\QueryAssertsTrait;
use Somnambulist\Components\QueryBuilder\Tests\Support\QueryCompilerBuilderTrait;
use Somnambulist\Components\QueryBuilder\ValueBinder;

/**
 * Tests Comparison class
 */
class ComparisonExpressionTest extends TestCase
{
    use QueryAssertsTrait;
    use QueryCompilerBuilderTrait;
    
    /**
     * Test sql generation using IdentifierExpression
     */
    public function testIdentifiers(): void
    {
        $this->registerTypeCaster();
        $compiler = $this->buildExpressionCompiler();
        
        $expr = new ComparisonExpression('field', new IdentifierExpression('other_field'));
        $this->assertEqualsSql('field = other_field', $compiler->compile($expr, new ValueBinder()));

        $expr = new ComparisonExpression(new IdentifierExpression('field'), new IdentifierExpression('other_field'));
        $this->assertEqualsSql('field = other_field', $compiler->compile($expr, new ValueBinder()));

        $expr = new ComparisonExpression(new IdentifierExpression('field'), new QueryExpression(['other_field']));
        $this->assertEqualsSql('field = (other_field)', $compiler->compile($expr, new ValueBinder()));

        $expr = new ComparisonExpression(new IdentifierExpression('field'), 'value');
        $this->assertEqualsSql('field = :c_0', $compiler->compile($expr, new ValueBinder()));

        $expr = new ComparisonExpression(new QueryExpression(['field']), new IdentifierExpression('other_field'));
        $this->assertEqualsSql('field = other_field', $compiler->compile($expr, new ValueBinder()));
    }

    /**
     * Tests that cloning Comparison instance clones value and field expressions.
     */
    public function testClone(): void
    {
        $exp = new ComparisonExpression(new QueryExpression('field1'), 1, 'integer', '<');
        $exp2 = clone $exp;

        $this->assertNotSame($exp->getField(), $exp2->getField());
    }
}
