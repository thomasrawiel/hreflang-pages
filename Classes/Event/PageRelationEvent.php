<?php
declare(strict_types=1);

namespace TRAW\HreflangPages\Event;

/*
 * This file is part of the "hreflang_pages" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Class PageRelationEvent
 */
final readonly class PageRelationEvent
{

    /**
     * @param $source
     * @param $targets
     */
    public function __construct(private int $source, private array $targets)
    {
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
