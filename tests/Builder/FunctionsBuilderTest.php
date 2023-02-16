<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Tests\Builder;

use PHPUnit\Framework\TestCase;
use Somnambulist\Components\QueryBuilder\Builder\Expressions\AggregateExpression;
use Somnambulist\Components\QueryBuilder\Builder\Expressions\FunctionExpression;
use Somnambulist\Components\QueryBuilder\Builder\Expressions\IdentifierExpression;
use Somnambulist\Components\QueryBuilder\Builder\FunctionsBuilder;
use Somnambulist\Components\QueryBuilder\Tests\Support\QueryCompilerBuilderTrait;

class FunctionsBuilderTest extends TestCase
{
    use QueryCompilerBuilderTrait;

    protected ?FunctionsBuilder $functions = null;

    public function setUp(): void
    {
        $this->registerTypeCaster();

        $this->functions = new FunctionsBuilder();
    }

    /**
     * Tests generating a generic function call
     */
    public function testArbitrary(): void
    {
        $function = $this->functions->MyFunc(['b' => 'literal']);
        $this->assertInstanceOf(FunctionExpression::class, $function);
        $this->assertSame('MyFunc', $function->getName());

        $function = $this->functions->MyFunc(['b'], ['string'], 'integer');
        $this->assertSame('integer', $function->getReturnType());
    }

    /**
     * Tests generating a generic aggregate call
     */
    public function testArbitraryAggregate(): void
    {
        $function = $this->functions->aggregate('MyFunc', ['b' => 'literal']);
        $this->assertInstanceOf(AggregateExpression::class, $function);
        $this->assertSame('MyFunc', $function->getName());

        $function = $this->functions->aggregate('MyFunc', ['b'], ['string'], 'integer');
        $this->assertSame('integer', $function->getReturnType());
    }

    /**
     * Tests generating a SUM() function
     */
    public function testSum(): void
    {
        $function = $this->functions->sum('total');
        $this->assertInstanceOf(AggregateExpression::class, $function);
        $this->assertSame('float', $function->getReturnType());

        $function = $this->functions->sum('total', ['integer']);
        $this->assertInstanceOf(AggregateExpression::class, $function);
        $this->assertSame('integer', $function->getReturnType());
    }

    /**
     * Tests generating a AVG() function
     */
    public function testAvg(): void
    {
        $function = $this->functions->avg('salary');
        $this->assertInstanceOf(AggregateExpression::class, $function);
        $this->assertSame('float', $function->getReturnType());
    }

    /**
     * Tests generating a MAX() function
     */
    public function testMax(): void
    {
        $function = $this->functions->max('total');
        $this->assertInstanceOf(AggregateExpression::class, $function);
        $this->assertSame('float', $function->getReturnType());

        $function = $this->functions->max('created', ['datetime']);
        $this->assertInstanceOf(AggregateExpression::class, $function);
        $this->assertSame('datetime', $function->getReturnType());
    }

    /**
     * Tests generating a MIN() function
     */
    public function testMin(): void
    {
        $function = $this->functions->min('created', ['date']);
        $this->assertInstanceOf(AggregateExpression::class, $function);
        $this->assertSame('date', $function->getReturnType());
    }

    /**
     * Tests generating a COUNT() function
     */
    public function testCount(): void
    {
        $function = $this->functions->count('*');
        $this->assertInstanceOf(AggregateExpression::class, $function);
        $this->assertSame('integer', $function->getReturnType());
    }

    /**
     * Tests generating a CONCAT() function
     */
    public function testConcat(): void
    {
        $function = $this->functions->concat(['title' => 'literal', ' is a string']);
        $this->assertInstanceOf(FunctionExpression::class, $function);
        $this->assertSame('string', $function->getReturnType());
    }

    /**
     * Tests generating a COALESCE() function
     */
    public function testCoalesce(): void
    {
        $function = $this->functions->coalesce(['NULL' => 'literal', '1', 'a'], ['a' => 'date']);
        $this->assertInstanceOf(FunctionExpression::class, $function);
        $this->assertSame('date', $function->getReturnType());
    }

