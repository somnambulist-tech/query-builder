<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Tests\Query\Expressions;

use PHPUnit\Framework\TestCase;
use Somnambulist\Components\QueryBuilder\Compiler\CompilerInterface;
use Somnambulist\Components\QueryBuilder\Query\Expressions\QueryExpression;
use Somnambulist\Components\QueryBuilder\Tests\Support\QueryAssertsTrait;
use Somnambulist\Components\QueryBuilder\Tests\Support\QueryCompilerBuilderTrait;
use Somnambulist\Components\QueryBuilder\Tests\Support\ValueBinderContainsTrait;
use Somnambulist\Components\QueryBuilder\TypeMap;
use Somnambulist\Components\QueryBuilder\ValueBinder;

/**
 * Tests QueryExpression class
 */
class QueryExpressionTest extends TestCase
{
    use QueryAssertsTrait;
    use QueryCompilerBuilderTrait;
    use ValueBinderContainsTrait;

    protected ?CompilerInterface $compiler = null;

    protected function setUp(): void
    {
        $this->registerTypeCaster();
        $this->compiler = $this->buildDelegatingCompiler();
    }

    protected function tearDown(): void
    {
        $this->compiler = null;
    }

    /**
     * Test setConjunction()/getConjunction() works.
     */
    public function testConjunction(): void
    {
        $expr = new QueryExpression(['1', '2']);
        $binder = new ValueBinder();

        $this->assertSame($expr, $expr->useConjunction('+'));
        $this->assertSame('+', $expr->getConjunction());

        $result = $this->compiler->compile($expr, $binder);
        $this->assertSame('(1 + 2)', $result);
    }

    /**
     * Tests conditions with multi-word operators.
     *
     * @return void
     */
    public function testMultiWordOperators(): void
    {
        $expr = new QueryExpression(['FUNC(Users.first + Users.last) is not' => 'me']);
        $this->assertSame('FUNC(Users.first + Users.last) != :c_0', $this->compiler->compile($expr, new ValueBinder()));

        $expr = new QueryExpression(['FUNC(Users.name + Users.id) NOT SIMILAR TO' => 'pattern']);
        $this->assertSame('FUNC(Users.name + Users.id) NOT SIMILAR TO :c_0', $this->compiler->compile($expr, new ValueBinder()));
    }

    /**
     * Tests conditions with symbol operators.
     *
     * @return void
     */
    public function testSymbolOperators(): void
    {
        $expr = new QueryExpression(['Users.name =' => 'pattern']);
        $this->assertSame('Users.name = :c_0', $this->compiler->compile($expr, new ValueBinder()));

        $expr = new QueryExpression(['Users.name !=' => 'pattern']);
        $this->assertSame('Users.name != :c_0', $this->compiler->compile($expr, new ValueBinder()));

        $expr = new QueryExpression(['Users.name <>' => 'pattern']);
        $this->assertSame('Users.name <> :c_0', $this->compiler->compile($expr, new ValueBinder()));

        $expr = new QueryExpression(['Users.name >=' => 'pattern']);
        $this->assertSame('Users.name >= :c_0', $this->compiler->compile($expr, new ValueBinder()));

        $expr = new QueryExpression(['Users.name <=' => 'pattern']);
        $this->assertSame('Users.name <= :c_0', $this->compiler->compile($expr, new ValueBinder()));

        $expr = new QueryExpression(['Users.name !~' => 'pattern']);
        $this->assertSame('Users.name !~ :c_0', $this->compiler->compile($expr, new ValueBinder()));

        $expr = new QueryExpression(['Users.name *' => 'pattern']);
        $this->assertSame('Users.name * :c_0', $this->compiler->compile($expr, new ValueBinder()));

        $expr = new QueryExpression(['Users.name -' => 'pattern']);
        $this->assertSame('Users.name - :c_0', $this->compiler->compile($expr, new ValueBinder()));

        $expr = new QueryExpression(['Users.name \\' => 'pattern']);
        $this->assertSame('Users.name \\ :c_0', $this->compiler->compile($expr, new ValueBinder()));

        $expr = new QueryExpression(['Users.name @>' => 'pattern']);
        $this->assertSame('Users.name @> :c_0', $this->compiler->compile($expr, new ValueBinder()));
    }

    /**
     * Test and() and or() calls work transparently
     */
    public function testAndOrCalls(): void
    {
        $expr = new QueryExpression();
        $expected = QueryExpression::class;
        $this->assertInstanceOf($expected, $expr->and([]));
        $this->assertInstanceOf($expected, $expr->or([]));
    }

    /**
     * Test SQL generation with one element
     */
    public function testSqlGenerationOneClause(): void
    {
        $expr = new QueryExpression();
        $binder = new ValueBinder();
        $expr->add(['Users.username' => 'sally'], ['Users.username' => 'string']);

        $result = $this->compiler->compile($expr, $binder);
        $this->assertSame('Users.username = :c_0', $result);
    }

