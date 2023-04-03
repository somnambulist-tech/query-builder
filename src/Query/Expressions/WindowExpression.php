<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Query\Expressions;

use Closure;
use Somnambulist\Components\QueryBuilder\Query\Expression;
use Somnambulist\Components\QueryBuilder\Query\Window;

/**
 * This represents a SQL window expression used by aggregate and window functions.
 */
class WindowExpression implements Expression, Window
{
    protected IdentifierExpression $name;

    /**
     * @var array<Expression>
     */
    protected array $partitions = [];

    protected ?OrderByExpression $orderBy = null;

    protected ?array $frame = null;
    protected ?string $exclusion = null;

    public function __construct(string $name = '')
    {
        $this->name = new IdentifierExpression($name);
    }

    /**
     * Return whether is only a named window expression.
     *
     * These window expressions only specify a named window and do not
     * specify their own partitions, frame or order.
     */
    public function isNamedOnly(): bool
    {
        return $this->name->getIdentifier() && (!$this->partitions && !$this->frame && !$this->orderBy);
    }

    public function name(string $name): self
    {
        $this->name = new IdentifierExpression($name);

        return $this;
    }

    public function partition(Expression|Closure|array|string $partitions): self
    {
        if (!$partitions) {
            return $this;
        }

        if ($partitions instanceof Closure) {
            $partitions = $partitions(new QueryExpression([], conjunction: ''));
        }

        if (!is_array($partitions)) {
            $partitions = [$partitions];
        }

        foreach ($partitions as &$partition) {
            if (is_string($partition)) {
                $partition = new IdentifierExpression($partition);
            }
        }

        $this->partitions = array_merge($this->partitions, $partitions);

        return $this;
    }

    public function orderBy(Expression|Closure|array|string $fields): self
    {
        if (!$fields) {
            return $this;
        }

        $this->orderBy ??= new OrderByExpression();

        if ($fields instanceof Closure) {
            $fields = $fields(new QueryExpression([], conjunction: ''));
        }

        $this->orderBy->add($fields);

        return $this;
    }

    public function range(Expression|string|int|null $start, Expression|string|int|null $end = 0): self
    {
        return $this->frame(self::RANGE, $start, self::PRECEDING, $end, self::FOLLOWING);
    }

    public function rows(?int $start, ?int $end = 0): self
    {
        return $this->frame(self::ROWS, $start, self::PRECEDING, $end, self::FOLLOWING);
    }

    public function groups(?int $start, ?int $end = 0): self
    {
        return $this->frame(self::GROUPS, $start, self::PRECEDING, $end, self::FOLLOWING);
    }

    /**
     * @inheritDoc
     */
    public function frame(
        string $type,
        Expression|string|int|null $startOffset,
        string $startDirection,
        Expression|string|int|null $endOffset,
        string $endDirection
    ): self
    {
        $this->frame = [
            'type' => $type,
            'start' => [
                'offset' => $startOffset,
                'direction' => $startDirection,
            ],
            'end' => [
                'offset' => $endOffset,
                'direction' => $endDirection,
            ],
        ];

        return $this;
    }

    public function excludeCurrent(): self
    {
        $this->exclusion = 'CURRENT ROW';

        return $this;
    }

    public function excludeGroup(): self
    {
        $this->exclusion = 'GROUP';

        return $this;
    }

    public function excludeTies(): self
    {
        $this->exclusion = 'TIES';

        return $this;
    }

    public function getName(): IdentifierExpression
    {
        return $this->name;
    }

    public function getPartitions(): array
    {
        return $this->partitions;
    }

    public function getOrderBy(): ?OrderByExpression
    {
        return $this->orderBy;
    }

    public function getFrame(): ?array
    {
        return $this->frame;
    }

    public function getExclusion(): ?string
    {
        return $this->exclusion;
    }

    public function traverse(Closure $callback): self
    {
        $callback($this->name);
        foreach ($this->partitions as $partition) {
            $callback($partition);
            $partition->traverse($callback);
        }

        if ($this->orderBy) {
            $callback($this->orderBy);
            $this->orderBy->traverse($callback);
        }

        if ($this->frame !== null) {
            $offset = $this->frame['start']['offset'];
            if ($offset instanceof Expression) {
                $callback($offset);
                $offset->traverse($callback);
            }
            $offset = $this->frame['end']['offset'] ?? null;
            if ($offset instanceof Expression) {
                $callback($offset);
                $offset->traverse($callback);
            }
        }

        return $this;
    }

    public function __clone()
    {
        $this->name = clone $this->name;

        foreach ($this->partitions as $i => $partition) {
            $this->partitions[$i] = clone $partition;
        }

        if ($this->orderBy !== null) {
            $this->orderBy = clone $this->orderBy;
        }
    }
}
