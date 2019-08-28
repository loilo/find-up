<?php
declare(strict_types=1);

namespace Loilo\FindUp\Test;

use Loilo\FindUp\Up;
use Loilo\NodePath\Path;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class UpTest extends TestCase
{
    protected function resolve(...$parts)
    {
        return Path::join(__DIR__, ...$parts);
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

    public function testPerformsLegacySkip(): void
    {
        $this->assertEquals(
            $this->resolve('../parent-2fd7a53ad64de.txt'),
            Up::find(function ($file, $directory) {
                if ($directory === __DIR__) {
                    return Up::SKIP;
                }

                return $file === 'self-a0da2db33b5a2.txt' || $file === 'parent-2fd7a53ad64de.txt';
            }, $this->resolve('.')),
            'Unexpectedly found self-a0da2db33b5a2.txt where it should have skipped the self directory'
        );
    }

    public function testPerformsSkip(): void
    {
        $this->assertEquals(
            $this->resolve('../parent-2fd7a53ad64de.txt'),
            Up::find(function ($file, $directory) {
                if ($directory === __DIR__) {
                    return Up::skip();
                }

                return $file === 'self-a0da2db33b5a2.txt' || $file === 'parent-2fd7a53ad64de.txt';
            }, $this->resolve('.')),
            'Unexpectedly found self-a0da2db33b5a2.txt where it should have skipped the self directory'
        );
    }

    public function testPerformsSkipWithLevels(): void
    {
        $this->assertEquals(
            $this->resolve('../../grandparent-5d2da8c50a6b4.txt'),
            Up::find(function ($file, $directory) {
                if ($directory === __DIR__) {
                    return Up::skip(2);
                }

                return $file === 'self-a0da2db33b5a2.txt' || $file === 'parent-2fd7a53ad64de.txt' || $file === 'grandparent-5d2da8c50a6b4.txt';
            }, $this->resolve('.')),
            'Unexpectedly found self-a0da2db33b5a2.txt or parent-2fd7a53ad64de.txt where it should have skipped the self and parent directories'
        );
    }

    public function testFailsSkipWithInvalidLevels(): void
    {
        $this->expectException(RuntimeException::class);

        Up::find(function () {
            return Up::skip(0);
        }, $this->resolve('.'));
    }

    public function testPerformsLegacyStop(): void
    {
        $grandParentDir = $this->resolve('../..');

        $this->assertEquals(
            $this->resolve('../parent-2fd7a53ad64de.txt'),
            Up::find(function ($file, $directory) use ($grandParentDir) {
                if ($directory === $grandParentDir) {
                    return Up::STOP;
                }

                return $file === 'parent-2fd7a53ad64de.txt';
            }, $this->resolve('..')),
            'Did not find parent-2fd7a53ad64de.txt although the stop should have only happened after that'
        );

        $this->assertEquals(
            null,
            Up::find(function ($file, $directory) use ($grandParentDir) {
                if ($directory === $grandParentDir) {
                    return Up::STOP;
                }

                return $file === 'grandparent-5d2da8c50a6b4.txt';
            }, $this->resolve('..')),
            'Unexpectedly found grandparent-5d2da8c50a6b4.txt where search should have been stopped before'
        );
    }

    public function testPerformsStop(): void
    {
        $grandParentDir = $this->resolve('../..');

        $this->assertEquals(
            $this->resolve('../parent-2fd7a53ad64de.txt'),
            Up::find(function ($file, $directory) use ($grandParentDir) {
                if ($directory === $grandParentDir) {
                    return Up::stop();
                }

                return $file === 'parent-2fd7a53ad64de.txt';
            }, $this->resolve('..')),
            'Did not find parent-2fd7a53ad64de.txt although the stop should have only happened after that'
        );

        $this->assertEquals(
            null,
            Up::find(function ($file, $directory) use ($grandParentDir) {
                if ($directory === $grandParentDir) {
                    return Up::stop();
                }

                return $file === 'grandparent-5d2da8c50a6b4.txt';
            }, $this->resolve('..')),
            'Unexpectedly found grandparent-5d2da8c50a6b4.txt where search should have been stopped before'
        );
    }

    public function testPerformsStopWithRelativeCustomResult(): void
    {
        $parentDir = $this->resolve('..');

        $this->assertEquals(
            $this->resolve('../stop.txt'),
            Up::find(function ($file, $directory) use ($parentDir) {
                if ($directory === $parentDir) {
                    return Up::stop('stop.txt');
                }

                return $file === 'parent-2fd7a53ad64de.txt';
            }, $this->resolve('.')),
            'Unexpectedly found parent-2fd7a53ad64de.txt although the stop should have provided a stop.txt'
        );
    }

    public function testPerformsStopWithAbsoluteCustomResult(): void
    {
        $parentDir = $this->resolve('..');
        $stopFile = $this->resolve('stop.txt');

        $this->assertEquals(
            $stopFile,
            Up::find(function ($file, $directory) use ($stopFile, $parentDir) {
                if ($directory === $parentDir) {
                    return Up::stop($stopFile);
                }

                return $file === 'parent-2fd7a53ad64de.txt';
            }, $this->resolve('.')),
            'Unexpectedly found parent-2fd7a53ad64de.txt although the stop should have provided a stop.txt'
        );
    }

    public function testPerformsJump(): void
    {
        $parentDir = $this->resolve('..');
        $otherDir = $this->resolve('../baz');

        $this->assertEquals(
            $this->resolve('../baz/other-1b1b88d1080de.txt'),
            Up::find(function ($file, $directory) use ($parentDir, $otherDir) {
                if ($directory === $parentDir) {
                    return Up::jump($otherDir);
                }

                return $file === 'other-1b1b88d1080de.txt';
            }, $this->resolve('.')),
            'Did not find other-1b1b88d1080de.txt although the jump should have gone to its parent directory'
        );
    }

    public function testFailsJumpToVisited(): void
    {
        $this->expectException(RuntimeException::class);

        $selfDir = $this->resolve('.');
        $parentDir = $this->resolve('..');

        Up::find(function ($file, $directory) use ($selfDir, $parentDir) {
            if ($directory === $parentDir) {
                return Up::jump($selfDir);
            }

            return $file === 'parent-2fd7a53ad64de.txt';
        }, $selfDir);
    }
}
