<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Query\Expressions;

use Closure;
use Somnambulist\Components\QueryBuilder\Query\ExpressionInterface;
use Somnambulist\Components\QueryBuilder\Query\Query;

/**
 * Represents a single identifier name in the database.
 *
 * Identifier values are unsafe with user supplied data. Values will be quoted when identifier quoting is enabled.
 *
 * @see Query::identifier()
 */
class IdentifierExpression implements ExpressionInterface
{
    public function __construct(
        protected string $identifier,
        protected ?string $collation = null
    ) {
    }

    public function identifier(string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function collate(?string $collation): self
    {
        $this->collation = $collation;

        return $this;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getCollation(): ?string
    {
        return $this->collation;
    }

    public function traverse(Closure $callback): self
    {
        return $this;
    }
}
