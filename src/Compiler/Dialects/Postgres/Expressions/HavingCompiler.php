<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Dialects\Postgres\Expressions;

use Somnambulist\Components\QueryBuilder\Compiler\AbstractCompiler;
use Somnambulist\Components\QueryBuilder\Query\Expressions\FunctionExpression;
use Somnambulist\Components\QueryBuilder\Query\Expressions\QueryExpression;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use function implode;
use function is_string;
use function preg_match_all;
use function preg_replace;
use function sprintf;
use function trim;

class HavingCompiler extends AbstractCompiler
{
    public function supports(mixed $expression): bool
    {
        return $expression === 'having';
    }

    public function compile(mixed $expression, ValueBinder $binder): string
    {
        /** @var QueryExpression $selectParts */
        $selectParts = $query->clause('select');

        foreach ($selectParts as $selectKey => $selectPart) {
            if (!$selectPart instanceof FunctionExpression) {
                continue;
            }

            foreach ($expression as $k => $p) {
                if (!is_string($p)) {
                    continue;
                }

                preg_match_all('/\b' . trim($selectKey, '"') . '\b/i', $p, $matches);

                if (empty($matches[0])) {
                    continue;
                }

                $parts[$k] = preg_replace(
                    ['/"/', '/\b' . trim($selectKey, '"') . '\b/i'],
                    ['', $this->compiler->compile($selectPart, $binder)],
                    $p
                );
            }
        }

        return sprintf(' HAVING %s', implode(', ', $parts));
    }
}
