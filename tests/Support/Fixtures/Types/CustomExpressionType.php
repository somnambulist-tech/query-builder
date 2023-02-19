<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Tests\Support\Fixtures\Types;

use Doctrine\DBAL\Types\StringType;
use Somnambulist\Components\QueryBuilder\Query\ExpressionInterface;
use Somnambulist\Components\QueryBuilder\Query\Expressions\FunctionExpression;
use Somnambulist\Components\QueryBuilder\TypeCanCastToExpressionInterface;

class CustomExpressionType extends StringType implements TypeCanCastToExpressionInterface
{
    public function getName(): string
    {
        return 'custom';
    }

    public function toExpression($value): ExpressionInterface
    {
        return new FunctionExpression('CUSTOM', [$value]);
    }
}
