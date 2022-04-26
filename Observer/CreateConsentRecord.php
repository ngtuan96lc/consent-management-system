<?php

declare(strict_types=1);

namespace SoftLoft\ConsentManagementSystem\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\Serializer\Json;
use SoftLoft\ConsentManagementSystem\Model\Queue\CreateConsentRecordHandler;

class CreateConsentRecord implements ObserverInterface
{
    /**
     * @var PublisherInterface
     */
    protected PublisherInterface $publisher;

    /**
     * @var Json
     */
    protected Json $json;

    /**
     * @param PublisherInterface $publisher
     * @param Json $json
     */
    public function __construct(
        PublisherInterface $publisher,
        Json $json
    ) {
        $this->publisher = $publisher;
        $this->json = $json;
    }

    /**
     * Perform push message queue if existing privacy-policy=on
     *
     * @param Observer $observer
     * @return $this|void
     */
    public function execute(Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();
        $request = $observer->getEvent()->getAccountController();
        if ($request->getRequest()->getParam('privacy-policy') == "on") {
            $data = [
                'email' => $customer->getEmail(),
                'customerId' => $customer->getId(),
                'consentPrivacyPolicy' => null
            ];
            $this->publisher->publish(
                CreateConsentRecordHandler::TOPIC_NAME_CREATE_CONSENT_RECORD,
                $this->json->serialize($data)
            );
        }
        return $this;
    }
}
