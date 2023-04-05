<?php
declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder;

use ArrayIterator;
use Closure;
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

    /**
     * Iterates the bindings passing the param/value as arguments to the closure
     *
     * Use this method to associate any placeholders and values to a prepared statement when executing
     * the query through e.g. PDO or Doctrine DBAL etc. The closure receives the prefixed placeholder
     * name and a `VaLue` object instance of the value.
     *
     * @param Closure $closure
     *
     * @return void
     */
    public function associateTo(Closure $closure): void
    {
        foreach ($this->bindings as $p => $v) {
            $closure($p, $v);
        }
    }

    /**
     * @param string $param The placeholder name including leading `:` e.g. `:name`
     * @param mixed $value The value to be bound
     * @param string|int|null $type The type of the value in SQL e.g. \PDO::PARAM_STR, etc
     *
     * @return void
     */
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
