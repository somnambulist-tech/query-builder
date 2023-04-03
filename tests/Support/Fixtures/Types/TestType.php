<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Tests\Support\Fixtures\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Somnambulist\Components\QueryBuilder\Query\Expression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\FunctionExpression;
use Somnambulist\Components\QueryBuilder\TypeCanCastToExpression;

class TestType extends Type implements TypeCanCastToExpression
{
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getStringTypeDeclarationSQL($column);
    }

    public function getName(): string
    {
        return 'test_type';
    }

    public function toExpression($value): Expression
    {
        return new FunctionExpression('CONCAT', [$value, ' - foo']);
    }
}
