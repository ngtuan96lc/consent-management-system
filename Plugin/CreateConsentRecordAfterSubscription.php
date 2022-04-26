<?php

declare(strict_types=1);

namespace SoftLoft\ConsentManagementSystem\Plugin;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Newsletter\Model\Subscriber;
use Magento\Newsletter\Model\SubscriptionManagerInterface;
use SoftLoft\ConsentManagementSystem\Model\Queue\CreateConsentRecordHandler;

class CreateConsentRecordAfterSubscription
{
    /**
     * @var RequestInterface
     */
    protected RequestInterface $request;

    /**
     * @var PublisherInterface
     */
    protected PublisherInterface $publisher;

    /**
     * @var Json
     */
    protected Json $json;

    /**
     * @var CustomerRepositoryInterface
     */
    protected CustomerRepositoryInterface $customerRepository;

    /**
     * @param RequestInterface $request
     * @param PublisherInterface $publisher
     * @param Json $json
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        RequestInterface $request,
        PublisherInterface $publisher,
        Json $json,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->request = $request;
        $this->publisher = $publisher;
        $this->json = $json;
        $this->customerRepository = $customerRepository;
    }

    /**
     * Plugin After Subscribe Customer
     *
     * @param SubscriptionManagerInterface $subscription
     * @param Subscriber $result
     * @param int $customerId
     * @param int $storeId
     * @return mixed
     */
    public function afterSubscribeCustomer(
        SubscriptionManagerInterface $subscription,
        Subscriber $result,
        int $customerId,
        int $storeId
    ) {
        if ($this->request->getParam('checkbox-privacy-policy') == "on") {
            $data = [
                'email' => $result->getEmail(),
                'customerId' => $result->getCustomerId(),
                'consentPrivacyPolicy' => null
            ];
            $this->publisher->publish(
                CreateConsentRecordHandler::TOPIC_NAME_CREATE_CONSENT_RECORD,
                $this->json->serialize($data)
            );
        }
        return $result;
    }

    /**
     * Plugin After Subscribe
     *
     * @param SubscriptionManagerInterface $subscription
     * @param Subscriber $result
     * @param string $email
     * @param int $storeId
     * @return mixed
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function afterSubscribe(
        SubscriptionManagerInterface $subscription,
        Subscriber $result,
        string $email,
        int $storeId
    ) {
        if ($this->request->getParam('checkbox-privacy-policy') == "on") {
            $customer = $this->customerRepository->get($email, $storeId);
            $data = [
                'email' => $result->getEmail(),
                'customerId' => $customer->getId(),
                'consentPrivacyPolicy' => null
            ];
            $this->publisher->publish(
                CreateConsentRecordHandler::TOPIC_NAME_CREATE_CONSENT_RECORD,
                $this->json->serialize($data)
            );
        }
        return $result;
    }
}
