<?php

declare(strict_types=1);

namespace SoftLoft\ConsentManagementSystem\Controller\Ajax;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use SoftLoft\ConsentManagementSystem\Api\ConsentRequestInterface;
use SoftLoft\ConsentManagementSystem\Helper\ConfigHelper;

class GetConsentInformation implements ActionInterface
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
     * @var ConfigHelper
     */
    protected ConfigHelper $configHelper;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected \Magento\Framework\Serialize\Serializer\Json $json;

    /**
     * @var StoreRepositoryInterface
     */
    protected StoreRepositoryInterface $storeRepository;

    /**
     * @param ConsentRequestInterface $consentRequest
     * @param ResultFactory $resultFactory
     * @param ConfigHelper $configHelper
     * @param \Magento\Framework\Serialize\Serializer\Json $json
     * @param StoreRepositoryInterface $storeRepository
     */
    public function __construct(
        ConsentRequestInterface $consentRequest,
        ResultFactory $resultFactory,
        ConfigHelper $configHelper,
        \Magento\Framework\Serialize\Serializer\Json $json,
        StoreRepositoryInterface $storeRepository
    ) {
        $this->consentRequest = $consentRequest;
        $this->resultFactory = $resultFactory;
        $this->configHelper = $configHelper;
        $this->json = $json;
        $this->storeRepository = $storeRepository;
    }

    /**
     * Get Consent Information, if already exists then get from existing value
     *
     * @return ResponseInterface|Json|ResultInterface
     */
    public function execute()
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $currentVersion = $this->configHelper->getCurrentVersion();
        $marketingDisplayText = $this->configHelper->getCustomVariables(
            ConsentRequestInterface::CUSTOM_VARIABLE_MARKETING_DISPLAY_TEXT
        );
        $privacyPolicies = $this->configHelper->getCmsPageByIdentifier(
            ConfigHelper::PRIVACY_POLICY_CMS_PAGE_IDENTIFIER
        );
        if ($currentVersion && $marketingDisplayText->count() && count($privacyPolicies)) {
            $response = [
                'marketing_display_text' => $this->json->unserialize($marketingDisplayText->getFirstItem()->getValue()),
                'privacy_policy' => $privacyPolicies,
                'consent_privacy_version' => $currentVersion,
                'list_store' => $this->getStoresData()
            ];
            return $result->setData($response);
        } else {
            $response = $this->consentRequest->getConsentInformation();
            return $result->setData($this->json->unserialize($response));
        }
    }

    /**
     * Get store list
     *
     * @return array
     */
    protected function getStoresData(): array
    {
        $result = [];
        $listStore = $this->storeRepository->getList();
        foreach ($listStore as $store) {
            $result[$store->getId()] = $store->getCode();
        }
        return $result;
    }
}
