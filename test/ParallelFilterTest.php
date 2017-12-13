<?php

namespace Amp\ParallelFunctions\Test;

use Amp\MultiReasonException;
use Amp\PHPUnit\TestCase;
use function Amp\ParallelFunctions\parallelFilter;
use function Amp\Promise\wait;

class ParallelFilterTest extends TestCase {
    public function testWithoutCallback() {
        $input = [1, 0, 3, false, true, null];

        $this->assertSame(\array_filter($input), wait(parallelFilter($input)));
    }

    public function testWithCallback() {
        $input = [1, 0, 3, false, true, null];
        $callback = function ($value) {
            return $value === false;
        };

        $this->assertSame(\array_filter($input, $callback), wait(parallelFilter($input, $callback)));
    }

    public function testWithCallbackAndFlagKey() {
        $input = [1, 0, 3, false, true, null];
        $callback = function ($key) {
            return $key === 2;
        };

        $this->assertSame(\array_filter($input, $callback, \ARRAY_FILTER_USE_KEY), wait(parallelFilter($input, $callback, \ARRAY_FILTER_USE_KEY)));
    }

    public function testWithCallbackAndFlagBoth() {
        $input = [1, 0, 3, false, true, null];
        $callback = function ($value, $key) {
            return $key === 2 || $value === true;
        };

        $this->assertSame(\array_filter($input, $callback, \ARRAY_FILTER_USE_BOTH), wait(parallelFilter($input, $callback, \ARRAY_FILTER_USE_BOTH)));
    }

    public function testException() {
        $this->expectException(MultiReasonException::class);

        wait(parallelFilter([1, 2, 3], function () {
            throw new \Exception;
        }));
    }

    public function testExecutesAllTasksOnException() {
        $files = [
            [0, \tempnam(\sys_get_temp_dir(), 'amp-parallel-functions-')],
            [1, \tempnam(\sys_get_temp_dir(), 'amp-parallel-functions-')],
            [2, \tempnam(\sys_get_temp_dir(), 'amp-parallel-functions-')],
        ];

        try {
            wait(parallelFilter($files, function ($args) {
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
