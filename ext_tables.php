<?php
defined('TYPO3_MODE') or die('Access denied.');
call_user_func(function ($_EXTKEY = 'hreflang_pages') {
    $GLOBALS['TBE_STYLES']['skins'][$_EXTKEY] = [
        'name' => $_EXTKEY,
        'stylesheetDirectories' => [
            'EXT:' . $_EXTKEY . '/Resources/Public/Css/Backend/'
        ]
    ];
});
