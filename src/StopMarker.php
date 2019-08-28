<?php namespace Loilo\FindUp;

/**
 * A marker that indicates the user wants to stop the lookup
 */
class StopMarker
{
    /**
     * @var string|null
     */
    private $result;

    public function __construct(?string $result = null)
    {
        $this->result = $result;
    }

    /**
     * Get the jump marker's path
     */
    public function getResult(): ?string
    {
        return $this->result;
    }
}
