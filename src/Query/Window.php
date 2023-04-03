<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Query;

use Closure;
use InvalidArgumentException;

/**
 * This defines the functions used for building window expressions.
 */
interface Window
{
    /**
     * @var string
     */
    public const PRECEDING = 'PRECEDING';

    /**
     * @var string
     */
    public const FOLLOWING = 'FOLLOWING';

    /**
     * @var string
     */
    public const RANGE = 'RANGE';

    /**
     * @var string
     */
    public const ROWS = 'ROWS';

    /**
     * @var string
     */
    public const GROUPS = 'GROUPS';

    /**
     * Adds one or more partition expressions to the window.
     *
     * @param Expression|Closure|array<Expression|string>|string $partitions Partition expressions
     *
     * @return $this
     */
    public function partition(Expression|Closure|array|string $partitions): self;

    /**
     * Adds one or more order by clauses to the window.
     *
     * @param Expression|Closure|array<Expression|string>|string $fields Order expressions
     *
     * @return $this
     */
    public function orderBy(Expression|Closure|array|string $fields): self;

    /**
     * Adds a simple range frame to the window.
     *
     * `$start`:
     *  - `0` - 'CURRENT ROW'
     *  - `null` - 'UNBOUNDED PRECEDING'
     *  - offset - 'offset PRECEDING'
     *
     * `$end`:
     *  - `0` - 'CURRENT ROW'
     *  - `null` - 'UNBOUNDED FOLLOWING'
     *  - offset - 'offset FOLLOWING'
     *
     * If you need to use 'FOLLOWING' with frame start or
     * 'PRECEDING' with frame end, use `frame()` instead.
     *
     * @param Expression|string|int|null $start Frame start
     * @param Expression|string|int|null $end Frame end
     *  If not passed in, only frame start SQL will be generated.
     *
     * @return $this
     */
    public function range(Expression|string|int|null $start, Expression|string|int|null $end = 0): self;

    /**
     * Adds a simple rows frame to the window.
     *
     * See `range()` for details.
     *
     * @param int|null $start Frame start
     * @param int|null $end Frame end
     *  If not passed in, only frame start SQL will be generated.
     *
     * @return $this
     */
    public function rows(?int $start, ?int $end = 0): self;

    /**
     * Adds a simple groups frame to the window.
     *
     * See `range()` for details.
     *
     * @param int|null $start Frame start
     * @param int|null $end Frame end
     *  If not passed in, only frame start SQL will be generated.
     *
     * @return $this
     */
    public function groups(?int $start, ?int $end = 0): self;

    /**
     * Adds a frame to the window.
     *
     * Use the `range()`, `rows()` or `groups()` helpers if you need simple
     * 'BETWEEN offset PRECEDING and offset FOLLOWING' frames.
     *
     * You can specify any direction for both frame start and frame end.
     *
     * With both `$startOffset` and `$endOffset`:
     *  - `0` - 'CURRENT ROW'
     *  - `null` - 'UNBOUNDED'
     *
     * @param string $type Frame type
     * @param Expression|string|int|null $startOffset Frame start offset
     * @param string $startDirection Frame start direction
     * @param Expression|string|int|null $endOffset Frame end offset
     * @param string $endDirection Frame end direction
     *
     * @return $this
     * @throws InvalidArgumentException WHen offsets are negative.
     * @psalm-param self::RANGE|self::ROWS|self::GROUPS $type
     * @psalm-param self::PRECEDING|self::FOLLOWING $startDirection
     * @psalm-param self::PRECEDING|self::FOLLOWING $endDirection
     */
    public function frame(
        string $type,
        Expression|string|int|null $startOffset,
        string $startDirection,
        Expression|string|int|null $endOffset,
        string $endDirection
    ): self;

    /**
     * Adds current row frame exclusion.
     *
     * @return $this
     */
    public function excludeCurrent(): self;

    /**
     * Adds group frame exclusion.
     *
     * @return $this
     */
    public function excludeGroup(): self;

    /**
     * Adds ties frame exclusion.
     *
     * @return $this
     */
    public function excludeTies(): self;
}
