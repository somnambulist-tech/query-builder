<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Tests\Compiler;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use PHPUnit\Framework\TestCase;
use Somnambulist\Components\QueryBuilder\Builder\Expressions\IdentifierExpression;
use Somnambulist\Components\QueryBuilder\Builder\FunctionsBuilder;
use Somnambulist\Components\QueryBuilder\TypeCaster;
use Somnambulist\Components\QueryBuilder\TypeCasters\DbalTypeCaster;
use Somnambulist\Components\QueryBuilder\Compiler\ExpressionCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Expressions\AggregateCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Expressions\FunctionCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Expressions\IdentifierCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\Expressions\WindowCompiler;
use Somnambulist\Components\QueryBuilder\ValueBinder;

class FunctionsCompilerTest extends TestCase
{
    protected ?FunctionsBuilder $functions = null;
    protected ?ExpressionCompiler $compiler = null;

    public function setUp(): void
    {
        parent::setUp();

        TypeCaster::register(new DbalTypeCaster($this->getMockBuilder(AbstractPlatform::class)->getMock()));

        $expr = new ExpressionCompiler([
            new AggregateCompiler(),
            new FunctionCompiler(),
            new IdentifierCompiler(),
            new WindowCompiler(),
        ]);

        $this->functions = new FunctionsBuilder();
        $this->compiler = $expr;
    }

    /**
     * Tests generating a generic function call
     */
    public function testArbitrary(): void
    {
        $function = $this->functions->MyFunc(['b' => 'literal']);
        $this->assertSame('MyFunc(b)', $this->compiler->compile($function, new ValueBinder()));
    }

    /**
     * Tests generating a generic aggregate call
     */
    public function testArbitraryAggregate(): void
    {
        $function = $this->functions->aggregate('MyFunc', ['b' => 'literal']);
        $this->assertSame('MyFunc(b)', $this->compiler->compile($function, new ValueBinder()));
    }

    /**
     * Tests generating a SUM() function
     */
    public function testSum(): void
    {
        $function = $this->functions->sum('total');
        $this->assertSame('SUM(total)', $this->compiler->compile($function, new ValueBinder()));

        $function = $this->functions->sum('total', ['integer']);
        $this->assertSame('SUM(total)', $this->compiler->compile($function, new ValueBinder()));
    }

    /**
     * Tests generating a AVG() function
     */
    public function testAvg(): void
    {
        $function = $this->functions->avg('salary');
        $this->assertSame('AVG(salary)', $this->compiler->compile($function, new ValueBinder()));
    }

    /**
     * Tests generating a MAX() function
     */
    public function testMax(): void
    {
        $function = $this->functions->max('total');
        $this->assertSame('MAX(total)', $this->compiler->compile($function, new ValueBinder()));

        $function = $this->functions->max('created', ['datetime']);
        $this->assertSame('MAX(created)', $this->compiler->compile($function, new ValueBinder()));
    }

    /**
     * Tests generating a MIN() function
     */
    public function testMin(): void
    {
        $function = $this->functions->min('created', ['date']);
        $this->assertSame('MIN(created)', $this->compiler->compile($function, new ValueBinder()));
    }

    /**
     * Tests generating a COUNT() function
     */
    public function testCount(): void
    {
        $function = $this->functions->count('*');
        $this->assertSame('COUNT(*)', $this->compiler->compile($function, new ValueBinder()));
    }

    /**
     * Tests generating a CONCAT() function
     */
    public function testConcat(): void
    {
        $function = $this->functions->concat(['title' => 'literal', ' is a string']);
        $this->assertSame('CONCAT(title, :param_0)', $this->compiler->compile($function, new ValueBinder()));
    }

    /**
     * Tests generating a COALESCE() function
     */
    public function testCoalesce(): void
    {
        $function = $this->functions->coalesce(['NULL' => 'literal', '1', 'a'], ['a' => 'date']);
        $this->assertSame('COALESCE(NULL, :param_0, :param_1)', $this->compiler->compile($function, new ValueBinder()));
    }

