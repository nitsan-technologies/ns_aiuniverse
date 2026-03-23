<?php

declare(strict_types=1);

$javascriptModules = [
    'dependencies' => ['core', 'backend'],
    'imports' => [
        '@nitsan/nsaiuniverse/statistics-chart.js' => 'EXT:ns_aiuniverse/Resources/Public/JavaScript/Chart/statistics-chart.js',
    ],
];

return $javascriptModules;
