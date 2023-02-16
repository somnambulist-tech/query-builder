<?php
declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;
use function str_starts_with;
use function substr;

/**
 * Value binder class manages list of values bound to conditions.
 *
 * @internal
 */
class ValueBinder implements Countable, IteratorAggregate
{
    /**
     * @var Value[]
     */
    private array $bindings = [];
    private int $count = 0;

    public function bind(string $param, mixed $value, string|int|null $type = null): void
    {
        $this->doBind($param, $value, $type);
    }

    public function get(string $param): ?Value
    {
        return $this->bindings[$this->ensurePrefixed($param)] ?? null;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->bindings);
    }

    public function count(): int
    {
        return count($this->bindings);
    }

    private function ensurePrefixed(string $param): string
    {
        if (!str_starts_with($param, ':') && $param !== '?') {
            $param = ':' . $param;
        }

        return $param;
    }

    private function doBind(string $param, mixed $value, string|int|null $type = null): Value
    {
        $param = $this->ensurePrefixed($param);

        return $this->bindings[$param] = new Value($param, $value, $type, substr($param, 1));
    }

    public function placeholder(string $token): string
    {
        $number = $this->count++;

        if (!str_starts_with($token, ':') && $token !== '?') {
            $token = sprintf(':%s_%s', $token, $number);
        }

        return $token;
    }

    public function generateManyNamedPlaceholders(iterable $values, string|int|null $type = null): array
    {
        $placeholders = [];

        foreach ($values as $k => $value) {
            $placeholders[$k] = $this->doBind($this->placeholder('c'), $value, $type)->param;
        }

        return $placeholders;
    }

    /**
     * @return Value[]
     */
    public function bindings(): array
    {
        return $this->bindings;
    }

    public function reset(): void
    {
        $this->bindings = [];
        $this->count = 0;
    }

    public function resetCount(): void
    {
        $this->count = 0;
    }
}
