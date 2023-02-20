<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler\Dialects\Common\Listeners;

use Somnambulist\Components\QueryBuilder\Compiler\Events\PostSelectQueryCompile;
use function preg_match;

/**
 * Ensures all placeholders are correctly bound to the value binder for the query
 */
class PropagateBoundParameters
{
    public function __invoke(PostSelectQueryCompile $event): void
    {
        if ($event->query->getBinder() !== $event->binder) {
            foreach ($event->query->getBinder()->bindings() as $binding) {
                $placeholder = ':' . $binding->placeholder;

                if (preg_match('/' . $placeholder . '(?:\W|$)/', $event->sql) > 0) {
                    $event->binder->bind($placeholder, $binding->value, $binding->type);
                }
            }
        }
    }
}
