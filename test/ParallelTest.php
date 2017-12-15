<?php

namespace Amp\ParallelFunctions\Test;

use Amp\Parallel\Worker\Pool;
use Amp\PHPUnit\TestCase;
use Amp\Promise;
use Amp\Success;
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

    public function testCustomPool() {
        $mock = $this->createMock(Pool::class);
        $mock->expects($this->once())
            ->method("enqueue")
            ->willReturn(new Success(1));

        $callable = parallel(function () {
            return 0;
        }, $mock);

        $this->assertSame(1, Promise\wait($callable()));
    }
}
