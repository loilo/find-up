<?php namespace Loilo\FindUp;

use RuntimeException;

/**
 * A marker that indicates the user wants to skip one or more directory levels
 */
class SkipMarker
{
    /**
     * @var int
     */
    private $levels;

    public function __construct(int $levels)
    {
        if ($levels < 1) {
            throw new RuntimeException(sprintf(
                'Up::skip() only accepts a positive, non-zero level number, got %s',
                $levels
            ));
        }

        $this->levels = $levels;
    }

    /**
     * Get the skip marker's levels
     */
    public function getLevels(): int
    {
        return $this->levels;
    }
}
