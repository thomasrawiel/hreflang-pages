<?php
defined('TYPO3') or die('Access denied.');
call_user_func(function($_EXTKEY = 'hreflang_pages') {
    //register renderType for Backend preview
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1622628435] = [
        'nodeName' => 'hreflanglist',
        'priority' => 40,
        'class' => \TRAW\HreflangPages\Form\Element\HreflangList::class,
    ];

    //Register cache
    if (!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_hreflang_pages_cache'])) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_hreflang_pages_cache'] = [];
    }
    //Clear cache when pages cache is cleared
    if (!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_hreflang_pages_cache']['groups'])) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_hreflang_pages_cache']['groups'] = ['pages'];
    }

    $GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][$_EXTKEY] = \TRAW\HreflangPages\Hooks\TCEmainHook::class;
    $GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][$_EXTKEY] = \TRAW\HreflangPages\Hooks\TCEmainHook::class;
});