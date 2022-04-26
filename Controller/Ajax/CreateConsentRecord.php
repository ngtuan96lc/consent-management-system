<?php

namespace SoftLoft\ConsentManagementSystem\Controller\Ajax;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use SoftLoft\ConsentManagementSystem\Api\ConsentRequestInterface;
use SoftLoft\ConsentManagementSystem\Model\Queue\CreateConsentRecordHandler;

class CreateConsentRecord implements ActionInterface
{
    /**
     * @var ConsentRequestInterface
     */
    protected ConsentRequestInterface $consentRequest;

    /**
     * @var ResultFactory
     */
    protected ResultFactory $resultFactory;

    /**
     * @var RequestInterface
     */
    protected RequestInterface $request;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected \Magento\Framework\Serialize\Serializer\Json $json;

    /**
     * @var PublisherInterface
     */
    protected PublisherInterface $publisher;

    /**
     * @param ConsentRequestInterface $consentRequest
     * @param ResultFactory $resultFactory
     * @param RequestInterface $request
     * @param \Magento\Framework\Serialize\Serializer\Json $json
     * @param PublisherInterface $publisher
     */
    public function __construct(
        ConsentRequestInterface $consentRequest,
        ResultFactory $resultFactory,
        RequestInterface $request,
        \Magento\Framework\Serialize\Serializer\Json $json,
        PublisherInterface $publisher
    ) {
        $this->consentRequest = $consentRequest;
        $this->resultFactory = $resultFactory;
        $this->request = $request;
        $this->json = $json;
        $this->publisher = $publisher;
    }

    /**
     * Controller Create Consent Record
     *
     * @return ResponseInterface|Json|ResultInterface
     */
    public function execute()
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        $email = $this->request->getParam('email');
        $consentPrivacyVersion = $this->request->getParam('consent_privacy_version');
        $refId = $this->request->getParam('ref_id');
        if (!$email && !$consentPrivacyVersion && !$refId) {
            return $result->setData([
                'status' => 400,
                'message' => __('Missing required data of fields')
            ]);
        }
        $data = [
            'email' => $email,
            'customerId' => $refId,
            'consentPrivacyPolicy' => $consentPrivacyVersion
        ];
        $this->publisher->publish(
            CreateConsentRecordHandler::TOPIC_NAME_CREATE_CONSENT_RECORD,
            $this->json->serialize($data)
        );
        return $result->setData([
            'status' => 200,
            'message' => __("Message is added to queue, wait to create new consent record")
        ]);
    }
}
