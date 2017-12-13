<?php

namespace Amp\ParallelClosure\Internal;

use Amp\Loop;
use Amp\Parallel\Worker\DefaultPool;

/** @internal */
function pool() {
    $pool = Loop::getState(__FUNCTION__);

    if ($pool === null) {
        $pool = new DefaultPool;

        Loop::setState(__FUNCTION__, $pool);
    }

    return $pool;
}