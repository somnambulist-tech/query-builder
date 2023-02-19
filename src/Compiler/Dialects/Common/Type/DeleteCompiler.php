<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Type;

use Closure;
use Somnambulist\Components\QueryBuilder\Compiler\Behaviours\CompileExpressionsToString;
use Somnambulist\Components\QueryBuilder\Compiler\Behaviours\DispatchesCompilerEvents;
use Somnambulist\Components\QueryBuilder\Compiler\Behaviours\IsCompilable;
use Somnambulist\Components\QueryBuilder\Compiler\AbstractCompiler;
use Somnambulist\Components\QueryBuilder\Compiler\DispatchesCompilerEventsInterface;
use Somnambulist\Components\QueryBuilder\Query\ExpressionInterface;
use Somnambulist\Components\QueryBuilder\Query\Query;
use Somnambulist\Components\QueryBuilder\Query\Type\DeleteQuery;
use Somnambulist\Components\QueryBuilder\ValueBinder;
use function implode;
use function sprintf;

class DeleteCompiler extends AbstractCompiler implements DispatchesCompilerEventsInterface
{
    use IsCompilable;
    use CompileExpressionsToString;
    use DispatchesCompilerEvents;

    protected array $templates = [
        'delete'   => 'DELETE',
        'modifier' => '%s',
        'with'     => '%s',
        'from'     => ' %s',
        'where'    => ' WHERE %s',
        'group'    => ' %s',
        'epilog'   => ' %s',
        'comment'  => '/* %s */ ',
    ];
    protected array $order = ['comment', 'with', 'delete', 'modifier', 'from', 'where', 'epilog'];

    public function compile(mixed $expression, ValueBinder $binder): string
    {
        /** @var DeleteQuery $expression */

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
            }
        };
    }
}
