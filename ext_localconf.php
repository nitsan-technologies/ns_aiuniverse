<?php

declare(strict_types=1);

defined('TYPO3_MODE') || defined('TYPO3') || die();

// Register cache configurations
if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['nsaiuniverse_statistics'] ?? null)) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['nsaiuniverse_statistics'] = [];
}

if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['nsaiuniverse_api_alert'] ?? null)) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['nsaiuniverse_api_alert'] = [];
}


$GLOBALS['TYPO3_CONF_VARS']['MAIL']['templateRootPaths'][1779278704] = 'EXT:ns_aiuniverse/Resources/Private/Templates/Email/';
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['layoutRootPaths'][1779278704] = 'EXT:ns_aiuniverse/Resources/Private/Layouts/Email/';
