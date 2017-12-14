<?php

require __DIR__ . '/../vendor/autoload.php';

use function Amp\ParallelFunctions\parallelMap;
use function Amp\Promise\wait;

// We have seen that the order can vary in the previous example, values returned have a deterministic order.
\var_dump(wait(parallelMap([1, 2, 3], 'abs')));
