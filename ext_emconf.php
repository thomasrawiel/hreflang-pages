<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'Hreflang Pages',
    'description' => 'Extends TYPO3 EXT:seo hreflang functionality',
    'category' => 'fe',
    'author' => 'Thomas Rawiel',
    'author_email' => 'thomas.rawiel@gmail.com',
    'state' => 'stable',
    'clearCacheOnLoad' => 0,
    'version' => '1.0.2',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-11.99.99',
            'seo' => '11.5.0-11.99.99'
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];