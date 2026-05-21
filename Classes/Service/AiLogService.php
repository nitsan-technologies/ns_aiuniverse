<?php

declare(strict_types=1);

namespace NITSAN\NsAiUniverse\Service;

use NITSAN\NsAiUniverse\Utility\AiUniverseUtilityHelper;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class AiLogService
{
    private array $extConf;
    private Context $context;
    protected LoggerInterface $logger;
    private string $extensionKey;

    /**
     * @param string $extensionKey Extension key to read configuration from
     */
    public function __construct(string $extensionKey = 'ns_aiuniverse')
    {
        $this->extensionKey = $extensionKey;
        $this->extConf = AiUniverseUtilityHelper::getExtensionConf($this->extensionKey);
        $this->context = GeneralUtility::makeInstance(Context::class);
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
    }

    public function writeLog(string $logMessage, string $logLevel, string $module = 'ns_aiuniverse'): void
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('sys_log');

        if ($connection->isConnected()) {
            $userId = 0;
            $workspace = 0;
            $data = [];
            $backendUser = $this->getBackendUser();

            if ($backendUser instanceof BackendUserAuthentication) {
                if (isset($backendUser->user['uid'])) {
                    $userId = (int)$backendUser->user['uid'];
                }
                $workspace = (int)$backendUser->workspace;
                if ($backUserId = $backendUser->getOriginalUserIdWhenInSwitchUserMode()) {
                    $data['originalUser'] = $backUserId;
                }
            }

            $connection->insert(
                'sys_log',
                [
                    'userid' => $userId,
                    'type' => 1, // Custom error type, adjust as needed
                    'channel' => $module,
                    'action' => 0,
                    'error' => 1,
                    'level' => $logLevel,
                    'details_nr' => 0,
                    'details' => str_replace('%', '%%', $logMessage),
                    'log_data' => empty($data) ? '' : json_encode($data),
                    'IP' => GeneralUtility::getIndpEnv('REMOTE_ADDR') ?: '',
                    'tstamp' => time(),
                    'workspace' => $workspace,
                ]
            );

            if ($logLevel === 'error') {
                GeneralUtility::makeInstance(AiApiAlertNotificationService::class)
                    ->notifyIfApplicable($logMessage, $module);
            }
        }
    }

    protected function getBackendUser(): ?BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'] ?? null;
    }
}
