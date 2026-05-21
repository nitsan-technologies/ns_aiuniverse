<?php

declare(strict_types=1);

namespace NITSAN\NsAiUniverse\Service;

use NITSAN\NsAiUniverse\Utility\AiUniverseUtilityHelper;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\MailerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\TemplatePaths;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Sends email alerts for critical AI API failures (quota exceeded, invalid API key).
 * Uses TYPO3 core Fluid email template "Default" (SystemEmail layout).
 */
final class AiApiAlertNotificationService
{
    private const CACHE_NAME = 'nsaiuniverse_api_alert';
    private const CACHE_IDENTIFIER_PREFIX = 'api_alert_mail_';
    private const COOLDOWN_SECONDS = 60; // 3600

    private FrontendInterface $cache;

    public function __construct(?FrontendInterface $cache = null)
    {
        $this->cache = $cache ?? GeneralUtility::makeInstance(CacheManager::class)->getCache(self::CACHE_NAME);
    }

    /**
     * Notify configured recipient when the error matches quota or API-key issues.
     */
    public function notifyIfApplicable(
        string $errorMessage,
        string $occurFrom = 'ns_aiuniverse',
        ?string $aiEngine = null
    ): void {
        $category = $this->classifyError($errorMessage);
        if ($category === null) {
            return;
        }

        $extConf = AiUniverseUtilityHelper::getExtensionConf('ns_aiuniverse');
        if (empty($extConf['enableApiQuotaEmailNotification'])) {
            return;
        }

        $recipient = trim((string)($extConf['apiQuotaNotificationEmail'] ?? ''));
        if ($recipient === '' || !GeneralUtility::validEmail($recipient)) {
            return;
        }

        $cacheKey = self::CACHE_IDENTIFIER_PREFIX . $category . '_' . preg_replace('/[^a-z0-9_]/', '_', strtolower($occurFrom));
        if ($this->cache->get($cacheKey) !== false) {
            return;
        }

        $this->sendNotificationEmail(
            $recipient,
            $errorMessage,
            $occurFrom,
            $category,
            $this->resolveAiEngineForNotification($aiEngine, $occurFrom)
        );
        $this->cache->set($cacheKey, 1, [], self::COOLDOWN_SECONDS);
    }

    /**
     * Log a quota/API-key error to sys_log and send the configured alert email (T3AS, T3AC, etc.).
     */
    public function reportApiError(
        string $errorMessage,
        string $occurFrom = 'ns_aiuniverse',
        ?string $aiEngine = null
    ): void {
        $errorMessage = trim($errorMessage);
        if ($errorMessage === '' || $this->classifyError($errorMessage) === null) {
            return;
        }

        $logMessage = str_starts_with($errorMessage, 'Error ') ? $errorMessage : 'Error ' . $errorMessage;
        GeneralUtility::makeInstance(AiLogService::class)->writeLog($logMessage, 'error', $occurFrom, $aiEngine);
    }

    /**
     * @return 'quota'|'api_key'|null
     */
    public function classifyError(string $errorMessage): ?string
    {
        $lower = mb_strtolower($errorMessage, 'UTF-8');
        if ($lower === '') {
            return null;
        }

        $quotaPatterns = [
            'insufficient_quota',
            'quota exceeded',
            'data quota',
            'exceeded your current quota',
            'billing hard limit',
            'exceeded your quota',
            'usage limit',
            'credit balance',
            'out of credits',
        ];
        foreach ($quotaPatterns as $pattern) {
            if (str_contains($lower, $pattern)) {
                return 'quota';
            }
        }

        $apiKeyPatterns = [
            'incorrect api key',
            'invalid api key',
            'invalid authorization header',
            'invalid_api_key',
            'authentication_error',
            'api key provided',
            'unauthorized',
            'invalid authentication',
        ];
        foreach ($apiKeyPatterns as $pattern) {
            if (str_contains($lower, $pattern)) {
                return 'api_key';
            }
        }

        return null;
    }

