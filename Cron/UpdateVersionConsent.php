<?php

declare(strict_types=1);

namespace SoftLoft\ConsentManagementSystem\Cron;

use Magento\Cms\Model\PageFactory;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\FlagManager;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\UrlRewrite\Model\ResourceModel\UrlRewrite;
use Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollectionFactory;
use Magento\Variable\Model\ResourceModel\Variable\Collection;
use Psr\Log\LoggerInterface;
use SoftLoft\ConsentManagementSystem\Api\ConsentRequestInterface;
use SoftLoft\ConsentManagementSystem\Helper\ConfigHelper;

class UpdateVersionConsent
{
    /**
     * @var ConfigHelper
     */
    protected ConfigHelper $configHelper;

    /**
     * @var ConsentRequestInterface
     */
    protected ConsentRequestInterface $consentRequest;

    /**
     * @var FlagManager
     */
    protected FlagManager $flagManager;

    /**
     * @var Json
     */
    protected Json $json;

    /**
     * @var PageFactory
     */
    protected PageFactory $pageFactory;

    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * @var StoreRepositoryInterface
     */
    protected StoreRepositoryInterface $storeRepository;

    /**
     * @var PageRepositoryInterface
     */
    protected PageRepositoryInterface $pageRepository;

    /**
     * @var UrlRewriteCollectionFactory
     */
    protected UrlRewriteCollectionFactory $urlRewriteCollection;

    /**
     * @var UrlRewrite
     */
    protected UrlRewrite $urlRewrite;

    /**
     * @param ConsentRequestInterface $consentRequest
     * @param ConfigHelper $configHelper
     * @param FlagManager $flagManager
     * @param Json $json
     * @param PageFactory $pageFactory
     * @param LoggerInterface $logger
     * @param StoreRepositoryInterface $storeRepository
     * @param PageRepositoryInterface $pageRepository
     * @param UrlRewriteCollectionFactory $urlRewriteCollection
     * @param UrlRewrite $urlRewrite
     */
    public function __construct(
        ConsentRequestInterface $consentRequest,
        ConfigHelper $configHelper,
        FlagManager $flagManager,
        Json $json,
        PageFactory $pageFactory,
        LoggerInterface $logger,
        StoreRepositoryInterface $storeRepository,
        PageRepositoryInterface $pageRepository,
        UrlRewriteCollectionFactory $urlRewriteCollection,
        UrlRewrite $urlRewrite
    ) {
        $this->configHelper = $configHelper;
        $this->consentRequest = $consentRequest;
        $this->flagManager = $flagManager;
        $this->json = $json;
        $this->pageFactory = $pageFactory;
        $this->logger = $logger;
        $this->storeRepository = $storeRepository;
        $this->pageRepository = $pageRepository;
        $this->urlRewriteCollection = $urlRewriteCollection;
        $this->urlRewrite = $urlRewrite;
    }

    /**
     * Update Consent Version Via Cron
     *
     * @return bool
     */
    public function execute()
    {
        $oldVersion = $this->configHelper->getCurrentVersion();
        try {
            $response = $this->consentRequest->getConsentInformation();
        } catch (\Exception $e) {
            return false;
        }
        $response = $this->json->unserialize($response);
        $flag = $this->configHelper->validateResponseConsent($response);
        if (!$flag) {
            return false;
        }
        if (!$oldVersion
            || (isset($response['consent_privacy_version'])
            && $oldVersion < $response['consent_privacy_version'])
        ) {
            $this->updateVersion($response['consent_privacy_version']);
            if (isset($response['marketing_display_text'])) {
                $this->updateConsent(
                    ConsentRequestInterface::CUSTOM_VARIABLE_MARKETING_DISPLAY_TEXT,
                    is_array($response['marketing_display_text'])
                        ? $this->json->serialize($response['marketing_display_text'])
                        : $response['marketing_display_text']
                );
            }
            if (isset($response['privacy_policy'])) {
                foreach ($response['privacy_policy'] as $storeCode => $content) {
                    try {
                        $store = $this->storeRepository->get($storeCode);
                        $this->updateCmsPage((int)$store->getId(), $content);
                    } catch (NoSuchEntityException $noSuchEntityException) {
                        $this->logger->critical($noSuchEntityException->getMessage());
                    }
                }
            }
        }
        return true;
    }

    /**
     * Update Consent Version
     *
     * @param string $version
     * @return void
     */
    protected function updateVersion(string $version)
    {
        $this->flagManager->saveFlag(ConsentRequestInterface::FLAG_CONSENT_VERSION, $version);
    }

    /**
     * Update Consent
     *
     * @param string $variableName
     * @param string $content
     * @return void
     */
    protected function updateConsent(string $variableName, string $content)
    {
        /** @var Collection $variableCollection */
        $variableCollection = $this->configHelper->getCustomVariables($variableName);
        if ($variableCollection->count()) {
            $this->configHelper->updateCustomVariable($variableCollection->getFirstItem(), $content);
        } else {
            $this->configHelper->createCustomVariable($variableName, $content);
        }
    }

    /**
     * Update Cms Page
     *
     * @param int $storeId
     * @param string $content
     * @return void
     */
    protected function updateCmsPage(int $storeId, string $content)
    {
        try {
            $page = $this->pageFactory->create();
            $pageId = $page->checkIdentifier(ConfigHelper::PRIVACY_POLICY_CMS_PAGE_IDENTIFIER, $storeId);
            if ($pageId > 0) {
                $page = $this->pageRepository->getById($pageId);
                $page->setContent($content);
            } else {
                $this->removeUrlKeyIfExist($storeId);
                $page->setTitle(ConfigHelper::PRIVACY_POLICY_CMS_PAGE_TITLE)
                    ->setIdentifier(ConfigHelper::PRIVACY_POLICY_CMS_PAGE_IDENTIFIER)
                    ->setIsActive(true)
                    ->setPageLayout('1column')
                    ->setStores([$storeId])
                    ->setContent($content);
            }
            $this->pageRepository->save($page);
        } catch (LocalizedException $localizedException) {
            $this->logger->critical($localizedException->getMessage());
        }
    }

    /**
     * Remove UrlKey If Exist
     *
     * @param int $storeIds
     * @return void
     */
    protected function removeUrlKeyIfExist(int $storeIds)
    {
        $urlCollection = $this->urlRewriteCollection->create()
            ->addFieldToFilter('store_id', ['in' => $storeIds])
            ->addFieldToFilter('entity_type', 'cms-page')
            ->addFieldToFilter('request_path', ConfigHelper::PRIVACY_POLICY_CMS_PAGE_IDENTIFIER);

        if ($urlCollection->count()) {
            foreach ($urlCollection->getItems() as $item) {
                try {
                    $this->urlRewrite->delete($item);
                } catch (\Exception $exception) {
                    $this->logger->critical($exception->getMessage());
                }
            }
        }
    }
}
