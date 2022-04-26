<?php

namespace SoftLoft\ConsentManagementSystem\Controller\Ajax;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use SoftLoft\ConsentManagementSystem\Api\ConsentRequestInterface;

class CheckCustomerInformation implements ActionInterface
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
     * @param ConsentRequestInterface $consentRequest
     * @param ResultFactory $resultFactory
     * @param RequestInterface $request
     * @param \Magento\Framework\Serialize\Serializer\Json $json
     */
    public function __construct(
        ConsentRequestInterface $consentRequest,
        ResultFactory $resultFactory,
        RequestInterface $request,
        \Magento\Framework\Serialize\Serializer\Json $json
    ) {
        $this->consentRequest = $consentRequest;
        $this->resultFactory = $resultFactory;
        $this->request = $request;
        $this->json = $json;
    }

    /**
     * Check Customer Information
     *
     * @return ResponseInterface|Json|ResultInterface
     */
    public function execute()
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        $email = $this->request->getParam('email');
        $mobile = $this->request->getParam('mobile');
        if (!$email && !$mobile) {
            return $result->setData([
                'status' => 400,
                'message' => __('Require a email or mobile')
            ]);
        }

        $refId = $this->request->getParam('ref_id');
        if (!$refId) {
            return $result->setData([
                'status' => 400,
                'message' => __('Missing required data of field ref_id')
            ]);
        }

        try {
            $response = $this->consentRequest->checkCustomerConsent($refId, $email, $mobile);
            $result->setData($this->json->unserialize($response));
        } catch (\Exception $e) {
            $result->setData([
                'status' => 400,
                'message' => __('Something went wrong, please try again.')
            ]);
        }
        return $result;
    }
}