    private function sendNotificationEmail(
        string $recipient,
        string $errorMessage,
        string $occurFrom,
        string $category,
        string $aiEngine
    ): void {
        $subject = $category === 'quota'
            ? (LocalizationUtility::translate('email.apiAlert.subject.quota', 'ns_aiuniverse')
                ?: 'AI API data quota exceeded')
            : (LocalizationUtility::translate('email.apiAlert.subject.apiKey', 'ns_aiuniverse')
                ?: 'AI API authentication error');

        $content = $this->buildMailBody($errorMessage, $occurFrom, $aiEngine);

        $fromAddress = trim((string)($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'] ?? ''));
        $fromName = trim((string)($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'] ?? 'T3 Planet'));

        try {
            $email = $this->createFluidEmail();
            $email
                ->to($recipient)
                ->subject($subject)
                ->format(FluidEmail::FORMAT_HTML)
                ->setTemplate('Default')
                ->assignMultiple([
                    'headline' => $subject,
                    'introduction' => '',
                    'content' => $content,
                ]);

            if ($fromAddress !== '' && GeneralUtility::validEmail($fromAddress)) {
                $email->from(new Address($fromAddress, $fromName !== '' ? $fromName : 'TYPO3'));
            }

            $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
            if ($request instanceof ServerRequestInterface) {
                $email->setRequest($request);
            }

            GeneralUtility::makeInstance(MailerInterface::class)->send($email);
        } catch (TransportExceptionInterface $exception) {
            // Mail transport not configured — do not break AI flows.
        } catch (\Throwable $exception) {
            // Ignore mail failures.
        }
    }

    private function createFluidEmail(): FluidEmail
    {
        $templatePaths = new TemplatePaths();
        $templatePaths->setTemplateRootPaths([
            'EXT:ns_aiuniverse/Resources/Private/Templates/Email/',
        ]);
        $templatePaths->setLayoutRootPaths([
            'EXT:ns_aiuniverse/Resources/Private/Layouts/Email/',
        ]);

        return GeneralUtility::makeInstance(FluidEmail::class, $templatePaths);
    }

    private function buildMailBody(string $errorMessage, string $occurFrom, string $aiEngine): string
    {
        $aiEngineLabel = LocalizationUtility::translate('email.apiAlert.aiEngine', 'ns_aiuniverse') ?: 'AI Engine';
        $errorLabel = LocalizationUtility::translate('email.apiAlert.errorMessage', 'ns_aiuniverse') ?: 'Error Message';
        $occurLabel = LocalizationUtility::translate('email.apiAlert.occurFrom', 'ns_aiuniverse') ?: 'Generated from';
        $timeLabel = LocalizationUtility::translate('email.apiAlert.timestamp', 'ns_aiuniverse') ?: 'Time';

        $aiEngineEscaped = htmlspecialchars($aiEngine, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $errorMessageEscaped = htmlspecialchars(trim($errorMessage), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $occurFromEscaped = htmlspecialchars($occurFrom, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $timestamp = $this->formatTimestampWithTimezone();

        return htmlspecialchars($aiEngineLabel, ENT_QUOTES | ENT_HTML5, 'UTF-8') . ' : ' . $aiEngineEscaped
            . '<br /><br />' . htmlspecialchars($errorLabel, ENT_QUOTES | ENT_HTML5, 'UTF-8') . ': ' . $errorMessageEscaped
            . '<br /><br />' . htmlspecialchars($occurLabel, ENT_QUOTES | ENT_HTML5, 'UTF-8') . ' : ' . $occurFromEscaped
            . '<br /><br />' . htmlspecialchars($timeLabel, ENT_QUOTES | ENT_HTML5, 'UTF-8')
            . ': ' . htmlspecialchars($timestamp, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function resolveAiEngineForNotification(?string $aiEngine, string $occurFrom): string
    {
        if ($aiEngine !== null && trim($aiEngine) !== '') {
            return $this->normalizeEngineName($aiEngine);
        }

        return $this->resolveAiEngine($occurFrom);
    }

    private function resolveAiEngine(string $occurFrom): string
    {
        $aiuniverse = AiUniverseUtilityHelper::getExtensionConf('ns_aiuniverse');

        if ($occurFrom === 'ns_t3ac' && ExtensionManagementUtility::isLoaded('ns_t3ac')) {
            $t3ac = AiUniverseUtilityHelper::getExtensionConf('ns_t3ac');
            $chatModel = (string)($t3ac['defaultChatModel'] ?? '');
            if ($chatModel !== '' && $chatModel !== 'default') {
                return $this->normalizeEngineName($chatModel);
            }
        }

        if (in_array($occurFrom, ['ns_t3as', 'ns_t3ac', 'ns_t3cs'], true) && ExtensionManagementUtility::isLoaded('ns_t3cs')) {
            $t3cs = AiUniverseUtilityHelper::getExtensionConf('ns_t3cs');
            $model = (string)($t3cs['defaultModel'] ?? '');
            if ($model === '' || $model === 'default') {
                return $this->normalizeEngineName((string)($aiuniverse['defaultModel'] ?? 'unknown'));
            }

            return $this->normalizeEngineName($model);
        }

        return $this->normalizeEngineName(
            (string)($aiuniverse['defaultModel'] ?? $aiuniverse['defaultGenerationModel'] ?? 'unknown')
        );
    }

    private function normalizeEngineName(string $engine): string
    {
        return strtolower(trim($engine));
    }

    private function formatTimestampWithTimezone(): string
    {
        $timezoneIdentifier = trim((string)($GLOBALS['TYPO3_CONF_VARS']['SYS']['timezone'] ?? 'UTC'));
        if ($timezoneIdentifier === '') {
            $timezoneIdentifier = 'UTC';
        }

        try {
            $timezone = new \DateTimeZone($timezoneIdentifier);
        } catch (\Exception $exception) {
            $timezone = new \DateTimeZone('UTC');
            $timezoneIdentifier = 'UTC';
        }

        $dateTime = new \DateTimeImmutable('now', $timezone);

        return $dateTime->format('Y-m-d H:i:s') . ' (' . $timezoneIdentifier . ')';
    }
}