    /**
     * Tests generating a CAST() function
     */
    public function testCast(): void
    {
        $function = $this->functions->cast('field', 'varchar');
        $this->assertInstanceOf(FunctionExpression::class, $function);
        $this->assertSame('string', $function->getReturnType());

        $function = $this->functions->cast($this->functions->now(), 'varchar');
        $this->assertInstanceOf(FunctionExpression::class, $function);
        $this->assertSame('string', $function->getReturnType());
    }

    /**
     * Tests generating a NOW(), CURRENT_TIME() and CURRENT_DATE() function
     */
    public function testNow(): void
    {
        $function = $this->functions->now();
        $this->assertInstanceOf(FunctionExpression::class, $function);
        $this->assertSame('datetime', $function->getReturnType());

        $function = $this->functions->now('date');
        $this->assertInstanceOf(FunctionExpression::class, $function);
        $this->assertSame('date', $function->getReturnType());

        $function = $this->functions->now('time');
        $this->assertInstanceOf(FunctionExpression::class, $function);
        $this->assertSame('time', $function->getReturnType());
    }

    /**
     * Tests generating a EXTRACT() function
     */
    public function testExtract(): void
    {
        $function = $this->functions->extract('day', 'created');
        $this->assertInstanceOf(FunctionExpression::class, $function);
        $this->assertSame('integer', $function->getReturnType());

        $function = $this->functions->datePart('year', 'modified');
        $this->assertInstanceOf(FunctionExpression::class, $function);
        $this->assertSame('integer', $function->getReturnType());
    }

    /**
     * Tests generating a DATE_ADD() function
     */
    public function testDateAdd(): void
    {
        $function = $this->functions->dateAdd('created', -3, 'day');
        $this->assertInstanceOf(FunctionExpression::class, $function);
        $this->assertSame('datetime', $function->getReturnType());

        $function = $this->functions->dateAdd(new IdentifierExpression('created'), -3, 'day');
        $this->assertInstanceOf(FunctionExpression::class, $function);
    }

    /**
     * Tests generating a DAYOFWEEK() function
     */
    public function testDayOfWeek(): void
    {
        $function = $this->functions->dayOfWeek('created');
        $this->assertInstanceOf(FunctionExpression::class, $function);
        $this->assertSame('integer', $function->getReturnType());

        $function = $this->functions->weekday('created');
        $this->assertInstanceOf(FunctionExpression::class, $function);
        $this->assertSame('integer', $function->getReturnType());
    }

    /**
     * Tests generating a RAND() function
     */
    public function testRand(): void
    {
        $function = $this->functions->rand();
        $this->assertInstanceOf(FunctionExpression::class, $function);
        $this->assertSame('float', $function->getReturnType());
    }

    /**
     * Tests generating a ROW_NUMBER() window function
     */
    public function testRowNumber(): void
    {
        $function = $this->functions->rowNumber();
        $this->assertInstanceOf(AggregateExpression::class, $function);
        $this->assertSame('integer', $function->getReturnType());
    }

    /**
     * Tests generating a LAG() window function
     */
    public function testLag(): void
    {
        $function = $this->functions->lag('field', 1);
        $this->assertInstanceOf(AggregateExpression::class, $function);
        $this->assertSame('float', $function->getReturnType());

        $function = $this->functions->lag('field', 1, 10, 'integer');
        $this->assertInstanceOf(AggregateExpression::class, $function);
        $this->assertSame('integer', $function->getReturnType());
    }

    /**
     * Tests generating a LEAD() window function
     */
    public function testLead(): void
    {
        $function = $this->functions->lead('field', 1);
        $this->assertInstanceOf(AggregateExpression::class, $function);
        $this->assertSame('float', $function->getReturnType());

        $function = $this->functions->lead('field', 1, 10, 'integer');
        $this->assertInstanceOf(AggregateExpression::class, $function);
        $this->assertSame('integer', $function->getReturnType());
    }
}
