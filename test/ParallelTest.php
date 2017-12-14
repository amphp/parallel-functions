<?php

namespace Amp\ParallelFunctions\Test;

use Amp\PHPUnit\TestCase;
use function Amp\ParallelFunctions\parallel;

class ParallelTest extends TestCase {
    /**
     * @expectedException \Error
     * @expectedExceptionMessage Unsupported callable: Serialization of 'class@anonymous' is not allowed
     */
    public function testUnserializableClosure() {
        $unserializable = new class {
        };
        parallel(function () use ($unserializable) {
            return 1;
        });
    }
}
