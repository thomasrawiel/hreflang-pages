<?php
declare(strict_types=1);

namespace TRAW\HreflangPages\Hooks;

/*
 * This file is part of the "hreflang_pages" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TRAW\HreflangPages\Event\PageRelationEvent;
use TRAW\HreflangPages\Utility\RelationUtility;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheGroupException;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class TCEmainHook
 */
final class TCEmainHook
{
    /**
     * @var RelationUtility
     */
    protected $relationUtility;

    /**
     * @var mixed|object|\Psr\Log\LoggerAwareInterface|\TYPO3\CMS\Core\SingletonInterface|null
     */
    protected $eventDispatcher;

    public function __construct()
    {
        $this->relationUtility = GeneralUtility::makeInstance(RelationUtility::class);
        $this->eventDispatcher = GeneralUtility::makeInstance(EventDispatcher::class);
    }

    /**
     * @param             $table
     * @param             $id
     * @param             $recordToDelete
     * @param null        $recordWasDeleted
     * @param DataHandler $pObj
     *
     * @throws NoSuchCacheGroupException
     * @throws NoSuchCacheException
     */
    public function processCmdmap_deleteAction($table, $id, $recordToDelete, &$recordWasDeleted, DataHandler &$pObj)
    {
        if ($table === 'pages') {
            $this->relationUtility->removeRelations($recordToDelete['uid']);
        }
    }

    /**
     * @param DataHandler $pObj
     *
     * @throws NoSuchCacheGroupException
     */
    public function processDatamap_afterAllOperations(DataHandler &$pObj)
    {
        if (isset($pObj->datamap['pages'])) {
            foreach ($pObj->datamap['pages'] as $uid => $page) {
                if ((int)$uid > 0) {
                    $relations = $this->relationUtility->getCachedRelations($uid);
                    $relations[] = $uid;
                    foreach ($relations as $relationUid) {
                        $this->relationUtility->flushRelationCacheForPage($relationUid);
                    }

                    $this->eventDispatcher->dispatch(new PageRelationEvent($uid, $relations));
                }
            }
        }
    }
}
