<?php

require __DIR__ . '/../vendor/autoload.php';

use function Amp\ParallelClosure\parallel_map;
use function Amp\Promise\wait;

$start = microtime(true);

// sleep() is executed in child processes, the results are sent back to the parent.
//
// All communication is non-blocking and can be used in an event loop. Amp\Promise\wait() can be used to use the library
// in a traditional synchronous environment.
wait(parallel_map([1, 2, 3], 'sleep'));

print 'Took ' . (microtime(true) - $start) . ' milliseconds.' . \PHP_EOL;
