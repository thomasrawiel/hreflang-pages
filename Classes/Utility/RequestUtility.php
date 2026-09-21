<?php
declare(strict_types=1);

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
 */
final class RequestUtility
{
    protected ?ServerRequestInterface $request;

    protected array $arguments = [];

    /**
     *
     */
    public function __construct()
    {
        $this->request = $this->getRequest();

        if ($this->request instanceof \Psr\Http\Message\ServerRequestInterface) {
            $this->arguments = !empty($this->request->getAttributes()['routing']) ? $this->request->getAttributes()['routing']->getArguments() : [];
        }
    }

    public function getRequestUri(): string
    {
        return $this->request->getUri()->__toString();
    }

    public function hasArguments(): bool
    {
        return $this->arguments !== [];
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * @return string
     */
    public function getArgumentsAsQueryString(?string $argument = null): string
    {
        if ($argument === null) {
            return http_build_query($this->arguments);
        }

        if (array_key_exists($argument, $this->arguments)) {
            return http_build_query([$argument => $this->arguments[$argument]]);
        }
        return '';
    }

    /**
     * @return ServerRequestInterface|null
     */
    protected function getRequest(): ?ServerRequestInterface
    {
        return $GLOBALS['TYPO3_REQUEST'] ?? null;
    }
}
