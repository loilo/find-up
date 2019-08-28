<?php namespace Loilo\FindUp;

use InvalidArgumentException;
use Loilo\NodePath\Path;
use Loilo\Traceback\Traceback;
use RuntimeException;

define('UP_STOP_SYMBOL', uniqid('up-', true));
define('UP_SKIP_SYMBOL', uniqid('up-', true));

class Up
{
    /**
     * @deprecated 1.1.0 Legacy stop marker, use Up::stop() instead
     */
    const STOP = UP_STOP_SYMBOL;

    /**
     * @deprecated 1.1.0 Legacy skip marker, use Up::skip() instead
     */
    const SKIP = UP_SKIP_SYMBOL;

    /**
     * Find a file from a directory upwards
     *
     * @param string|callable $matcher   Acceptance criteria for the file to find
     *                                   Can be a file name or a non-string callable that takes the
     *                                   file name of each encountered file and its according directory
     *                                   and returns a truthy value on search match
     * @param string|int      $directory The path to the starting directory of the search
     *                                   If omitted (or `null`), the starting directory will be the
     *                                   directory from which the `find()` method has been called
     * @return string|null The path to the found file, or `null` if no file was found
     */
    public static function find($matcher, ?string $directory = null): ?string
    {
        // Validate $matcher argument
        if (!is_string($matcher) && !is_callable($matcher)) {
            throw new InvalidArgumentException('Invalid matcher, expected file name or callback');
        }

        // Validate $directory argument
        if (is_null($directory)) {
            $directory = Traceback::dir();
        } elseif (is_string($directory)) {
            $directory = $directory;

            if (!is_dir($directory) || !is_readable($directory)) {
                throw new RuntimeException(sprintf(
                    'Starting directory "%s" does not exist or is not readable',
                    $directory
                ));
            }
        } else {
            throw new InvalidArgumentException('Invalid source, expected a directory path or null');
        }

        // Perform search
        return static::walk($matcher, $directory);
    }

    /**
     * Perform the upwards search
     *
     * @param string|callable $matcher   Acceptance criteria for the file to find
     *                                   Can be a file name or a non-string callable that takes the
     *                                   file name of each encountered file and its according directory
     *                                   and returns a truthy value on search match
     * @param string|int      $directory The path to the starting directory of the search
     * @return string|null The path to the found file, or `null` if no file was found
     */
    protected static function walk($matcher, string $directory): ?string
    {
        $matchingFile = null;

        $visitedDirectories = [];

        while (dirname($directory) !== $directory) {
            $visitedDirectories[] = $directory;

            $dirHandle = dir($directory);

            while (false !== ($file = $dirHandle->read())) {
                if ($file === '.' || $file === '..') {
                    continue;
                }

                if (is_string($matcher)) {
                    if ($file === $matcher) {
                        $matchingFile = $file;
                        $dirHandle->close();
                        break 2;
                    }
                } else {
                    $matcherResult = $matcher($file, $directory);

                    if ($matcherResult === static::SKIP) {
                        break;
                    } elseif ($matcherResult instanceof SkipMarker) {
                        if ($matcherResult->getLevels() > 1) {
                            $directory = dirname($directory, $matcherResult->getLevels() - 1);
                        }
                        break;
                    } elseif ($matcherResult === static::STOP) {
                        break 2;
                    } elseif ($matcherResult instanceof StopMarker) {
                        $matchingFile = $matcherResult->getResult();
                        break 2;
                    } elseif ($matcherResult instanceof JumpMarker) {
                        $jumpPath = $matcherResult->getPath();

                        // Check whether $jumpPath equals any visited directory
                        if (in_array($jumpPath, $visitedDirectories, true)) {
                            throw new RuntimeException(sprintf(
                                'Up::jump() may only receive paths that have not been visited yet ("%s" has already been)',
                                $jumpPath
                            ));
                        }

                        // Add an arbitrary part to the jump path
                        // because dirname() will be called below
                        $directory = Path::join($jumpPath, '_');
                    } elseif ($matcherResult) {
                        $matchingFile = $file;
                        break 2;
                    }
                }
            }

            $dirHandle->close();
            unset($dirHandle);

            $directory = dirname($directory);
        }

        // When break; was used, the handle might still be open
        if (isset($dirHandle) && is_resource($dirHandle)) {
            $dirHandle->close();
        }

        if (!is_null($matchingFile)) {
            return Path::resolve($directory, $matchingFile);
        } else {
            return null;
        }
    }

    public static function jump(string $path)
    {
        return new JumpMarker($path);
    }

    public static function skip(int $levels = 1)
    {
        return new SkipMarker($levels);
    }

    public static function stop(?string $result = null)
    {
        return new StopMarker($result);
    }
}
