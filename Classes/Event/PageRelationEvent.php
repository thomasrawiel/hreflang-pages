<?php
declare(strict_types=1);

namespace TRAW\HreflangPages\Event;

/**
 * Class PageRelationEvent
 */
final class PageRelationEvent
{
    protected $source = 0;
    protected $targets = [];

    public function __construct($source, $targets)
    {
        $this->source = $source;
        $this->targets = $targets;
    }

    /**
     * @return int
     */
    public function getSource(): int
    {
        return $this->source;
    }

    /**
     * @return array
     */
    public function getTargets(): array
    {
        return $this->targets;
    }
}
