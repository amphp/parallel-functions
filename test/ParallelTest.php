<?php

namespace Amp\ParallelFunctions\Test;

use Amp\PHPUnit\TestCase;
use function Amp\ParallelFunctions\parallel;

class ParallelTest extends TestCase {
    /**
     * @expectedException \Error
     * @expectedExceptionMessage Serialization of closure failed
     */
    public function testUnserializableClosure() {
        $unserializable = new class {};
        $callable = parallel(function () use ($unserializable) {
            return 1;
        });
    }
}
