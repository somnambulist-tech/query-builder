<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Tests\Query\Expressions;

use PHPUnit\Framework\TestCase;
use Somnambulist\Components\QueryBuilder\Query\Expressions\ComparisonExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\IdentifierExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\QueryExpression;
use Somnambulist\Components\QueryBuilder\Tests\Support\QueryAssertsTrait;
use Somnambulist\Components\QueryBuilder\Tests\Support\QueryCompilerBuilderTrait;
use Somnambulist\Components\QueryBuilder\ValueBinder;

class ComparisonExpressionTest extends TestCase
{
    use QueryAssertsTrait;
    use QueryCompilerBuilderTrait;
    
    public function testIdentifiers(): void
    {
        $this->registerTypeCaster();
        $compiler = $this->buildDelegatingCompiler();
        
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

    public function testClone(): void
    {
        $exp = new ComparisonExpression(new QueryExpression('field1'), 1, 'integer', '<');
        $exp2 = clone $exp;

        $this->assertNotSame($exp->getField(), $exp2->getField());
    }
}
