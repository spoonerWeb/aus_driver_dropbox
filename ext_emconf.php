<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'anders und sehr: Dropbox FAL driver (CDN)',
    'description' => 'Provides a FAL driver for Dropbox. See documentation for more details.',
    'category' => 'be',
    'version' => '1.0.2',
    'state' => 'stable',
    'uploadfolder' => false,
    'createDirs' => '',
    'clearcacheonload' => false,
    'author' => 'Markus Hoelzle',
    'author_email' => 'm.hoelzle@andersundsehr.com',
    'author_company' => 'anders und sehr GmbH',
    'constraints' =>
        [
            'depends' =>
                [
                    'typo3' => '6.2.0-6.2.99',
                ],
            'conflicts' =>
                [
                ],
            'suggests' =>
                [
                ],
        ],
];
