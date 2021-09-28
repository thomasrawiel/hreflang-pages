<?php
defined('TYPO3') or die('Access denied.');
call_user_func(function ($_EXTKEY = 'hreflang_pages', $table = 'pages') {
    $LLL = 'LLL:EXT:hreflang_pages/Resources/Private/Language/locallang_tca.xlf:';
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns($table, [
        'tx_hreflang_pages_hreflanglist' => [
            'exclude' => true,
            'displayCond' => [
                'OR' => [
                    'FIELD:sys_language_uid:=:0',
                    'FIELD:sys_language_uid:REQ:false',
                ]
            ],
            'label' => $LLL . 'page.preview',
            'config' => [
                'type' => 'none',
                'renderType' => 'hreflanglist'
            ]
        ],
        'tx_hreflang_pages_pages' => [
            'exclude' => true,
            'displayCond' => [
                'OR' => [
                    'FIELD:sys_language_uid:=:0',
                    'FIELD:sys_language_uid:REQ:false',
                ]
            ],
            'label' => $LLL . 'connected-pages',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'pages',
                'foreign_table' => 'pages',
                'MM' => 'tx_hreflang_pages_page_page_mm',
                'size' => 6,
                'autoSizeMax' => 30,
                'minitems' => 0,
                'maxitems' => 9999,
                'suggestOptions' => [
                    'default' => [
                        'searchWholePhrase' => 1,
                    ],
                ],
            ],
        ],
        'tx_hreflang_pages_pages_2' => [
            'exclude' => true,
            'displayCond' => [
                'OR' => [
                    'FIELD:sys_language_uid:=:0',
                    'FIELD:sys_language_uid:REQ:false',
                ]
            ],
            'label' => $LLL . 'connected-pages-2',
            'config' => [
                'readOnly' => true,
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'pages',
                'foreign_table' => 'pages',
                'foreign_table_where' => 'AND pages.sys_language_uid = 0',
                'MM' => 'tx_hreflang_pages_page_page_mm',
                'MM_opposite_field' => 'tx_hreflang_pages_pages',
                'size' => 6,
                'autoSizeMax' => 30,
                'minitems' => 0,
                'maxitems' => 9999,
                'fieldControl' => [
                    'elementBrowser' => [
                        'disabled' => true,
                    ],
                ],
            ],
        ],
        'tx_hreflang_pages_xdefault' => [
            'exclude' => true,
            'displayCond' => [
                'OR' => [
                    'FIELD:sys_language_uid:=:0',
                    'FIELD:sys_language_uid:REQ:false',
                ]
            ],
            'label' => $LLL . 'force-x-default',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        0 => $LLL . 'force-x-default.hint',
                        1 => '',
                    ]
                ],
                'default' => 0,
            ]
        ],
    ]);

    $GLOBALS['TCA'][$table]['palettes']['hreflang_connections'] = [
        'label' => $LLL.'palette.hreflang_connections',
        'showitem' => 'linebreak--,tx_hreflang_pages_pages,tx_hreflang_pages_pages_2,--linebreak--,tx_hreflang_pages_xdefault',
    ];
    $GLOBALS['TCA'][$table]['palettes']['hreflang_preview'] = [
        'label' => 'Hreflang Preview',
        'showitem' => 'tx_hreflang_pages_hreflanglist'
    ];

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('pages',
        "--div--;${LLL}div.hreflang,
        --palette--;;hreflang_connections,
        --palette--;;hreflang_preview",
        '',
        'after:sitemap_priority');

});