<?php

declare(strict_types=1);

namespace TRAW\HreflangPages\Utility;

/*
 * This file is part of the "hreflang_pages" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheGroupException;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class RelationUtility
 */
final class RelationUtility
{
    /**
     * @var CacheManager|mixed|object|\Psr\Log\LoggerAwareInterface|\TYPO3\CMS\Core\SingletonInterface|null
     */
    protected CacheManager $cacheManager;
    /**
     * @var ConnectionPool|mixed|object|\Psr\Log\LoggerAwareInterface|\TYPO3\CMS\Core\SingletonInterface|null
     */
    protected ConnectionPool $connectionPool;

    /**
     * @param ConnectionPool|null $connectionPool
     * @param CacheManager|null   $cacheManager
     */
    public function __construct(ConnectionPool $connectionPool = null, CacheManager $cacheManager = null)
    {
        $this->connectionPool = $connectionPool ?? GeneralUtility::makeInstance(ConnectionPool::class);
        $this->cacheManager = $cacheManager ?? GeneralUtility::makeInstance(CacheManager::class);
    }

    /**
     * Get hreflang relations from cache or generate and cache them
     *
     * @param int $pageId
     *
     * @return int[] Array of related page UIDs
     * @throws NoSuchCacheGroupException|NoSuchCacheException
     */
    public function getCachedRelations(int $pageId): array
    {
        $relations = $this->getCacheInstance()->get($pageId);

        if ($relations === false) {
            $relations = $this->buildRelations($pageId);
            $this->resetRelationCache($pageId, $relations);
        }

        return $this->getAllRelationUids($relations, $pageId);
    }

    /**
     * Clears the relation cache and sets new cached values with tags.
     *
     * @param int   $pageId (uid_local)
     * @param array|int $relations - relations array with uid_local and uid_foreign as keys OR relation page uid
     *
     * @throws NoSuchCacheGroupException|NoSuchCacheException
     */
    public function resetRelationCache(int $pageId, array|int $relations): void
    {
        $relationIds = is_array($relations) ? $relations : [['uid_foreign' => $relations]];

        $tags = array_filter(array_map(static function (array $value): ?string {
            return isset($value['uid_foreign']) ? 'pageId_' . (int)$value['uid_foreign'] : null;
        }, $relationIds));

        if (!empty($tags)) {
            $this->cacheManager->flushCachesInGroupByTags('pages', $tags);
            $this->getCacheInstance()->set((string)$pageId, $relationIds, $tags, 7 * 24 * 60 * 60);
        }
    }

    /**
     * Deletes hreflang relations for a page and flushes the corresponding cache.
     *
     * @param int $pageUid
     *
     * @throws NoSuchCacheGroupException|NoSuchCacheException
     */
    public function removeRelations(int $pageUid): void
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_hreflang_pages_page_page_mm');

        $relations = $this->getCachedRelations($pageUid);

        $affectedRows = $queryBuilder
            ->delete('tx_hreflang_pages_page_page_mm')
            ->where(
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->eq('uid_local', $queryBuilder->createNamedParameter($pageUid, Connection::PARAM_INT)),
                    $queryBuilder->expr()->eq('uid_foreign', $queryBuilder->createNamedParameter($pageUid, Connection::PARAM_INT))
                )
            )
            ->executeStatement();

        if ($affectedRows > 0) {
            $this->flushRelationCacheForPage($pageUid);
            foreach ($relations as $relatedUid) {
                $this->flushRelationCacheForPage($relatedUid);
            }
        }
    }

    /**
     * Flushes the cache for a single page by its UID.
     *
     * @param int $pageUid
     *
     * @throws NoSuchCacheGroupException
     */
    public function flushRelationCacheForPage(int $pageUid): void
    {
        $this->cacheManager->flushCachesInGroupByTag('pages', 'pageId_' . $pageUid);
    }

    /**
     * Builds a full list of hreflang relations, including indirect ones.
     *
     * @param int $pageId
     *
     * @return array Array of MM relation rows
     */
    public function buildRelations(int $pageId): array
    {
        $relations = $this->fetchRelationsForPage($pageId);

        $indirectRelations = [];

        foreach ($relations as $relation) {
            $uidLocal = (int)($relation['uid_local'] ?? 0);

            // Avoid looping if uid_local is invalid or same as pageId
            if ($uidLocal > 0 && $uidLocal !== $pageId) {
                $indirect = $this->fetchRelationsForPage($uidLocal, $pageId);
                $indirectRelations = array_merge($indirectRelations, $indirect);
            }
        }

        $allRelations = array_merge($relations, $indirectRelations);

        // Deduplicate by serialized content
        return array_map('unserialize', array_unique(array_map('serialize', $allRelations)));
    }

    /**
     * Retrieves relation records from the MM table for a given page.
     *
     * @param int      $pageId
     * @param int|null $excludePageId If set, excludes relations pointing to this ID.
     *
     * @return array Array of MM relation rows
     */
    private function fetchRelationsForPage(int $pageId, ?int $excludePageId = null): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $expr = $queryBuilder->expr();
        $conditions = [
            $expr->or(
                $expr->eq('mm.uid_local', $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT)),
                $expr->eq('mm.uid_foreign', $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT))
            ),
        ];

        if ($excludePageId !== null) {
            $conditions[] = $expr->neq('mm.uid_foreign', $queryBuilder->createNamedParameter($excludePageId, Connection::PARAM_INT));
        }

        return $queryBuilder
            ->select('mm.*')
            ->from('tx_hreflang_pages_page_page_mm', 'mm')
            ->leftJoin('mm', 'pages', 'p', 'mm.uid_foreign = p.uid')
            ->where(...$conditions)
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * Merges all UIDs from relations and excludes the source page UID.
     *
     * @param array $relations MM table relation rows.
     * @param int   $pageId    The original page UID to exclude.
     *
     * @return int[] Array of unique related page UIDs
     */
    protected function getAllRelationUids(array $relations, int $pageId): array
    {
        $uids = [];

        foreach ($relations as $relation) {
            if (isset($relation['uid_local'])) {
                $uids[] = (int)$relation['uid_local'];
            }
            if (isset($relation['uid_foreign'])) {
                $uids[] = (int)$relation['uid_foreign'];
            }
        }

        return array_values(array_diff(array_unique($uids), [$pageId]));
    }

    /**
     * Retrieves the cache instance for hreflang page relations.
     *
     * @param string $cacheIdentifier
     *
     * @return FrontendInterface
     * @throws NoSuchCacheException
     */
    protected function getCacheInstance(string $cacheIdentifier = 'tx_hreflang_pages_cache'): FrontendInterface
    {
        return $this->cacheManager->getCache($cacheIdentifier);
    }

    /**
     * When we detect a page, that triggers a SiteNotFoundException, we remove every relation to this uid
     *
     * @param $relationUid
     *
     * @return void
     */
    public function removeRelationsForNonExistentPage(int $relationUid)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_hreflang_pages_page_page_mm');

        $affectedRows = $queryBuilder->delete('tx_hreflang_pages_page_page_mm')
            ->where(
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->eq('uid_local', $queryBuilder->createNamedParameter($relationUid, \TYPO3\CMS\Core\Database\Connection::PARAM_INT)),
                    $queryBuilder->expr()->eq('uid_foreign', $relationUid),
                )
            )->executeStatement();
        if ($affectedRows > 0) {
            $this->flushRelationCacheForPage($relationUid);
        }
    }
}
