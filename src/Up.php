<?php namespace Loilo\FindUp;

use RuntimeException;
use InvalidArgumentException;

define('UP_STOP_SYMBOL', uniqid('up-', true));
define('UP_SKIP_SYMBOL', uniqid('up-', true));

class Up
{
    const STOP = UP_STOP_SYMBOL;
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
            $directory = static::getCallingDir();
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
     * Get the directory of the file from which the find() method was called
     *
     * @return string
     *
     * @throws RuntimeException When no calling file can be found from the trace or
     *                          the found file does not exist (e.g. in a CLI context)
     */
    protected static function getCallingDir(): string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        foreach ($trace as $traceItem) {
            if ($traceItem['file'] !== __FILE__) {
                $callingFile = $traceItem['file'];
                break;
            }
        }

        if (!isset($callingFile)) {
            throw new RuntimeException('Starting directory cannot be read from the call stack');
        }

        if (!is_file($callingFile)) {
            throw new RuntimeException(sprintf(
                'Calling file "%s" (determined from call stack) not found',
                $callingFile
            ));
        }

        return dirname($callingFile);
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

        while (dirname($directory) !== $directory) {
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
                    } elseif ($matcherResult === static::STOP) {
                        $dirHandle->close();
                        break 2;
                    } elseif ($matcherResult) {
                        $matchingFile = $file;
                        $dirHandle->close();
                        break 2;
                    }
                }
            }

            $dirHandle->close();
            unset($dirHandle);

            $directory = dirname($directory);
        }

        if (!is_null($matchingFile)) {
            if (strpos($directory, '/') === false && preg_match('/^[a-z]:\\\\/i', $directory)) {
                return $directory . '\\' . $matchingFile;
            } else {
                return $directory . '/' . $matchingFile;
            }
        } else {
            return null;
        }
    }
}
