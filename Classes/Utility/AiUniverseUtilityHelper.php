<?php

declare(strict_types=1);

namespace NITSAN\NsAiUniverse\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * AiUniverseUtilityHelper
 *
 * Utility helper class providing common functionality for AI extensions
 */
class AiUniverseUtilityHelper
{
    /**
     * Get TYPO3 version data as array
     *
     * @return array
     */
    public static function getVersionData(): array
    {
        return VersionNumberUtility::convertVersionStringToArray(
            VersionNumberUtility::getCurrentTypo3Version()
        );
    }

    /**
     * Get current language ID from module data
     *
     * @return int
     */
    public static function getLanguageId(): int
    {
        $moduleData = BackendUtility::getModuleData(['language'], [], 'web_layout');
        if (isset($moduleData['language'])) {
            return (int)$moduleData['language'];
        }
        return 0;
    }

    /**
     * Get current page record
     *
     * @param int $pageId
     * @param int $languageId
     * @return array|null
     */
    public static function getCurrentPage(int $pageId, int $languageId): ?array
    {
        $currentPage = null;
        if ($pageId > 0) {
            if ($languageId === 0) {
                $currentPage = BackendUtility::getRecord(
                    'pages',
                    $pageId
                );
            } elseif ($languageId > 0) {
                $overlayRecords = BackendUtility::getRecordLocalization(
                    'pages',
                    $pageId,
                    $languageId
                );

                if (is_array($overlayRecords) && array_key_exists(0, $overlayRecords) && is_array($overlayRecords[0])) {
                    $currentPage = $overlayRecords[0];
                }
            }
        }
        return $currentPage;
    }

    /**
     * Get extension configuration
     *
     * @param string $extensionKey Extension key (default: 'ns_aiuniverse')
     * @return array
     */
    public static function getExtensionConf(string $extensionKey = 'ns_aiuniverse'): array
    {
        try {
            return GeneralUtility::makeInstance(ExtensionConfiguration::class)
                ->get($extensionKey);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Set extension configuration
     *
     * @param array $value Configuration values
     * @param string $extensionKey Extension key (default: 'ns_aiuniverse')
     * @return void
     */
    public static function setExtensionConf(array $value, string $extensionKey = 'ns_aiuniverse'): void
    {
        GeneralUtility::makeInstance(ExtensionConfiguration::class)
            ->set($extensionKey, $value);
    }

    /**
     * Check if API key is set for a given extension
     *
     * @param string $extensionKey Extension key (default: 'ns_aiuniverse')
     * @param string $apiKeyName API key configuration name (default: 'openai_api_key')
     * @return bool
     */
    public static function isApiKeySet(string $extensionKey = 'ns_aiuniverse', string $apiKeyName = 'openai_api_key'): bool
    {
        $extConf = self::getExtensionConf($extensionKey);
        return !empty($extConf[$apiKeyName]);
    }

    /**
     * Get TYPO3 major version
     *
     * @return int
     */
    public static function getTypo3MajorVersion(): int
    {
        $versionData = self::getVersionData();
        return (int)($versionData['version_main'] ?? 11);
    }

    /**
     * Check if an extension is loaded
     *
     * @param string $extensionKey Extension key to check
     * @return bool
     */
    public static function isExtensionLoaded(string $extensionKey): bool
    {
        return ExtensionManagementUtility::isLoaded($extensionKey);
    }
}
