<?php

require __DIR__ . '/../vendor/autoload.php';

use function Amp\ParallelFunctions\map;
use function Amp\Promise\wait;

// All output in the parallel environment is redirected to STDERR of the parent process automatically.
// You might notice that the output order varies here when running it multiple times.
wait(map([1, 2, 3], 'var_dump'));
