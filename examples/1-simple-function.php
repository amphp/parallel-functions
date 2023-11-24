<?php declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use function Amp\ParallelFunctions\parallelMap;

$start = microtime(true);

// sleep() is executed in child processes, the results are sent back to the parent.
//
// All communication is non-blocking and can be used in an event loop.
parallelMap([1, 2, 3], 'sleep');

print 'Took ' . (microtime(true) - $start) . ' seconds.' . \PHP_EOL;
