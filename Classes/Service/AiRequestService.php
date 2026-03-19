<?php

declare(strict_types=1);

namespace NITSAN\NsAiUniverse\Service;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use NITSAN\NsAiUniverse\Client\BaseClient;
use NITSAN\NsAiUniverse\Utility\AiUniverseUtilityHelper;

/**
 * AiRequestService
 *
 * Core service for sending AI requests and processing responses.
 * This service handles all AI provider communication, logging, and error handling.
 */
class AiRequestService
{
    private array $extConf;
    private string $extensionKey;
    private RequestFactory $requestFactory;
    private BaseClient $baseClient;
    private AiLogService $logService;
    private bool $nonLegacyModel;

    public function __construct(
        string $extensionKey = 'ns_aiuniverse',
        ?RequestFactory $requestFactory = null,
        ?BaseClient $baseClient = null,
        ?AiLogService $logService = null
    ) {
        $this->extensionKey = $extensionKey;
        $this->extConf = AiUniverseUtilityHelper::getExtensionConf($this->extensionKey);
        $this->requestFactory = $requestFactory ?? GeneralUtility::makeInstance(RequestFactory::class);
        $this->logService = $logService ?? GeneralUtility::makeInstance(AiLogService::class, $this->extensionKey);

        // Determine if we're using legacy models
        $this->nonLegacyModel = $this->isNonLegacyModel();
        $this->baseClient = $baseClient ?? GeneralUtility::makeInstance(
            BaseClient::class,
            $this->nonLegacyModel,
            $this->extConf
        );
    }

    /**
     * Send AI request and get response
     *
     * @param string $modelType AI model type (openai, gemini, claude, etc.)
     * @param array $messages Message array in format: [['role' => 'user', 'content' => '...']]
     * @param string $aiSelectedModel Specific model to use (e.g., 'gpt-4o', 'gemini-1.5-flash')
     * @param array $options Additional options (temperature, max_tokens, etc.)
     * @param bool $logRequest Whether to log this request
     * @param string $module Module name for logging
     * @param string $scope Scope for logging
     * @return string Generated text response
     * @throws Exception|GuzzleException
     */
    public function sendRequest(
        string $modelType,
        array $messages,
        string $aiSelectedModel = '',
        array $options = [],
        bool $logRequest = true,
        string $module = '',
        string $scope = ''
    ): string {
        try {
            // Prepare request data
            $requestData = $this->baseClient->getRequestData(
                $modelType,
                $this->prepareMessages($messages, $options),
                null,
                $aiSelectedModel
            );

            // Send request
            $response = $this->requestFactory->request(
                $requestData['url'],
                'POST',
                $requestData['body']
            );

            $resJsonBody = $response->getBody()->getContents();
            $resBody = json_decode($resJsonBody, true);

            // Check for errors in response
            if (isset($resBody['error'])) {
                $errorMessage = $resBody['error']['message'] ?? 'Unknown error';
                if ($logRequest) {
                    $this->logService->writeLog(
                        $errorMessage,
                        'error'
                    );
                }
                throw new Exception('AI Request failed: ' . $errorMessage);
            }

            // Extract response text
            $generatedText = $this->baseClient->getResponseData($modelType, $resBody);

            // Log successful request
            if ($logRequest) {
                $this->logService->writeLog(
                    'AI request successful',
                    'info',
                );
            }

            return $generatedText;

        } catch (GuzzleException $e) {
            if ($logRequest) {
                $this->logService->writeLog(
                    $e->getMessage(),
                    'error',
                );
            }
            throw $e;
        } catch (Exception $e) {
            if ($logRequest) {
                $this->logService->writeLog(
                    $e->getMessage(),
                    'error',
                );
            }
            throw $e;
        }
    }

    /**
     * Prepare messages array with default options
     *
     * @param array $messages
     * @param array $options
     * @return array
     */
    private function prepareMessages(array $messages, array $options = []): array
    {
        $defaultOptions = [
            'model' => $this->extConf['openai_model'] ?? 'gpt-3.5-turbo',
            'temperature' => (float)($this->extConf['openai_temperature'] ?? 0.7),
            'max_tokens' => $options['max_tokens'] ?? (int)($this->extConf['openai_max_tokens'] ?? 1024),
            'top_p' => $options['top_p'] ?? 0.01,
            'frequency_penalty' => $options['frequency_penalty'] ?? 0.01,
            'presence_penalty' => $options['presence_penalty'] ?? 0.01,
        ];

        // Merge with provided options
        $mergedOptions = array_merge($defaultOptions, $options);

        // Add messages
        $mergedOptions['messages'] = $messages;

        return $mergedOptions;
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
     * Get OpenAI usage data
     *
     * @param string $date Date in Y-m-d format
     * @param int $dateScope Number of days (0 for single date)
     * @return array
     */
    public function getOpenAiUsageData(string $date = '', int $dateScope = 0): array
    {
        return $this->baseClient->getOpenAiUsageData($date, $dateScope);
    }

    /**
     * Get extension configuration
     *
     * @return array
     */
    public function getExtensionConfiguration(): array
    {
        return $this->extConf;
    }
}
