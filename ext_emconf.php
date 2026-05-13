<?php

declare(strict_types=1);

$EM_CONF['ns_aiuniverse'] = [
    'title' => 'AI Universe',
    'description' => 'AI Universe is the shared AI foundation layer for TYPO3 extensions. It centralizes AI provider communication, model selection, request handling, statistics preparation, and utility functions so other extensions can build AI features faster and with consistent behavior.',
    'category' => 'be',
    'author' => 'Team T3Planet',
    'author_email' => 'support@t3planet.de',
    'author_company' => 'T3Planet // NITSAN',
    'state' => 'stable',
    'clearCacheOnLoad' => 0,
    'version' => '1.0.1',
    'constraints' => [
        'depends' => [
            'typo3' => '11.0.0-13.4.99',
            'php' => '7.4.0-8.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
