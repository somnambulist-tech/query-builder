<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Type;

use Closure;
use Exception;
use Somnambulist\Components\QueryBuilder\Compiler\Behaviours\CompileExpressionsToString;
use Somnambulist\Components\QueryBuilder\Compiler\Behaviours\DispatchesCompilerEvents;
use Somnambulist\Components\QueryBuilder\Compiler\Behaviours\IsCompilable;
use Somnambulist\Components\QueryBuilder\Compiler\AbstractCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\DispatchesCompilerEventsInterface;
use Somnambulist\Components\QueryBuilder\Query\ExpressionInterface;
use Somnambulist\Components\QueryBuilder\Query\Query;
use Somnambulist\Components\QueryBuilder\Query\Type\InsertQuery;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use function implode;
use function sprintf;

class InsertCompiler extends AbstractCompiler implements DispatchesCompilerEventsInterface
{
    use IsCompilable;
    use CompileExpressionsToString;
    use DispatchesCompilerEvents;

    protected array $templates = [
        'with'     => '%s',
        'where'    => ' WHERE %s',
        'epilog'   => ' %s',
        'comment'  => '/* %s */ ',
    ];
    protected array $order = ['comment', 'with', 'insert', 'values', 'epilog'];

    public function compile(mixed $expression, ValueBinder $binder): string
    {
        /** @var InsertQuery $expression */

        $sql = '';

        $expression->traverseParts(
            $this->sqlCompiler($sql, $expression, $binder),
            $this->order
        );

        return $sql;
    }

    protected function sqlCompiler(string &$sql, Query $query, ValueBinder $binder): Closure
    {
        return function ($part, $partName) use (&$sql, $query, $binder): void {
            if ($this->isNotCompilable($part)) {
                return;
            }

            $this->preCompile($partName, $part, $query, $binder);

            if ($part instanceof ExpressionInterface) {
                $part = [$this->compiler->compile($part, $binder)];
            }
            if (isset($this->templates[$partName])) {
                $part = $this->compileExpressionsToString((array)$part, $binder);
                $sql .= sprintf($this->templates[$partName], implode(', ', $part));

                $sql = $this->postCompile($partName, $sql, $query, $binder);

                return;
            }

            $sql .= match ($partName) {
                'insert' => $this->buildInsertPart($part, $query, $binder),
                'values' => $this->buildValuesPart($part, $query, $binder),
            };

            $sql = $this->postCompile($partName, $sql, $query, $binder);
        };
    }

    protected function buildInsertPart(array $parts, Query $query, ValueBinder $binder): string
    {
        if (!isset($parts[0])) {
            throw new Exception(
                'Could not compile insert query. No table was specified. Use "into()" to define a table.'
            );
        }

        $table = $parts[0];
        $columns = $this->compileExpressionsToString($parts[1], $binder);
        $modifiers = '';

        if (null !== $modifier = $query->clause('modifier')) {
            $modifiers = $this->compiler->compile($modifier, $binder);
        }

        return sprintf('INSERT%s INTO %s (%s)', $modifiers, $table, implode(', ', $columns));
    }

    protected function buildValuesPart(array $parts, Query $query, ValueBinder $binder): string
    {
        return implode('', $this->compileExpressionsToString($parts, $binder));
    }
}