    /**
     * Test SQL generation with many elements
     */
    public function testSqlGenerationMultipleClauses(): void
    {
        $expr = new QueryExpression();
        $binder = new ValueBinder();
        $expr->add(
            [
                'Users.username' => 'sally',
                'Users.active' => 1,
            ],
            [
                'Users.username' => 'string',
                'Users.active' => 'boolean',
            ]
        );

        $result = $this->compiler->compile($expr, $binder);
        $this->assertSame('(Users.username = :c_0 AND Users.active = :c_1)', $result);
    }

    /**
     * Test that empty expressions don't emit invalid SQL.
     */
    public function testSqlWhenEmpty(): void
    {
        $expr = new QueryExpression();
        $binder = new ValueBinder();
        $result = $this->compiler->compile($expr, $binder);
        $this->assertSame('', $result);
    }

    /**
     * Test deep cloning of expression trees.
     */
    public function testDeepCloning(): void
    {
        $expr = new QueryExpression();
        $expr = $expr->add(new QueryExpression('1 + 1'))
            ->isNull('deleted')
            ->like('title', 'things%');

        $dupe = clone $expr;
        $this->assertEquals($dupe, $expr);
        $this->assertNotSame($dupe, $expr);
        $originalParts = [];
        $expr->iterateParts(function ($part) use (&$originalParts): void {
            $originalParts[] = $part;
        });
        $dupe->iterateParts(function ($part, $i) use ($originalParts): void {
            $this->assertNotSame($originalParts[$i], $part);
        });
    }

    /**
     * Tests the hasNestedExpression() function
     */
    public function testHasNestedExpression(): void
    {
        $expr = new QueryExpression();
        $this->assertFalse($expr->hasNestedExpression());

        $expr->add(['a' => 'b']);
        $this->assertTrue($expr->hasNestedExpression());

        $expr = new QueryExpression();
        $expr->add('a = b');
        $this->assertFalse($expr->hasNestedExpression());

        $expr->add(new QueryExpression('1 = 1'));
        $this->assertTrue($expr->hasNestedExpression());
    }

    /**
     * Returns the list of specific comparison methods
     */
    public static function methodsProvider(): array
    {
        return [
            ['eq'], ['notEq'], ['gt'], ['lt'], ['gte'], ['lte'], ['like'],
            ['notLike'], ['in'], ['notIn'],
        ];
    }

    /**
     * Tests that the query expression uses the type map when the specific comparison functions are used.
     *
     * @dataProvider methodsProvider
     */
    public function testTypeMapUsage(string $method): void
    {
        $expr = new QueryExpression([], new TypeMap(['created' => 'date']));
        if (in_array($method, ['in', 'notIn'])) {
            $expr->{$method}('created', ['foo']);
        } else {
            $expr->{$method}('created', 'foo');
        }

        $binder = new ValueBinder();
        $this->compiler->compile($expr, $binder);
        $bindings = $binder->bindings();
        $type = current($bindings)->type;

        $this->assertSame('date', $type);
    }

    /**
     * Tests that creating query expressions with either the
     * array notation or using the combinators will produce a
     * zero-count expression object.
     */
    public function testEmptyOr(): void
    {
        $expr = new QueryExpression();
        $expr = $expr->or([]);
        $expr = $expr->or([]);
        $this->assertCount(0, $expr);

        $expr = new QueryExpression(['OR' => []]);
        $this->assertCount(0, $expr);
    }

    /**
     * Tests that both conditions are generated for notInOrNull().
     */
    public function testNotInOrNull(): void
    {
        $expr = new QueryExpression();
        $expr->notInOrNull('test', ['one', 'two']);
        $this->assertEqualsSql(
            '(test NOT IN (:c_0,:c_1) OR (test) IS NULL)',
            $this->compiler->compile($expr, new ValueBinder())
        );
    }

    public function testCaseWithoutValue(): void
    {
        $expr = (new QueryExpression())
            ->case()
            ->when(1)
            ->then(2);

        $this->assertEqualsSql(
            'CASE WHEN :c_0 THEN :c_1 ELSE NULL END',
            $this->compiler->compile($expr, new ValueBinder())
        );
    }

    public function testCaseWithNullValue(): void
    {
        $expr = (new QueryExpression())
            ->case(null)
            ->when(1)
            ->then('Yes');

        $this->assertEqualsSql(
            'CASE NULL WHEN :c_0 THEN :c_1 ELSE NULL END',
            $this->compiler->compile($expr, new ValueBinder())
        );
    }

    public function testCaseWithValueAndType(): void
    {
        $expr = (new QueryExpression())
            ->case('1', 'integer')
            ->when(1)
            ->then('Yes');

        $binder = new ValueBinder();

        $this->assertEqualsSql(
            'CASE :c_0 WHEN :c_1 THEN :c_2 ELSE NULL END',
            $this->compiler->compile($expr, $binder)
        );

        $this->assertCount(3, $binder);
        $this->assertValueBinderContains($binder, ':c_0', ':c_1', ':c_2');
    }
}
