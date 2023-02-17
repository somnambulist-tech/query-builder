<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Query\Expressions;

use Somnambulist\Components\QueryBuilder\Exceptions\QueryException;
use Somnambulist\Components\QueryBuilder\Query\ExpressionSet;
use function array_key_exists;

/**
 * @property array<string, CommonTableExpression> $expressions
 */
class WithExpression extends ExpressionSet
{
    public function add(CommonTableExpression $cte): self
    {
        $this->expressions[] = $cte;

        return $this;
    }

    public function has(int|string $key): bool
    {
        foreach ($this->expressions as $cte) {
            if ($cte->getName() === $key) {
                return true;
            }
        }

        return false;
    }

    public function get(int|string $key): CommonTableExpression
    {
        if (array_key_exists($key, $this->expressions)) {
            return $this->expressions[$key];
        }

        foreach ($this->expressions as $cte) {
            if ($cte->getName() === $key) {
                return $cte;
            }
        }

        throw QueryException::noWithExpressionNamed($key);
    }

    public function remove(int|string $key): static
    {
        foreach ($this->expressions as $k => $cte) {
            if ($cte->getName()->getIdentifier() === $key) {
                unset($this->expressions[$k]);
                break;
            }
        }

        return $this;
    }
}
