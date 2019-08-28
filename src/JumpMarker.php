<?php namespace Loilo\FindUp;

use RuntimeException;
use Loilo\NodePath\Path;

/**
 * A marker that indicates the user wants to jump to another directory
 */
class JumpMarker
{
    /**
     * @var string
     */
    private $path;

    public function __construct(string $path)
    {
        if (!Path::isAbsolute($path)) {
            throw new RuntimeException(sprintf(
                'Up::jump() only accepts absolute paths, "%s" given',
                $path
            ));
        }

        if (!is_dir($path)) {
            throw new RuntimeException(sprintf(
                'Up::jump() path "%s" is not a directory',
                $path
            ));
        }

        $this->path = $path;
    }

    /**
     * Get the jump marker's path
     */
    public function getPath(): string
    {
        return $this->path;
    }
}
