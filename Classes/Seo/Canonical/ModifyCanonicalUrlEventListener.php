<?php
declare(strict_types=1);

namespace TRAW\HreflangPages\Seo\Canonical;

/*
 * This file is part of the "hreflang_pages" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TRAW\HreflangPages\Utility\RequestUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Seo\Event\ModifyUrlForCanonicalTagEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;


/**
 * Class ModifyCanonicalUrlEventListener
 */
#[AsEventListener(
    identifier: 'traw-hreflang-pages/modify-url-for-canonical-tag',
)]
final class ModifyCanonicalUrlEventListener
{
    /**
     * @param RequestUtility $requestUtility
     */
    public function __construct(
        private readonly RequestUtility $requestUtility
    )
    {
    }

    /**
     * @param ModifyUrlForCanonicalTagEvent $event
     */
    public function __invoke(ModifyUrlForCanonicalTagEvent $event): void
    {
        //set the canonical url to contain all request parameters
        //@extensionScannerIgnoreLine
        $event->setUrl($this->requestUtility->getRequestUri());
    }
}
