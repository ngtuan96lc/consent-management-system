<?php

declare(strict_types=1);

namespace SoftLoft\ConsentManagementSystem\Model;

use Exception;
use Psr\Log\LoggerInterface;
use SoftLoft\ConsentManagementSystem\Model\ResourceModel\ConsentQueue as ConsentQueueResource;
use SoftLoft\ConsentManagementSystem\Model\ResourceModel\ConsentQueueLog as ConsentQueueLogResource;

class ConsentQueueManagement
{
    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * @var ConsentQueueFactory
     */
    protected ConsentQueueFactory $consentQueue;

    /**
     * @var ConsentQueueResource
     */
    protected ConsentQueueResource $consentQueueResource;

    /**
     * @var ConsentQueueLogFactory
     */
    protected ConsentQueueLogFactory $consentQueueLog;

    /**
     * @var ConsentQueueLogResource
     */
    protected ConsentQueueLogResource $consentQueueLogResource;

    /**
     * @param LoggerInterface $logger
     * @param ConsentQueueFactory $consentQueue
     * @param ConsentQueueResource $consentQueueResource
     * @param ConsentQueueLogFactory $consentQueueLog
     * @param ConsentQueueLogResource $consentQueueLogResource
     */
    public function __construct(
        LoggerInterface $logger,
        ConsentQueueFactory $consentQueue,
        ConsentQueueResource $consentQueueResource,
        ConsentQueueLogFactory $consentQueueLog,
        ConsentQueueLogResource $consentQueueLogResource
    ) {
        $this->logger = $logger;
        $this->consentQueue = $consentQueue;
        $this->consentQueueResource = $consentQueueResource;
        $this->consentQueueLog = $consentQueueLog;
        $this->consentQueueLogResource = $consentQueueLogResource;
    }

    /**
     * Add / Update Message Queue
     *
     * @param array $data
     * @return false|mixed
     */
    public function addOrUpdateMessageQueue(array $data)
    {
        if (isset($data['message_id'])) {
            $consentQueueModel = $this->consentQueue->create()->load($data['message_id']);
            $consentQueueModel->setData('tries', $consentQueueModel->getData('tries') + 1);
        } else {
            $consentQueueModel = $this->consentQueue->create();
            $consentQueueModel->setData('tries', 0);
        }
        $consentQueueModel->setData('topic_name', $data['topic_name'] ?? null);
        $consentQueueModel->setData('status', $data['status'] ?? 0);
        $consentQueueModel->setData('body', $data['body'] ?? null);
        try {
            $this->consentQueueResource->save($consentQueueModel);
            return $consentQueueModel->getId();
        } catch (Exception $exception) {
            $this->logger->critical($exception->getMessage());
            return false;
        }
    }

    /**
     * Add Log For Message Queue
     *
     * @param int $messageId
     * @param Exception $errorBody
     * @return void
     */
    public function addLogForMessageQueue(int $messageId, Exception $errorBody)
    {
        $consentQueueLogModel = $this->consentQueueLog->create();
        $consentQueueLogModel->setData('message_id', $messageId);
        $consentQueueLogModel->setData('error_body', $errorBody);
        try {
            $this->consentQueueLogResource->save($consentQueueLogModel);
        } catch (Exception $exception) {
            $this->logger->critical($exception->getMessage());
        }
    }
}
