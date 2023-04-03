<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Tests\Support\Fixtures\Types;

use Doctrine\DBAL\Types\StringType;
use Somnambulist\Components\QueryBuilder\Query\Expression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\FunctionExpression;
use Somnambulist\Components\QueryBuilder\TypeCanCastToExpression;

class CustomExpressionType extends StringType implements TypeCanCastToExpression
{
    public function getName(): string
    {
        return 'custom';
    }

    public function toExpression($value): Expression
    {
        return new FunctionExpression('CUSTOM', [$value]);
    }
}
