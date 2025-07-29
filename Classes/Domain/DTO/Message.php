<?php
declare(strict_types=1);

namespace TRAW\HreflangPages\Domain\DTO;

/*
 * This file is part of the "hreflang_pages" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Class Message
 */
final readonly class Message
{
    /**
     * @param string $type
     * @param string $text
     */
    public function __construct(
        private string $type,
        private string $text
    )
    {
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getText(): string
    {
        return $this->text;
    }
}