<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Hreflang Pages',
    'description' => 'Extends TYPO3 EXT:seo hreflang functionality',
    'category' => 'fe',
    'author' => 'Thomas Rawiel',
    'author_email' => 'thomas.rawiel@gmail.com',
    'state' => 'stable',
    'version' => '3.0.2',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-14.99.99',
            'seo' => '13.4.0-14.99.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
