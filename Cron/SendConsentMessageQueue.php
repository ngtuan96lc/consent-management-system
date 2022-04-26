<?php

declare(strict_types=1);

namespace SoftLoft\ConsentManagementSystem\Cron;

use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\Serializer\Json;
use SoftLoft\ConsentManagementSystem\Helper\ConfigHelper;
use SoftLoft\ConsentManagementSystem\Model\Queue\CreateConsentRecordHandler;
use SoftLoft\ConsentManagementSystem\Model\ResourceModel\ConsentQueue;
use SoftLoft\ConsentManagementSystem\Model\ResourceModel\ConsentQueue\ConsentQueueCollectionFactory;

class SendConsentMessageQueue
{
    /**
     * @var PublisherInterface
     */
    protected PublisherInterface $publisher;

    /**
     * @var ConsentQueueCollectionFactory
     */
    protected ConsentQueueCollectionFactory $consentQueueCollection;

    /**
     * @var Json
     */
    protected Json $json;

    /**
     * @var ConsentQueue
     */
    protected ConsentQueue $consentQueueResource;

    /**
     * @var ConfigHelper
     */
    protected ConfigHelper $configHelper;

    /**
     * @param PublisherInterface $publisher
     * @param ConsentQueueCollectionFactory $consentQueueCollection
     * @param ConsentQueue $consentQueueResource
     * @param Json $json
     * @param ConfigHelper $configHelper
     */
    public function __construct(
        PublisherInterface $publisher,
        ConsentQueueCollectionFactory $consentQueueCollection,
        ConsentQueue $consentQueueResource,
        Json $json,
        ConfigHelper $configHelper
    ) {
        $this->publisher = $publisher;
        $this->consentQueueCollection = $consentQueueCollection;
        $this->json = $json;
        $this->consentQueueResource = $consentQueueResource;
        $this->configHelper = $configHelper;
    }

    /**
     * Send Consent Message Queue Via Cron
     *
     * @return void
     */
    public function execute()
    {
        $queueCollection = $this->consentQueueCollection->create()
            ->addFieldToFilter('status', 0);
        if ($queueCollection->count()) {
            foreach ($queueCollection->getItems() as $queue) {
                if ($queue->getData('tries') <= $this->configHelper->getXTrials()) {
                    $data = $this->json->unserialize($queue->getBody());
                    $data['message_id'] = $queue->getId();
                    $this->publisher->publish(
                        CreateConsentRecordHandler::TOPIC_NAME_CREATE_CONSENT_RECORD,
                        $this->json->serialize($data)
                    );
                }
            }
        }
    }
}
