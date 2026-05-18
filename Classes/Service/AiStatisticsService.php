<?php

declare(strict_types=1);

namespace NITSAN\NsAiUniverse\Service;

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use NITSAN\NsAiUniverse\Client\BaseClient;
use NITSAN\NsAiUniverse\Helper\AiUniverseChartHelper;
use NITSAN\NsAiUniverse\Utility\AiUniverseUtilityHelper;

/**
 * AiStatisticsService
 *
 * Service for processing and caching AI usage statistics with chart data
 */
class AiStatisticsService
{
    private FrontendInterface $cache;
    private BaseClient $baseClient;
    private AiUniverseChartHelper $chartHelper;
    private string $extensionKey;
    private array $extConf;

    public function __construct(
        string $extensionKey = 'ns_aiuniverse',
        ?FrontendInterface $cache = null,
        ?BaseClient $baseClient = null,
        ?AiUniverseChartHelper $chartHelper = null
    ) {
        $this->extensionKey = $extensionKey;
        $this->extConf = AiUniverseUtilityHelper::getExtensionConf($extensionKey);

        $this->cache = $cache ?? GeneralUtility::makeInstance(CacheManager::class)->getCache('nsaiuniverse_statistics');

        // Initialize BaseClient
        $nonLegacyModel = $this->isNonLegacyModel();
        $this->baseClient = $baseClient ?? GeneralUtility::makeInstance(
            BaseClient::class,
            $nonLegacyModel,
            $this->extConf
        );

        $this->chartHelper = $chartHelper ?? GeneralUtility::makeInstance(AiUniverseChartHelper::class);
    }

    /**
     * Get processed OpenAI usage statistics with chart data
     *
     * @param string $date Date in Y-m-d format (empty for today)
     * @param int $dateScope Number of days (0 for single date)
     * @param bool $forceRefresh Force refresh from API (ignore cache)
     * @return array Processed statistics data with chart configurations
     */
    public function getOpenAiStatistics(string $date = '', int $dateScope = 0, bool $forceRefresh = false): array
    {
        if ($date === '') {
            $date = date('Y-m-d');
        }

        $cacheKey = $dateScope === 0 ? $date : 'dateScope';
        $statisticsCacheData = $this->cache->get('nsaiuniverse_statistics');

        // Check if we have cached data and should use it
        $useCache = !$forceRefresh &&
            $statisticsCacheData !== false &&
            isset($statisticsCacheData[$cacheKey]);

        if ($useCache) {
            $proceedData = $statisticsCacheData[$cacheKey];
        } else {
            // Fetch from API
            $apiData = $this->baseClient->getOpenAiUsageData($date, $dateScope);

            if (!$apiData['success']) {
                return [
                    'success' => false,
                    'error' => $this->formatErrorMessage($apiData['responseData'] ?? 'Unknown error'),
                    'data' => null,
                ];
            }

            // Process the data
            $proceedData = $this->chartHelper->processOpenAiUsageData($apiData['responseData']);

            // Cache the processed data (24 hours)
            $cacheData = $statisticsCacheData ?: [];
            $cacheData[$cacheKey] = $proceedData;
            $this->cache->set('nsaiuniverse_statistics', $cacheData, [], 86400);
        }

        // Generate chart configurations
        $chartConfigs = $this->generateChartConfigs($proceedData);

        return [
            'success' => true,
            'data' => $proceedData,
            'charts' => $chartConfigs,
            'summary' => [
                'totalRequests' => $proceedData['totalRequests'],
                'totalTokens' => $proceedData['totalTokens'],
            ],
        ];
    }

    /**
     * Generate all chart configurations from processed data
     *
     * @param array $proceedData Processed statistics data
     * @return array Chart configurations
     */
    private function generateChartConfigs(array $proceedData): array
    {
        return [
            'apiUsage' => $this->chartHelper->getChartConfig(
                $proceedData['numberOfRequestData'],
                'bar',
                'API Usages'
            ),
            'apiRequest' => $this->chartHelper->getChartConfig(
                $proceedData['numberOfRequestData'],
                'bar',
                'API Request',
                ['indexAxis' => 'y']
            ),
            'totalTokens' => $this->chartHelper->getChartConfig(
                $proceedData['totalTokenData'],
                'doughnut',
                'Total Tokens'
            ),
            'contextTokens' => $this->chartHelper->getChartConfig(
                $proceedData['contextTokenData'],
                'doughnut',
                'Context Tokens'
            ),
            'generatedTokens' => $this->chartHelper->getChartConfig(
                $proceedData['generatedTokenData'],
                'doughnut',
                'Generated Tokens'
            ),
        ];
    }

    /**
     * Format error message from API response
     *
     * @param string $error Raw error message
     * @return string Formatted error message
     */
    private function formatErrorMessage(string $error): string
    {
        if (str_contains($error, "You've exceeded the 5 request/min rate limit")) {
            return "You've exceeded the 5 request/min rate limit, please slow down and try again";
        }
        if (str_contains($error, 'Invalid authorization header')) {
            return 'Invalid authorization header';
        }
        if (str_contains($error, 'Incorrect API key provided')) {
            return 'Incorrect API key provided';
        }
        return $error;
    }

    /**
     * Check if using non-legacy model
     *
     * @return bool
     */
    private function isNonLegacyModel(): bool
    {
        $model = $this->extConf['openai_model'] ?? 'gpt-3.5-turbo';
        $nonLegacyModels = ['gpt-4', 'gpt-4o', 'gpt-4.1', 'gpt-5', 'gpt-3.5-turbo'];
        return in_array($model, $nonLegacyModels);
    }

    /**
     * Clear statistics cache
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->cache->flush();
    }
}
