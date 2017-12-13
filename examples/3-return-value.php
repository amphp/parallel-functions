<?php

require __DIR__ . '/../vendor/autoload.php';

use function Amp\ParallelClosure\parallel_map;
use function Amp\Promise\wait;

// We have seen that the order can vary in the previous example, values returned have a deterministic order.
var_dump(wait(parallel_map([1, 2, 3], 'abs')));