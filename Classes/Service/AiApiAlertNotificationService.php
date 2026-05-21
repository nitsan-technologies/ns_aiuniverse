<?php

declare(strict_types=1);

namespace NITSAN\NsAiUniverse\Service;

use NITSAN\NsAiUniverse\Utility\AiUniverseUtilityHelper;
use Psr\Http\Message\ServerRequestInterface;
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
    private const COOLDOWN_SECONDS = 3600;

    private FrontendInterface $cache;

    public function __construct(?FrontendInterface $cache = null)
    {
        $this->cache = $cache ?? GeneralUtility::makeInstance(CacheManager::class)->getCache(self::CACHE_NAME);
    }

    /**
     * Notify configured recipient when the error matches quota or API-key issues.
     */
    public function notifyIfApplicable(string $errorMessage, string $occurFrom = 'ns_aiuniverse'): void
    {
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

        $cacheKey = self::CACHE_IDENTIFIER_PREFIX . $category;
        if ($this->cache->get($cacheKey) !== false) {
            return;
        }

        $this->sendNotificationEmail($recipient, $errorMessage, $occurFrom, $category);
        $this->cache->set($cacheKey, 1, [], self::COOLDOWN_SECONDS);
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
        string $category
    ): void {
        $subject = $category === 'quota'
            ? (LocalizationUtility::translate('email.apiAlert.subject.quota', 'ns_aiuniverse')
                ?: 'AI API data quota exceeded')
            : (LocalizationUtility::translate('email.apiAlert.subject.apiKey', 'ns_aiuniverse')
                ?: 'AI API authentication error');

        $content = $this->buildMailBody($errorMessage, $occurFrom);

        $fromAddress = trim((string)($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'] ?? ''));
        $fromName = trim((string)($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'] ?? 'TYPO3'));

        try {
            // $email = $this->createFluidEmail()
            $email = new FluidEmail();
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
            'EXT:ns_aiuniverse/Resources/Private/Layouts/',
            'EXT:core/Resources/Private/Layouts/',
        ]);

        return GeneralUtility::makeInstance(FluidEmail::class, $templatePaths);
    }

    private function buildMailBody(string $errorMessage, string $occurFrom): string
    {
        $errorLabel = LocalizationUtility::translate('email.apiAlert.errorMessage', 'ns_aiuniverse') ?: 'Error Message';
        $occurLabel = LocalizationUtility::translate('email.apiAlert.occurFrom', 'ns_aiuniverse') ?: 'Occur from';
        $timeLabel = LocalizationUtility::translate('email.apiAlert.timestamp', 'ns_aiuniverse') ?: 'Time';

        $errorMessageEscaped = htmlspecialchars(trim($errorMessage), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $occurFromEscaped = htmlspecialchars($occurFrom, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return htmlspecialchars($errorLabel, ENT_QUOTES | ENT_HTML5, 'UTF-8') . ': ' . $errorMessageEscaped
            . '<br /><br />' . htmlspecialchars($occurLabel, ENT_QUOTES | ENT_HTML5, 'UTF-8') . ' : ' . $occurFromEscaped
            . '<br /><br />' . htmlspecialchars($timeLabel, ENT_QUOTES | ENT_HTML5, 'UTF-8')
            . ': ' . date('Y-m-d H:i:s');
    }
}
