<?php

namespace TRAW\HreflangPages\Seo\Hreflang;

/*
 * This file is part of the "hreflang_pages" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TRAW\HreflangPages\Utility\RelationUtility;
use TRAW\HreflangPages\Utility\RequestUtility;
use TRAW\HreflangPages\Utility\UrlUtility;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheGroupException;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\DataProcessing\LanguageMenuProcessor;
use TYPO3\CMS\Frontend\Event\ModifyHrefLangTagsEvent;
use TYPO3\CMS\Seo\HrefLang\HrefLangGenerator;

/**
 * Class HreflangPagesGenerator
 * @package TRAW\HreflangPages\Seo\Hreflang
 */
class HreflangPagesGenerator extends HrefLangGenerator
{
    /**
     * @var RelationUtility|null
     */
    protected ?RelationUtility $relationUtility = null;

    /**
     * @var RequestUtility|object|\Psr\Log\LoggerAwareInterface|(RequestUtility&\Psr\Log\LoggerAwareInterface)|(RequestUtility&\TYPO3\CMS\Core\SingletonInterface)|\TYPO3\CMS\Core\SingletonInterface|null
     */
    protected ?RequestUtility $requestUtility = null;

    /**
     * HreflangPagesGenerator constructor.
     *
     * @param ContentObjectRenderer $cObj
     * @param LanguageMenuProcessor $languageMenuProcessor
     */
    public function __construct(ContentObjectRenderer $cObj, LanguageMenuProcessor $languageMenuProcessor)
    {
        parent::__construct($cObj, $languageMenuProcessor);
        $this->relationUtility = GeneralUtility::makeInstance(RelationUtility::class);
        $this->requestUtility = GeneralUtility::makeInstance(RequestUtility::class);
    }

    /**
     * @param ModifyHrefLangTagsEvent $event
     *
     * @throws NoSuchCacheGroupException
     */
    public function __invoke(ModifyHrefLangTagsEvent $event): void
    {
        $hrefLangs = $event->getHrefLangs();
        if ((int)$this->getTypoScriptFrontendController()->page['no_index'] === 1) {
            return;
        }
        //remove the x-default, we will determine that later
        unset($hrefLangs['x-default']);

        $languages = $this->languageMenuProcessor->process($this->cObj, [], [], []);
        $pageId = (int)$this->getTypoScriptFrontendController()->id;

        $connectedPages = $this->getConnectedPagesHreflang($pageId);
        if (!empty($connectedPages)) {
            foreach ($connectedPages as $relationUid => $relationHreflang) {
                foreach ($relationHreflang as $hreflang => $url) {
                    if (!isset($hrefLangs[$hreflang])) {
                        $hrefLangs[$hreflang] = $url;
                    } else {
                        //don't render duplicates
                        //$hrefLangs[$hreflang . '_' . $relationUid] = $url;
                    }
                }
            }
            ksort($hrefLangs);
        }
        if (count($hrefLangs) > 1 && !isset($hrefLangs['x-default'])) {
            if (array_key_exists($languages['languagemenu'][0]['hreflang'], $hrefLangs)) {
                $hrefLangs['x-default'] = $hrefLangs[$languages['languagemenu'][0]['hreflang']];
            }
        }

        //if we have query params, we must add them to the hreflang urls, because they must be self-referencing
        if ($this->requestUtility->hasArguments()) {
            if(!empty($this->requestUtility->getArguments()['tx_solr'])) {
                foreach ($hrefLangs as $lang => $href) {
                    $hrefLangs[$lang] = $href . (parse_url($href, PHP_URL_QUERY) ? '&' : '?') . $this->requestUtility->getArgumentsAsQueryString();
                }
            }
        }


        $event->setHrefLangs($hrefLangs);
    }

    /**
     * @param $pageUid
     *
     * @return array
     * @throws NoSuchCacheGroupException|NoSuchCacheException
     */
    protected function getConnectedPagesHreflang($pageUid): array
    {
        $relationUids = $this->relationUtility->getCachedRelations($pageUid);
        $hreflangs = [];

        foreach ($relationUids as $relationUid) {
            $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($relationUid);
            /** @var SiteLanguage $language */
            foreach ($site->getLanguages() as $language) {
                $translation = $this->getTranslatedPageRecord($relationUid, $language->getLanguageId(), $site);
                if (empty($translation)) continue;
                $href = UrlUtility::getAbsoluteUrl($translation['slug'], $language);
                $hreflangs[$relationUid][$language->getHreflang()] = $href;

                if ($language->getLanguageId() === 0 && !isset($hreflangs['x-default']) && $translation['tx_hreflang_pages_xdefault']) {
                    $hreflangs[$relationUid]['x-default'] = $href;
                }
            }
        }


        return $hreflangs;
    }


}