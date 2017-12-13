<?php

require __DIR__ . '/../vendor/autoload.php';

use function Amp\ParallelFunctions\map;
use function Amp\Promise\wait;

// We have seen that the order can vary in the previous example, values returned have a deterministic order.
\var_dump(wait(map([1, 2, 3], 'abs')));
