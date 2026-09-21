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

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TRAW\HreflangPages\Event\PageRelationEvent;
use TRAW\HreflangPages\Utility\RelationUtility;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheGroupException;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;

#[Autoconfigure(public:true)]
final readonly class TCEmainHook
{
    public function __construct(private RelationUtility $relationUtility, private EventDispatcher $eventDispatcher)
    {
    }

    /**
     * @throws NoSuchCacheException
     * @throws NoSuchCacheGroupException
     */
    public function processCmdmap_deleteAction($table, $id, array $recordToDelete, &$recordWasDeleted, DataHandler &$pObj): void
    {
        if ($table === 'pages') {
            $this->relationUtility->removeRelations($recordToDelete['uid']);
        }
    }

    /**
     * @throws NoSuchCacheGroupException
     */
    public function processDatamap_afterAllOperations(DataHandler &$pObj): void
    {
        if (isset($pObj->datamap['pages'])) {
            foreach ($pObj->datamap['pages'] as $uid => $page) {
                $uid = (int)$uid;
                if ($uid > 0) {
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
