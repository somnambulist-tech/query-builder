<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Tests\Compiler\Dialects;

use PHPUnit\Framework\TestCase;
use Somnambulist\Components\QueryBuilder\Query\Type\SelectQuery;

class PostgresTest extends TestCase
{
    public function testHavingReplacesAlias(): void
    {
        $this->markTestSkipped('not implemented');

        $query = new SelectQuery();
        $query
            ->select([
                'posts.author_id',
                'post_count' => $query->func()->count('posts.id'),
            ])
            ->groupBy('posts.author_id')
            ->having([$query->newExpr()->gte('post_count', 2, 'integer')]);

        $expected = 'SELECT posts.author_id, (COUNT(posts.id)) AS "post_count" ' .
            'GROUP BY posts.author_id HAVING COUNT(posts.id) >= :c0';
        $this->assertSame($expected, $query->sql());
    }

    /**
     * Test that having queries replaces nothing if no alias is used.
     */
    public function testHavingWhenNoAliasIsUsed(): void
    {
        $this->markTestSkipped('not implemented');

        $query = new SelectQuery();
        $query
            ->select([
                'posts.author_id',
                'post_count' => $query->func()->count('posts.id'),
            ])
            ->groupBy('posts.author_id')
            ->having([$query->newExpr()->gte('posts.author_id', 2, 'integer')]);

        $expected = 'SELECT posts.author_id, (COUNT(posts.id)) AS "post_count" ' .
            'GROUP BY posts.author_id HAVING posts.author_id >= :c0';

        $this->assertSame($expected, $query->sql());
    }
}
