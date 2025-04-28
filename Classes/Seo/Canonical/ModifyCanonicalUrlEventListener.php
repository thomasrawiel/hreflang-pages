<?php
declare(strict_types=1);

namespace TRAW\HreflangPages\Seo\Canonical;

use TRAW\HreflangPages\Utility\RequestUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Seo\Event\ModifyUrlForCanonicalTagEvent;

/**
 * Class ModifyCanonicalUrlEventListener
 */
final class ModifyCanonicalUrlEventListener
{
    /**
     * @var RequestUtility|null
     */
    protected ?RequestUtility $requestUtility = null;

    public function __construct()
    {
        $this->requestUtility = GeneralUtility::makeInstance(RequestUtility::class);
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
