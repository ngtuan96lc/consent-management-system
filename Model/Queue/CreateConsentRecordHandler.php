<?php

declare(strict_types=1);

namespace SoftLoft\ConsentManagementSystem\Model\Queue;

use Exception;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;
use SoftLoft\ConsentManagementSystem\Api\ConsentRequestInterface;
use SoftLoft\ConsentManagementSystem\Helper\ConfigHelper;
use SoftLoft\ConsentManagementSystem\Model\ConsentQueueManagement;

class CreateConsentRecordHandler
{
    public const TOPIC_NAME_CREATE_CONSENT_RECORD = 'consent.createConsentRecord';

    /**
     * @var ConsentRequestInterface
     */
    protected ConsentRequestInterface $consentRequest;

    /**
     * @var Json
     */
    protected Json $json;

    /**
     * @var ConfigHelper
     */
    protected ConfigHelper $configHelper;

    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * @var ConsentQueueManagement
     */
    protected ConsentQueueManagement $consentQueueManagement;

    /**
     * @param ConsentRequestInterface $consentRequest
     * @param Json $json
     * @param ConfigHelper $configHelper
     * @param LoggerInterface $logger
     * @param ConsentQueueManagement $consentQueueManagement
     */
    public function __construct(
        ConsentRequestInterface $consentRequest,
        Json $json,
        ConfigHelper $configHelper,
        LoggerInterface $logger,
        ConsentQueueManagement $consentQueueManagement
    ) {
        $this->consentRequest = $consentRequest;
        $this->json = $json;
        $this->configHelper = $configHelper;
        $this->logger = $logger;
        $this->consentQueueManagement = $consentQueueManagement;
    }

    /**
     * Process function of Consumer
     *
     * @param string $data
     * @return void
     */
    public function process(string $data)
    {
        $dataArray = $this->json->unserialize($data);
        if (array_key_exists('consentPrivacyPolicy', $dataArray) && $dataArray['consentPrivacyPolicy'] == null) {
            $dataArray['consentPrivacyPolicy'] = $this->configHelper->getCurrentVersion();
        }
        $validate = $this->validate($dataArray);
        if ($validate) {
            $prepareData = [
                'topic_name' => self::TOPIC_NAME_CREATE_CONSENT_RECORD,
                'body' => $data,
                'consentPrivacyPolicy' => $dataArray['consentPrivacyPolicy']
            ];
            if (isset($dataArray['message_id'])) {
                $prepareData['message_id'] = $dataArray['message_id'];
            }

            try {
                $this->consentRequest->createConsentRecord(
                    $dataArray['email'],
                    (int)$dataArray['customerId'],
                    $dataArray['consentPrivacyPolicy']
                );
                $prepareData['status'] = 1;
                $this->consentQueueManagement->addOrUpdateMessageQueue($prepareData);
            } catch (Exception $e) {
                $this->logger->info(__('Unable create consent record with email %1', $dataArray['email']));
                $prepareData['status'] = 0;
                $messageId = $this->consentQueueManagement->addOrUpdateMessageQueue($prepareData);
                if ($messageId) {
                    $this->consentQueueManagement->addLogForMessageQueue($messageId, $e);
                }
            }
        }
    }

    /**
     * Validate
     *
     * @param array $data
     * @return bool
     */
    protected function validate(array $data): bool
    {
        if (isset($data['email']) && isset($data['customerId'])) {
            return true;
        }
        return false;
    }
}
