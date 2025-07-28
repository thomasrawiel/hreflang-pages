<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Hreflang Pages',
    'description' => 'Extends TYPO3 EXT:seo hreflang functionality',
    'category' => 'fe',
    'author' => 'Thomas Rawiel',
    'author_email' => 'thomas.rawiel@gmail.com',
    'state' => 'stable',
    'version' => '2.1.2',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-13.4.99',
            'seo' => '12.4.0-12.99.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
