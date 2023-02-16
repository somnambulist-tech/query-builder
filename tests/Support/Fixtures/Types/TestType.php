<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Tests\Support\Fixtures\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Somnambulist\Components\QueryBuilder\Query\ExpressionInterface;
use Somnambulist\Components\QueryBuilder\Query\Expressions\FunctionExpression;
use Somnambulist\Components\QueryBuilder\TypeCanCastToExpressionInterface;

class TestType extends Type implements TypeCanCastToExpressionInterface
{
    public function getSQLDeclaration(array $column, AbstractPlatform $platform)
    {
        return $platform->getStringTypeDeclarationSQL($column);
    }

    public function getName()
    {
        return 'test_type';
    }

    public function toExpression($value): ExpressionInterface
    {
        return new FunctionExpression('CONCAT', [$value, ' - foo']);
    }
}
