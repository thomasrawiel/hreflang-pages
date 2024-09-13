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
use stdClass;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Class HreflangListUtility
 * @package TRAW\HreflangPages\Utility
 */
class HreflangListUtility
{
    /**
     *
     */
    protected const lll = "LLL:EXT:hreflang_pages/Resources/Private/Language/locallang_tca.xlf:";
    /**
     * @var array
     */
    protected $databaseRow;
    /**
     * @var array
     */
    protected $pageLanguageOverlayRows;
    /**
     * @var Site
     */
    protected $site;

    /**
     * @var array
     */
    protected $messages = [];

    /**
     * HreflangListUtility constructor.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->databaseRow = $data['databaseRow'];
        $this->site = $data['site'];
        $this->pageLanguageOverlayRows = $data['pageLanguageOverlayRows'];
    }

    /**
     * @return string
     */
    public function generateHrefLangList(): string
    {
        if ($this->databaseRow['no_index'] === 1) {
            return $this->generateHtml(LocalizationUtility::translate(self::lll . 'no-index-no-preview'));
        }

        if (isset($this->site)) {
            $content = "<div class='row'>"
                . "<div class='col-md-6'>" . $this->getHreflangPreview() . "</div>"
                . "<div class='col-md-6'>" . $this->getLanguagePreview() . "</div>"
                . "</div>";

        } else {
            $content = LocalizationUtility::translate(self::lll . 'siteconfig-no-preview');
        }

        return $this->generateHtml($content);
    }

    /**
     * @param int                         $pageId
     * @param int                         $languageId
     * @param ServerRequestInterface|null $request
     *
     * @return array
     * @throws SiteNotFoundException
     */
    protected function getTranslatedPageRecord(int $pageId, int $languageId): array
    {
        return PageUtility::getPageTranslationRecord($pageId, $languageId);
    }

    /**
     * @return string
     */
    protected function getHreflangPreview(): string
    {
        $content = "<strong class='headline'>" . LocalizationUtility::translate(self::lll . 'hreflang.headline') . "</strong>";

        $hrefLangs = [];
        if (empty($this->databaseRow['canonical_link'])) {
            foreach ($this->site->getLanguages() as $language) {
                if ($language === $this->site->getDefaultLanguage()) {
                    $hrefLangs[$language->getHreflang()] = UrlUtility::getAbsoluteUrl($this->databaseRow['slug'], $language);
                } else {
                    // @extensionScannerIgnoreLine
                    $translation = $this->getPageTranslatedInLanguage($language->getLanguageId());
                    if (!is_null($translation)) {
                        $hrefLangs[$language->getHreflang()] = UrlUtility::getAbsoluteUrl($translation['slug'], $language);
                    }
                }
            }

            $connectedHreflangs = $this->getConnectedHreflangs();
            if (!empty($connectedHreflangs)) {
                foreach ($connectedHreflangs as $relationUid => $relationHreflang) {
                    foreach ($relationHreflang as $hreflang => $url) {
                        if (!isset($hrefLangs[$hreflang])) {
                            $hrefLangs[$hreflang] = $url;
                        } else {
                            //$hrefLangs[$hreflang . '_' . $relationUid] = $url;
                            $this->addMsg('warning-same-language', 'warning', [0 => $hreflang . '_' . $relationUid]);
                        }
                    }
                }
            }

            if (count($hrefLangs) > 1 && !isset($hrefLangs['x-default'])) {
                $hrefLangs['x-default'] = $hrefLangs[$this->site->getDefaultLanguage()->getHreflang()];
            }
            ksort($hrefLangs);
        } else {
            $this->addMsg('canonical-no-preview');
        }

        if (count($hrefLangs) > 1) {
            $content .= '<ul class="hreflangs">';
            foreach ($hrefLangs as $hreflang => $url) {
                $content .= "<li><strong>$hreflang</strong> ($url)</li>";
            }
            $content .= '</ul>';
        } else {
            $this->addMsg('translation-missing-no-preview');
        }

        if (!empty($this->messages)) {
            $content .= "<strong>Note:</strong><ul class='warnings'>";
            foreach ($this->messages as $message) {
                $content .= "<li class='" . $message->messageType . "'>"
                    . $message->text
                    . "</li>";
            }
            $content .= "</ul>";
        }

        return $content;
    }

