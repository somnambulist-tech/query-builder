<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Query\Expressions;

use Somnambulist\Components\QueryBuilder\Query\Expression;
use Somnambulist\Components\QueryBuilder\Query\HasReturnType;
use Somnambulist\Components\QueryBuilder\TypeCasterManager;
use Somnambulist\Components\QueryBuilder\TypeMap;

/**
 * This class represents a function call string in a SQL statement.
 *
 * Calls can be constructed by passing the name of the function and a list of params.
 * For security reasons, all params passed are quoted by default unless explicitly told otherwise.
 */
class FunctionExpression extends QueryExpression implements HasReturnType
{
    /**
     * The name of the function to be constructed when generating the SQL string
     *
     * @var string
     */
    protected string $name;
    protected string $returnType;

    /**
     * Takes a name for the function to be invoked and a list of params
     * to be passed into the function. Optionally you can pass a list of types to
     * be used for each bound param.
     *
     * By default, all params that are passed will be quoted. If you wish to use
     * literal arguments, you need to explicitly hint this function.
     *
     * ### Examples:
     *
     * `$f = new FunctionExpression('CONCAT', ['PHP', ' rules']);`
     *
     * Previous line will generate `CONCAT('PHP', ' rules')`
     *
     * `$f = new FunctionExpression('CONCAT', ['name' => 'literal', ' rules']);`
     *
     * Will produce `CONCAT(name, ' rules')`
     */
    public function __construct(string $name, array $params = [], array $types = [], string $returnType = 'string')
    {
        $this->name = $name;
        $this->returnType = $returnType;

        parent::__construct($params, new TypeMap($types), ',');
    }

    /**
     * Gets the name of the SQL function to be invoked in this expression.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Sets the name of the SQL function to be invoked in this expression.
     *
     * @param string $name The name of the function
     *
     * @return $this
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getReturnType(): string
    {
        return $this->returnType;
    }

    public function setReturnType(string $returnType): self
    {
        $this->returnType = $returnType;

        return $this;
    }

    /**
     * Adds one or more arguments for the function call.
     *
     * @param Expression|array|string $conditions list of arguments to be passed to the function
     * If associative the key would be used as argument when value is 'literal'
     * @param array<string, string> $types Associative array of types to be associated with the
     * passed arguments
     * @param bool $prepend Whether to prepend or append to the list of arguments
     *
     * @return $this
     * @psalm-suppress MoreSpecificImplementedParamType
     * @see FunctionExpression::__construct() for more details.
     */
    public function add(Expression|array|string $conditions, array $types = [], bool $prepend = false): self
    {
        $put = $prepend ? 'array_unshift' : 'array_push';
        $typeMap = $this->typeMap->setTypes($types);

        foreach ($conditions as $k => $p) {
            if ($p === 'literal') {
                $put($this->conditions, $k);
                continue;
            }

            if ($p === 'identifier') {
                $put($this->conditions, new IdentifierExpression($k));
                continue;
            }

            $type = $typeMap->type($k);

            if ($type !== null && !$p instanceof Expression) {
                $p = TypeCasterManager::castTo($p, $type);
            }

            if ($p instanceof Expression) {
                $put($this->conditions, $p);
                continue;
            }

            $put($this->conditions, ['value' => $p, 'type' => $type]);
        }

        return $this;
    }

    /**
     * The name of the function is in itself an expression to generate, thus
     * always adding 1 to the amount of expressions stored in this object.
     *
     * @return int
     */
    public function count(): int
    {
        return 1 + count($this->conditions);
    }
}
