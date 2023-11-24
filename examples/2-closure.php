<?php declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use function Amp\ParallelFunctions\parallelMap;

// Parallel function execution is nice, but it's even better being able to use closures instead of having to write a
// function that has to be autoloadable.
var_dump(parallelMap([1, 2, 3], function ($time) {
    sleep($time); // a blocking function call, might also do blocking I/O here

    return $time * $time;
}));
