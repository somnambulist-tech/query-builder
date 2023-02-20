<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Tests\Compiler\Dialects;

use PHPUnit\Framework\TestCase;
use Somnambulist\Components\QueryBuilder\Compiler\CompilerInterface;
use Somnambulist\Components\QueryBuilder\Compiler\Dialects\Postgres\Listeners\HavingPreProcessor;
use Somnambulist\Components\QueryBuilder\Compiler\Events\PreHavingExpressionCompile;
use Somnambulist\Components\QueryBuilder\Query\Type\SelectQuery;
use Somnambulist\Components\QueryBuilder\Tests\Support\QueryAssertsTrait;
use Somnambulist\Components\QueryBuilder\Tests\Support\QueryCompilerBuilderTrait;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use function dump;

class PostgresTest extends TestCase
{
    use QueryAssertsTrait;
    use QueryCompilerBuilderTrait;

    protected ?CompilerInterface $compiler = null;

    protected function setUp(): void
    {
        $this->compiler = $this->buildCompiler();

        $listener = new HavingPreProcessor();
        $listener->setCompiler($this->compiler);

        $this->dispatcher->addListener(PreHavingExpressionCompile::class, $listener);
    }

    protected function tearDown(): void
    {
        $this->compiler = null;
        $this->dispatcher = null;
    }

    public function testHavingReplacesAlias(): void
    {
        $query = new SelectQuery();
        $query
            ->select([
                'posts.author_id',
                'post_count' => $query->func()->count('posts.id'),
            ])
            ->groupBy('posts.author_id')
            ->having([$query->newExpr()->gte('post_count', 2, 'integer')]);

        $expected = 'SELECT posts.author_id, (COUNT(posts.id)) AS post_count ' .
            'GROUP BY posts.author_id HAVING COUNT(posts.id) >= :c_0';

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertSame($expected, $sql);
    }

    /**
     * Test that having queries replaces nothing if no alias is used.
     */
    public function testHavingWhenNoAliasIsUsed(): void
    {
        $query = new SelectQuery();
        $query
            ->select([
                'posts.author_id',
                'post_count' => $query->func()->count('posts.id'),
            ])
            ->groupBy('posts.author_id')
            ->having([$query->newExpr()->gte('posts.author_id', 2, 'integer')]);

        $expected = 'SELECT posts.author_id, (COUNT(posts.id)) AS post_count ' .
            'GROUP BY posts.author_id HAVING posts.author_id >= :c_0';

        $sql = $this->compiler->compile($query, new ValueBinder());

        $this->assertSame($expected, $sql);
    }
}
