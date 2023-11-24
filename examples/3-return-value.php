<?php

require __DIR__ . '/../vendor/autoload.php';

use function Amp\ParallelFunctions\parallelMap;

// We have seen that the order can vary in the previous example, values returned have a deterministic order.
var_dump(parallelMap([1, 2, 3], 'abs'));
