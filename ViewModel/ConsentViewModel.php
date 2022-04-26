<?php

declare(strict_types=1);

namespace SoftLoft\ConsentManagementSystem\ViewModel;

use Magento\Cms\Model\PageFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;
use SoftLoft\ConsentManagementSystem\Api\ConsentRequestInterface;
use SoftLoft\ConsentManagementSystem\Helper\ConfigHelper;

class ConsentViewModel implements ArgumentInterface
{
    /**
     * @var ConfigHelper
     */
    public ConfigHelper $configHelper;

    /**
     * @var Json
     */
    public Json $json;

    /**
     * @var StoreManagerInterface
     */
    public StoreManagerInterface $storeManager;

    /**
     * @var PageFactory
     */
    public PageFactory $pageFactory;

    /**
     * @var UrlInterface
     */
    public UrlInterface $urlBuilder;

    /**
     * @param ConfigHelper $configHelper
     * @param Json $json
     * @param StoreManagerInterface $storeManager
     * @param PageFactory $pageFactory
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        ConfigHelper $configHelper,
        Json $json,
        StoreManagerInterface $storeManager,
        PageFactory $pageFactory,
        UrlInterface $urlBuilder
    ) {
        $this->configHelper = $configHelper;
        $this->json = $json;
        $this->storeManager = $storeManager;
        $this->pageFactory = $pageFactory;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Get Marketing Display Text
     *
     * @return mixed|null
     * @throws NoSuchEntityException
     */
    public function getMarketingDisplayText()
    {
        $result = null;
        $collection = $this->configHelper->getCustomVariables(
            ConsentRequestInterface::CUSTOM_VARIABLE_MARKETING_DISPLAY_TEXT
        );
        if ($collection->count()) {
            $variableModel = $collection->getFirstItem();
            $value = $this->json->unserialize($variableModel->getValue());
            $currentStoreCode = $this->storeManager->getStore()->getCode();
            if (isset($value[$currentStoreCode])) {
                $result = $value[$currentStoreCode];
            }
        }
        return $result;
    }

    /**
     * Get Url Privacy Policy Cms Page
     *
     * @return string|null
     * @throws NoSuchEntityException
     */
    public function getUrlPrivacyPolicyCmsPage()
    {
        $url = null;
        $currentStoreId = $this->storeManager->getStore()->getId();
        $page = $this->pageFactory->create();
        $pageId = $page->checkIdentifier(
            ConfigHelper::PRIVACY_POLICY_CMS_PAGE_IDENTIFIER,
            $currentStoreId
        );
        if ($pageId > 0) {
            $url = $this->urlBuilder->getUrl(
                null,
                ['_direct' => ConfigHelper::PRIVACY_POLICY_CMS_PAGE_IDENTIFIER]
            );
        }
        return $url;
    }
}
