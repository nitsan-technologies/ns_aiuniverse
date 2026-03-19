<?php

$EM_CONF['ns_aiuniverse'] = [
    'title' => 'AI Universe - Base AI Extension',
    'description' => 'Base extension providing AI model configuration, HTTP authentication, and AI request/response processing. This extension serves as the foundation for AI-powered TYPO3 extensions.',
    'category' => 'be',
    'author' => 'Team T3Planet',
    'author_email' => 'support@t3planet.com',
    'author_company' => 'T3Planet // NITSAN',
    'state' => 'beta',
    'clearCacheOnLoad' => 0,
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '11.0.0-13.4.99',
            'php' => '7.4.0-8.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