    /**
     * Tests generating a CAST() function
     */
    public function testCast(): void
    {
        $function = $this->functions->cast('field', 'varchar');
        $this->assertSame('CAST(field AS varchar)', $this->compiler->compile($function, new ValueBinder()));

        $function = $this->functions->cast($this->functions->now(), 'varchar');
        $this->assertSame('CAST(NOW() AS varchar)', $this->compiler->compile($function, new ValueBinder()));
    }

    /**
     * Tests generating a NOW(), CURRENT_TIME() and CURRENT_DATE() function
     */
    public function testNow(): void
    {
        $function = $this->functions->now();
        $this->assertSame('NOW()', $this->compiler->compile($function, new ValueBinder()));

        $function = $this->functions->now('date');
        $this->assertSame('CURRENT_DATE()', $this->compiler->compile($function, new ValueBinder()));

        $function = $this->functions->now('time');
        $this->assertSame('CURRENT_TIME()', $this->compiler->compile($function, new ValueBinder()));
    }

    /**
     * Tests generating a EXTRACT() function
     */
    public function testExtract(): void
    {
        $function = $this->functions->extract('day', 'created');
        $this->assertSame('EXTRACT(DAY FROM created)', $this->compiler->compile($function, new ValueBinder()));

        $function = $this->functions->datePart('year', 'modified');
        $this->assertSame('EXTRACT(YEAR FROM modified)', $this->compiler->compile($function, new ValueBinder()));
    }

    /**
     * Tests generating a DATE_ADD() function
     */
    public function testDateAdd(): void
    {
        $function = $this->functions->dateAdd('created', -3, 'day');
        $this->assertSame('DATE_ADD(created, INTERVAL -3 DAY)', $this->compiler->compile($function, new ValueBinder()));

        $function = $this->functions->dateAdd(new IdentifierExpression('created'), -3, 'day');
        $this->assertSame('DATE_ADD(created, INTERVAL -3 DAY)', $this->compiler->compile($function, new ValueBinder()));
    }

    /**
     * Tests generating a DAYOFWEEK() function
     */
    public function testDayOfWeek(): void
    {
        $function = $this->functions->dayOfWeek('created');
        $this->assertSame('DAYOFWEEK(created)', $this->compiler->compile($function, new ValueBinder()));

        $function = $this->functions->weekday('created');
        $this->assertSame('DAYOFWEEK(created)', $this->compiler->compile($function, new ValueBinder()));
    }

    /**
     * Tests generating a RAND() function
     */
    public function testRand(): void
    {
        $function = $this->functions->rand();
        $this->assertSame('RAND()', $this->compiler->compile($function, new ValueBinder()));
    }

    /**
     * Tests generating a ROW_NUMBER() window function
     */
    public function testRowNumber(): void
    {
        $function = $this->functions->rowNumber();
        $this->assertSame('ROW_NUMBER() OVER ()', $this->compiler->compile($function, new ValueBinder()));
    }

    /**
     * Tests generating a LAG() window function
     */
    public function testLag(): void
    {
        $function = $this->functions->lag('field', 1);
        $this->assertSame('LAG(field, 1) OVER ()', $this->compiler->compile($function, new ValueBinder()));

        $function = $this->functions->lag('field', 1, 10, 'integer');
        $this->assertSame('LAG(field, 1, :param_0) OVER ()', $this->compiler->compile($function, new ValueBinder()));
    }

    /**
     * Tests generating a LEAD() window function
     */
    public function testLead(): void
    {
        $function = $this->functions->lead('field', 1);
        $this->assertSame('LEAD(field, 1) OVER ()', $this->compiler->compile($function, new ValueBinder()));

        $function = $this->functions->lead('field', 1, 10, 'integer');
        $this->assertSame('LEAD(field, 1, :param_0) OVER ()', $this->compiler->compile($function, new ValueBinder()));
    }
}
