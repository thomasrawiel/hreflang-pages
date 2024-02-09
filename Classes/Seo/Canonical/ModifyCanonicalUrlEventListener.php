<?php

namespace TRAW\HreflangPages\Seo\Canonical;


use TRAW\HreflangPages\Utility\RequestUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Seo\Event\ModifyUrlForCanonicalTagEvent;

/**
 * Class ModifyCanonicalUrlEventListener
 * @package TRAW\HreflangPages\Seo\Canonical
 */
class ModifyCanonicalUrlEventListener {

    /**
     * @var RequestUtility|object|\Psr\Log\LoggerAwareInterface|(RequestUtility&\Psr\Log\LoggerAwareInterface)|(RequestUtility&\TYPO3\CMS\Core\SingletonInterface)|\TYPO3\CMS\Core\SingletonInterface|null
     */
    protected ?RequestUtility $requestUtility = null;

    /**
     *
     */
    public function __construct()
    {
        $this->requestUtility = GeneralUtility::makeInstance(RequestUtility::class);
    }

    /**
     * @param ModifyUrlForCanonicalTagEvent $event
     *
     * @return void
     */
    public function __invoke(ModifyUrlForCanonicalTagEvent $event): void
    {
        //set the canonical url to contain all request parameters
        $event->setUrl($this->requestUtility->getRequestUri());
    }
}