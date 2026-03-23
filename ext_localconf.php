<?php

declare(strict_types=1);

defined('TYPO3_MODE') || defined('TYPO3') || die();

// Register cache configurations
if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['nsaiuniverse_statistics'] ?? null)) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['nsaiuniverse_statistics'] = [];
}
