<?php

namespace Amp\ParallelClosure\Test;

use Amp\MultiReasonException;
use Amp\PHPUnit\TestCase;
use function Amp\ParallelClosure\parallel_map;
use function Amp\Promise\wait;

class ParallelMapTest extends TestCase {
    public function testValidInput() {
        $this->assertSame([3, 4, 5], wait(parallel_map([1, 2, 3], function ($input) {
            return $input + 2;
        })));
    }

    public function testException() {
        $this->expectException(MultiReasonException::class);

        wait(parallel_map([1, 2, 3], function () {
            throw new \Exception;
        }));
    }

    public function testExecutesAllTasksOnException() {
        $files = [
            [0, \tempnam(\sys_get_temp_dir(), 'amp-parallel-closure-')],
            [1, \tempnam(\sys_get_temp_dir(), 'amp-parallel-closure-')],
            [2, \tempnam(\sys_get_temp_dir(), 'amp-parallel-closure-')],
        ];

        try {
            wait(parallel_map($files, function ($args) {
                list($id, $filename) = $args;

                if ($id === 0) {
                    throw new \Exception;
                }

                \sleep(1);
                \file_put_contents($filename, $id);
            }));

            $this->fail('No exception thrown.');
        } catch (MultiReasonException $e) {
            $this->assertStringEqualsFile($files[1][1], '1');
            $this->assertStringEqualsFile($files[2][1], '2');
        }
    }
}