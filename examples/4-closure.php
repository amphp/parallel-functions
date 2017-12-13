<?php

require __DIR__ . '/../vendor/autoload.php';

use function Amp\ParallelClosure\parallel_map;
use function Amp\Promise\wait;

// Parallel function execution is nice, but it's even better being able to use closures instead of having to write a
// function that has to be autoloadable.
var_dump(wait(parallel_map([1, 2, 3], function ($time) {
    sleep($time); // a blocking function call, might also do blocking I/O here

    return $time * $time;
})));