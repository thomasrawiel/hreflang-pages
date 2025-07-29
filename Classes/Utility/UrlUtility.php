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

use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;


/**
 * Class UrlUtility
 */
final class UrlUtility
{

    /**
     * Converts a relative URL into an absolute URL using the site's base URL.
     * If already absolute, it returns the original URL.
     *
     * @param string       $url          Relative or absolute URL
     * @param SiteLanguage $siteLanguage The language context providing the base URL
     *
     * @return string Fully qualified absolute URL
     */
    public static function getAbsoluteUrl(string $url, SiteLanguage $siteLanguage): string
    {
        $uri = new Uri($url);

        if ($uri->getHost()) {
            // Already absolute
            return (string)$uri;
        }

        $baseUri = $siteLanguage->getBase();

        // Normalize path (ensure no double slashes)
        $basePath = rtrim($baseUri->getPath(), '/');
        $urlPath = ltrim($uri->getPath(), '/');
        $combinedPath = $basePath . '/' . $urlPath;

        $absoluteUri = $baseUri->withPath($combinedPath);

        if ($uri->getQuery()) {
            $absoluteUri = $absoluteUri->withQuery($uri->getQuery());
        }

        return (string)$absoluteUri;
    }
}
