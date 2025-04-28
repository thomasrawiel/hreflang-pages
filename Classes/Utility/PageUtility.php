<?php
declare(strict_types=1);

namespace TRAW\HreflangPages\Utility;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspectFactory;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class PageUtility
 */
final class PageUtility
{
    /**
     * @param int                $pageId
     * @param int                $languageId
     *
     * @param SiteInterface|null $site
     *
     * @return mixed
     * @throws \TYPO3\CMS\Core\Exception\SiteNotFoundException
     */
    public static function getPageTranslationRecord(int $pageId, int $languageId, ?SiteInterface $site = null): ?array
    {
        if (is_null($site)) {
            $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($pageId);
        }
        $targetSiteLanguage = $site->getLanguageById($languageId);
        $languageAspect = LanguageAspectFactory::createFromSiteLanguage($targetSiteLanguage);

        /** @var Context $context */
        $context = clone GeneralUtility::makeInstance(Context::class);
        $context->setAspect('language', $languageAspect);

        $pageRepository = GeneralUtility::makeInstance(PageRepository::class, $context);
        if ($languageId > 0) {
            return $pageRepository->getPageOverlay($pageId, $languageId);
        }
        return $pageRepository->getPage($pageId);
    }
}
