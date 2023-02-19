<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Compiler;

use Psr\EventDispatcher\EventDispatcherInterface;

interface DispatchesCompilerEventsInterface
{
    public function setDispatcher(EventDispatcherInterface $dispatcher): void;
}
