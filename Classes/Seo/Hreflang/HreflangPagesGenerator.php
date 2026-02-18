<?php
declare(strict_types=1);

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
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheGroupException;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\DataProcessing\LanguageMenuProcessor;
use TYPO3\CMS\Frontend\Event\ModifyHrefLangTagsEvent;
use TYPO3\CMS\Seo\HrefLang\HrefLangGenerator;

/**
 * Class HreflangPagesGenerator
 */
#[AsEventListener(
    identifier: 'traw-hreflangpages/hreflangpagesGenerator',
    after: 'typo3-seo/hreflangGenerator'
)]
final class HreflangPagesGenerator extends HrefLangGenerator
{
    /**
     * HreflangPagesGenerator constructor.
     *
     * @param ContentObjectRenderer $cObj
     * @param LanguageMenuProcessor $languageMenuProcessor
     */
    public function __construct(
        ContentObjectRenderer            $cObj,
        LanguageMenuProcessor            $languageMenuProcessor,
        private readonly RelationUtility $relationUtility,
        private readonly RequestUtility  $requestUtility
    )
    {
        parent::__construct($cObj, $languageMenuProcessor);
    }

    /**
     * Adds or modifies hreflang tags for a page, taking into account related pages and query parameters.
     *
     * @param ModifyHrefLangTagsEvent $event The event containing the current hreflang state and page context.
     */
    public function __invoke(ModifyHrefLangTagsEvent $event): void
    {
        $hrefLangs = $event->getHrefLangs();
        $request = $event->getRequest();
        $pageInformation = $request->getAttribute('frontend.page.information');
        $pageRecord = $pageInformation->getPageRecord();
        $pageId = $pageInformation->getId();

        // Skip pages with no_index
        if ((int)($pageRecord['no_index'] ?? 0) === 1) {
            return;
        }

        // Remove x-default (will be determined later)
        unset($hrefLangs['x-default']);

        $languages = $this->languageMenuProcessor->process($this->cObj, [], [], []);
        $connectedPages = $this->getConnectedPagesHreflang($pageId);

        if (!empty($connectedPages)) {
            $hrefLangs = $this->mergeConnectedPageHreflangs($hrefLangs, $connectedPages);
        }

        $this->addXDefaultIfApplicable($hrefLangs, $languages);
        $this->appendQueryArgumentsIfNeeded($hrefLangs);

        $event->setHrefLangs($hrefLangs);
    }


    /**
     * Merges hreflang URLs from connected pages into the existing set, avoiding duplicates.
     *
     * @param array $hrefLangs       Existing hreflang tag URLs (language code => URL).
     * @param array $connectedPages  Connected pages' hreflang data, grouped by relation UID.
     *                               Format: [int $relationUid => [string $hreflang => string $url]]
     *
     * @return array Merged and sorted hreflang URLs.
     */
    protected function mergeConnectedPageHreflangs(array $hrefLangs, array $connectedPages): array
    {
        foreach ($connectedPages as $relationHreflang) {
            foreach ($relationHreflang as $hreflang => $url) {
                if (!isset($hrefLangs[$hreflang])) {
                    $hrefLangs[$hreflang] = $url;
                }
            }
        }
        ksort($hrefLangs);
        return $hrefLangs;
    }

    /**
     * Adds an `x-default` hreflang entry if more than one hreflang is present and a default language is known.
     *
     * @param array $hrefLangs Reference to the hreflang tag array to modify.
     * @param array $languages Language menu structure from LanguageMenuProcessor.
     */
    protected function addXDefaultIfApplicable(array &$hrefLangs, array $languages): void
    {
        $firstLang = $languages['languagemenu'][0]['hreflang'] ?? null;
        if (count($hrefLangs) > 1 && $firstLang && isset($hrefLangs[$firstLang])) {
            $hrefLangs['x-default'] = $hrefLangs[$firstLang];
        }
    }

    /**
     * Appends the current request's query string to all hreflang URLs if necessary (e.g. when Solr filters are active).
     *
     * @param array $hrefLangs Reference to the hreflang tag array to modify.
     */
    protected function appendQueryArgumentsIfNeeded(array &$hrefLangs): void
    {
        if (!$this->requestUtility->hasArguments()) {
            return;
        }

        $arguments = $this->requestUtility->getArguments();
        if (empty($arguments['tx_solr'] ?? [])) {
            return;
        }

        $queryString = $this->requestUtility->getArgumentsAsQueryString();
        foreach ($hrefLangs as $lang => $href) {
            $hrefLangs[$lang] = $href . (parse_url($href, PHP_URL_QUERY) ? '&' : '?') . $queryString;
        }
    }


    /**
     * Retrieves translated page URLs for all related pages across languages, suitable for hreflang output.
     *
     * @param int $pageUid The UID of the current page.
     *
     * @return array Hreflang mapping grouped by related page UID.
     *               Format: [int $relationUid => [string $hreflang => string $url]]
     * @throws \TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException
     * @throws \TYPO3\CMS\Core\Cache\Exception\NoSuchCacheGroupException
     */
    protected function getConnectedPagesHreflang(int $pageUid): array
    {
        $relationUids = $this->relationUtility->getCachedRelations($pageUid);
        $hreflangs = [];

        $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);

        foreach ($relationUids as $relationUid) {
            try {
                $site = $siteFinder->getSiteByPageId($relationUid);

                foreach ($site->getLanguages() as $language) {
                    // @extensionScannerIgnoreLine
                    $languageId = $language->getLanguageId();
                    $translation = $this->getTranslatedPageRecord($relationUid, $languageId, $site);

                    if (empty($translation)) {
                        continue;
                    }

                    $href = UrlUtility::getAbsoluteUrl($translation['slug'], $language);
                    $hreflangs[$relationUid][$language->getHreflang()] = $href;

                    if (
                        $languageId === 0 &&
                        !isset($hreflangs['x-default']) &&
                        ($translation['tx_hreflang_pages_xdefault'] ?? false)
                    ) {
                        $hreflangs[$relationUid]['x-default'] = $href;
                    }
                }
            } catch (SiteNotFoundException $exception) {
                $this->relationUtility->removeRelationsForNonExistentPage($relationUid);
                $this->relationUtility->resetRelationCache($pageUid, $relationUid);
                continue;
            }
        }

        return $hreflangs;
    }

}
