<?php

namespace TRAW\HreflangPages\Utility;

/*
 * This file is part of the "hreflang_pages" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Psr\Http\Message\ServerRequestInterface;

/**
 * Class RequestUtility
 * @package TRAW\HreflangPages\Utility
 */
class RequestUtility
{
    protected ?ServerRequestInterface $request = null;

    protected array $arguments = [];

    public function __construct() {
        $this->request = $this->getRequest();

        if (!empty($this->request)) {
            $this->arguments = !empty($this->request->getAttributes()['routing']) ? $this->request->getAttributes()['routing']->getArguments() : [];
        }
    }

    public function getRequestUri(): string
    {
        return $this->request->getUri();
    }

    public function hasArguments(): bool
    {
        return !empty($this->arguments);
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function getArgumentsAsQueryString(): string
    {
        return http_build_query($this->arguments);
    }

    /**
     * @return ServerRequestInterface|null
     */
    protected function getRequest(): ?ServerRequestInterface
    {
        return $GLOBALS['TYPO3_REQUEST'] ?? null;
    }

}