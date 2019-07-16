<?php
declare(strict_types=1);

namespace Loilo\FindUp\Test;

use Loilo\FindUp\Up;
use PHPUnit\Framework\TestCase;
use Webmozart\PathUtil\Path;

final class UpTest extends TestCase
{
    protected function resolve(...$parts)
    {
        return Path::canonicalize(Path::join(__DIR__, ...$parts));
    }

    public function testFindsNameInSameDirectory(): void
    {
        $this->assertEquals(
            $this->resolve('self-a0da2db33b5a2.txt'),
            Up::find('self-a0da2db33b5a2.txt'),
            'Did not find self-a0da2db33b5a2.txt in same directory as test class'
        );
    }

    public function testFindsNameInAncestorDirectories(): void
    {
        $this->assertEquals(
            $this->resolve('../parent-2fd7a53ad64de.txt'),
            Up::find('parent-2fd7a53ad64de.txt'),
            'Did not find parent-2fd7a53ad64de.txt in parent directory'
        );

        $this->assertEquals(
            $this->resolve('../../grandparent-5d2da8c50a6b4.txt'),
            Up::find('grandparent-5d2da8c50a6b4.txt'),
            'Did not find grandparent-5d2da8c50a6b4.txt in grandparent directory'
        );
    }

    public function testFindsCallableInSameDirectory(): void
    {
        $this->assertEquals(
            $this->resolve('self-a0da2db33b5a2.txt'),
            Up::find(function ($file) {
                return $file === 'self-a0da2db33b5a2.txt';
            }),
            'Did not find self-a0da2db33b5a2.txt in same directory as test class'
        );
    }

    public function testFindsCallableInAncestorDirectories(): void
    {
        $this->assertEquals(
            $this->resolve('../parent-2fd7a53ad64de.txt'),
            Up::find(function ($file) {
                return $file === 'parent-2fd7a53ad64de.txt';
            }),
            'Did not find parent-2fd7a53ad64de.txt in parent directory'
        );

        $this->assertEquals(
            $this->resolve('../../grandparent-5d2da8c50a6b4.txt'),
            Up::find(function ($file) {
                return $file === 'grandparent-5d2da8c50a6b4.txt';
            }),
            'Did not find grandparent-5d2da8c50a6b4.txt in grandparent directory'
        );
    }

    public function testRespectsStartingDirectory(): void
    {
        $this->assertEquals(
            null,
            Up::find('self-a0da2db33b5a2.txt', $this->resolve('..')),
            'Unexpectedly found self-a0da2db33b5a2.txt where it should have started in parent directory'
        );
    }

    public function testPerformsSkip(): void
    {
        $this->assertEquals(
            $this->resolve('../parent-2fd7a53ad64de.txt'),
            Up::find(function ($file, $directory) {
                if ($directory === __DIR__) {
                    return Up::SKIP;
                }

                return $file === 'self-a0da2db33b5a2.txt' || $file === 'parent-2fd7a53ad64de.txt';
            }, $this->resolve('..')),
            'Unexpectedly found self-a0da2db33b5a2.txt where it should have skipped the self directory'
        );
    }

    public function testPerformsStop(): void
    {
        $parentDir = $this->resolve('../..');

        $this->assertEquals(
            $this->resolve('../parent-2fd7a53ad64de.txt'),
            Up::find(function ($file, $directory) use ($parentDir) {
                if ($directory === $parentDir) {
                    return Up::STOP;
                }

                return $file === 'parent-2fd7a53ad64de.txt';
            }, $this->resolve('..')),
            'Did not find parent-2fd7a53ad64de.txt although the stop should have only happened after that'
        );

        $this->assertEquals(
            null,
            Up::find(function ($file, $directory) use ($parentDir) {
                if ($directory === $parentDir) {
                    return Up::STOP;
                }

                return $file === 'grandparent-5d2da8c50a6b4.txt';
            }, $this->resolve('..')),
            'Unexpectedly found grandparent-5d2da8c50a6b4.txt where search should have been stopped before'
        );
    }
}