    /**
     * @return array
     */
    protected function getConnectedHreflangs()
    {

        $hreflangs = [];

        $relationUtility = GeneralUtility::makeInstance(RelationUtility::class);
        //check if uid is integer, in some cases it is 'NEW123456'
        $relationUids = MathUtility::canBeInterpretedAsInteger($this->databaseRow['uid'])
            ? $relationUtility->getCachedRelations($this->databaseRow['uid'])
            : [];

        foreach ($relationUids as $relationUid) {
            if ($relationUid === $this->databaseRow['uid']) continue;
            $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($relationUid);
            /** @var SiteLanguage $language */
            foreach ($site->getLanguages() as $language) {
                // @extensionScannerIgnoreLine
                $translation = $this->getTranslatedPageRecord($relationUid, $language->getLanguageId());
                if (empty($translation)) continue;

                $href = UrlUtility::getAbsoluteUrl($translation['slug'], $language);
                $hreflangs[$relationUid][$language->getHreflang()] = $href;
                // @extensionScannerIgnoreLine
                if ($language->getLanguageId() === 0 && !isset($hreflangs[$relationUid]['x-default']) && $translation['tx_hreflang_pages_xdefault']) {
                    $hreflangs[$relationUid]['x-default'] = $href;

                    if ($this->databaseRow['tx_hreflang_pages_xdefault']) {
                        $this->addMsg('x-default-conflict', 'warning', [0 => $translation['uid']]);
                    }
                }
            }
        }
        return $hreflangs;
    }

    /**
     * @param string $text
     * @param string $type
     * @param array  $additionalData
     */
    protected function addMsg(string $text, string $type = 'info', $additionalData = [])
    {
        $message = new stdClass();
        $message->messageType = $type;

        $messageText = LocalizationUtility::translate(self::lll . $text, null, $additionalData);
        $message->text = $messageText ?? $text;

        $this->messages[] = $message;
    }

    /**
     * @return string
     */
    protected function getLanguagePreview(): string
    {
        $content = "<strong class='headline'>" . LocalizationUtility::translate(self::lll . 'languages.headline') . "</strong>"
            . "<table class='languages'>
                   <thead><tr><th>Title/ Navtitle</th><th>Hreflang</th><th>Translated</th></tr></thead>";

        $content .= "<tbody>";
        foreach ($this->site->getLanguages() as $language) {
            $title = $language->getTitle() . "/" . $language->getNavigationTitle();
            $hreflang = $language->getHreflang();

            // @extensionScannerIgnoreLine
            $languageId = $language->getLanguageId();
            $isAvailable = call_user_func(function ($languageId) {
                return $languageId > 0 && !is_null($this->getPageTranslatedInLanguage($languageId));
                // @extensionScannerIgnoreLine
            }, $languageId) ? 'YES' : ($language === $this->site->getDefaultLanguage() ? 'is default' : 'NO');

            $content .= <<<HTML
                <tr>
                    <td>{$title}</td>
                    <td>{$hreflang}</td>
                    <td>{$isAvailable}</td>
                </tr>
                HTML;
        }

        $content .= "</tbody></table>";

        return $content;

    }

    /**
     * @param $languageId
     *
     * @return mixed|null
     */
    protected function getPageTranslatedInLanguage($languageId)
    {
        if (empty($this->pageLanguageOverlayRows)) return null;

        foreach ($this->pageLanguageOverlayRows as $overlay) {
            if ($languageId === $overlay['sys_language_uid']
                && $overlay['hidden'] === 0 && $overlay['deleted'] === 0) {
                return $overlay;
            }
        }
        return null;
    }

    /**
     * @return array
     */
    protected function getPageTranslationLanguages()
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $result = $queryBuilder->select('sys_language_uid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->and(
                    $queryBuilder->expr()->or(
                        $queryBuilder->expr()->eq('uid', $this->databaseRow['uid']),
                        $queryBuilder->expr()->eq('l10n_parent', $this->databaseRow['uid'])
                    ),
                    $queryBuilder->expr()->eq('hidden', 0),
                    $queryBuilder->expr()->eq('deleted', 0)
                )
            )->execute()
            ->fetchAllAssociative();

        $translations = [];
        foreach ($result as $translation) {
            array_push($translations, $translation['sys_language_uid']);
        }

        return $translations;
    }

    /**
     * @param string $content
     *
     * @return string
     */
    protected function generateHtml(string $content): string
    {
        return "<section class='tx-hreflang-list'>"
            . $content
            . "</section >";
    }
}