<?php
declare(strict_types=1);

namespace TRAW\HreflangPages\Form\Element;

/*
 * This file is part of the "hreflang_pages" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TRAW\HreflangPages\Utility\HreflangListUtility;
use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class HreflangList
 */
final class HreflangList extends AbstractFormElement
{
    /**
     * @inheritDoc
     */
    public function render(): array
    {
        $result = $this->initializeResultArray();
        $result['html'] = (GeneralUtility::makeInstance(HreflangListUtility::class, $this->data))
            ->generateHrefLangList();

        return $result;
    }
}
