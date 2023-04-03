<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Query\Expressions;

use Closure;
use Somnambulist\Components\QueryBuilder\Query\Expression;
use Somnambulist\Components\QueryBuilder\Query\Field;
use Somnambulist\Components\QueryBuilder\TypeCasterManager;

/**
 * An expression object that represents a SQL BETWEEN snippet
 */
class BetweenExpression implements Expression, Field
{
    public function __construct(
        protected Expression|string $field,
        protected mixed $from,
        protected mixed $to,
        protected ?string $type = null
    ) {
        $this->from = TypeCasterManager::castTo($this->from, $this->type);
        $this->to = TypeCasterManager::castTo($this->to, $this->type);
    }

    public function field(Expression|array|string $field): self
    {
        $this->field = $field;

        return $this;
    }

    public function from(mixed $from): self
    {
        $this->from = $from;

        return $this;
    }

    public function to(mixed $to): self
    {
        $this->to = $to;

        return $this;
    }

    public function getField(): Expression|array|string
    {
        return $this->field;
    }

    public function getFrom(): mixed
    {
        return $this->from;
    }

    public function getTo(): mixed
    {
        return $this->to;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function traverse(Closure $callback): self
    {
        foreach ([$this->field, $this->from, $this->to] as $part) {
            if ($part instanceof Expression) {
                $callback($part);
            }
        }

        return $this;
    }

    public function __clone()
    {
        foreach (['field', 'from', 'to'] as $part) {
            if ($this->{$part} instanceof Expression) {
                $this->{$part} = clone $this->{$part};
            }
        }
    }
}
