<?php

require __DIR__ . '/../vendor/autoload.php';

use function Amp\ParallelClosure\parallel_map;
use function Amp\Promise\wait;

// All output in the parallel environment is redirected to STDERR of the parent process automatically.
// You might notice that the output order varies here when running it multiple times.
wait(parallel_map([1, 2, 3], 'var_dump'));