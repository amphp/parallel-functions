<?php declare(strict_types=1);

namespace Amp\ParallelFunctions\Test;

use Amp\CompositeException;
use Amp\PHPUnit\AsyncTestCase;
use function Amp\ParallelFunctions\parallelMap;

class MapTest extends AsyncTestCase
{
    public function testValidInput()
    {
        $r = parallelMap([1, 2, 3], function ($input) {
            return $input + 2;
        });
        ksort($r);
        $this->assertSame([3, 4, 5], $r);
    }

    public function testException()
    {
        $this->expectException(CompositeException::class);

        parallelMap([1, 2, 3], function () {
            throw new \Exception;
        });
    }

    public function testExecutesAllTasksOnException()
    {
        $files = [
            [0, \tempnam(\sys_get_temp_dir(), 'amp-parallel-functions-')],
            [1, \tempnam(\sys_get_temp_dir(), 'amp-parallel-functions-')],
            [2, \tempnam(\sys_get_temp_dir(), 'amp-parallel-functions-')],
        ];

        try {
            parallelMap($files, function ($args) {
                list($id, $filename) = $args;

                if ($id === 0) {
                    throw new \Exception;
                }

                \sleep(1);
                \file_put_contents($filename, $id);
            });

            $this->fail('No exception thrown.');
        } catch (CompositeException $e) {
            $this->assertStringEqualsFile($files[1][1], '1');
            $this->assertStringEqualsFile($files[2][1], '2');
        }
    }
}
