<?php

declare(strict_types=1);

namespace SoftLoft\ConsentManagementSystem\Helper;

use Exception;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Model\PageFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\FlagManager;
use Magento\Store\Model\ScopeInterface;
use Magento\Variable\Model\ResourceModel\Variable\CollectionFactory;
use Magento\Variable\Model\Variable;
use Magento\Variable\Model\VariableFactory;
use Psr\Log\LoggerInterface;
use SoftLoft\ConsentManagementSystem\Api\ConsentRequestInterface;

class ConfigHelper extends AbstractHelper
{
    public const PATH_CONSENT_GENERAL_API_ENDPOINT = 'consent_integration/general/api_endpoint';
    public const PATH_CONSENT_GENERAL_API_KEY = 'consent_integration/general/api_key';
    public const PATH_CONSENT_GENERAL_CHANNEL = 'consent_integration/general/channel';
    public const PATH_CONSENT_GENERAL_PARTNER = 'consent_integration/general/partner';
    public const PATH_CONSENT_GENERAL_BRAND = 'consent_integration/general/brand';
    public const PATH_CONSENT_GENERAL_X_TRIALS = 'consent_integration/general/x_trials';

    public const PRIVACY_POLICY_CMS_PAGE_IDENTIFIER = 'privacy-policy-cms-page';
    public const PRIVACY_POLICY_CMS_PAGE_TITLE = 'Privacy & Policy CMS Page';

    /**
     * @var CollectionFactory
     */
    public CollectionFactory $variableCollection;

    /**
     * @var VariableFactory
     */
    public VariableFactory $variableFactory;

    /**
     * @var \Magento\Variable\Model\ResourceModel\Variable
     */
    public \Magento\Variable\Model\ResourceModel\Variable $variableResource;

    /**
     * @var LoggerInterface
     */
    public LoggerInterface $logger;

    /**
     * @var FlagManager
     */
    public FlagManager $flagManager;

    /**
     * @var PageFactory
     */
    public PageFactory $pageFactory;

    /**
     * @var PageRepositoryInterface
     */
    public PageRepositoryInterface $pageRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    public SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param CollectionFactory $variableCollection
     * @param VariableFactory $variableFactory
     * @param \Magento\Variable\Model\ResourceModel\Variable $variableResource
     * @param LoggerInterface $logger
     * @param FlagManager $flagManager
     * @param PageFactory $pageFactory
     * @param PageRepositoryInterface $pageRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        CollectionFactory $variableCollection,
        VariableFactory $variableFactory,
        \Magento\Variable\Model\ResourceModel\Variable $variableResource,
        LoggerInterface $logger,
        FlagManager $flagManager,
        PageFactory $pageFactory,
        PageRepositoryInterface $pageRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        parent::__construct($context);
        $this->scopeConfig = $scopeConfig;
        $this->variableCollection = $variableCollection;
        $this->variableFactory = $variableFactory;
        $this->variableResource = $variableResource;
        $this->logger = $logger;
        $this->flagManager = $flagManager;
        $this->pageFactory = $pageFactory;
        $this->pageRepository = $pageRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Get Api Endpoint Default Value
     *
     * @return mixed
     */
    public function getApiEndpoint()
    {
        return $this->scopeConfig->getValue(
            self::PATH_CONSENT_GENERAL_API_ENDPOINT,
            ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * Get ApiKey Default Value
     *
     * @return mixed
     */
    public function getApiKey()
    {
        return $this->scopeConfig->getValue(
            self::PATH_CONSENT_GENERAL_API_KEY,
            ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * Get Partner Default Value
     *
     * @return mixed
     */
    public function getPartner()
    {
        return $this->scopeConfig->getValue(
            self::PATH_CONSENT_GENERAL_PARTNER,
            ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * Get Brand Default Value
     *
     * @return mixed
     */
    public function getBrand()
    {
        return $this->scopeConfig->getValue(
            self::PATH_CONSENT_GENERAL_BRAND,
            ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * Get Channel Default Value
     *
     * @return mixed
     */
    public function getChannel()
    {
        return $this->scopeConfig->getValue(
            self::PATH_CONSENT_GENERAL_CHANNEL,
            ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * Get Times Of Retry Default Value
     *
     * @return mixed
     */
    public function getXTrials()
    {
        return $this->scopeConfig->getValue(
            self::PATH_CONSENT_GENERAL_X_TRIALS,
            ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * Get Current Consent Version
     *
     * @return array|bool|float|int|string|null
     */
    public function getCurrentVersion()
    {
        return $this->flagManager->getFlagData(ConsentRequestInterface::FLAG_CONSENT_VERSION);
    }

    /**
     * Validate Response Consent
     *
     * @param array $response
     * @return bool
     */
    public function validateResponseConsent(array $response): bool
    {
        if (isset($response['message']) || (isset($response['status']) && isset($response['error']))) {
            return false;
        }
        return true;
    }

    /**
     * Get Custom Variables Collection
     *
     * @param string $variables
     * @return mixed
     */
    public function getCustomVariables(string $variables)
    {
        $variableCollection = $this->variableCollection->create();
        $variableCollection
            ->addFieldToSelect('*')
            ->addFieldToFilter('code', ['in' => $variables]);
        $variableCollection->getSelect()
            ->join(
                ['vv' => $variableCollection->getTable('variable_value')],
                'main_table.variable_id = vv.variable_id'
            );
        return $variableCollection;
    }

    /**
     * Create Custom Variable
     *
     * @param string $variable
     * @param string $content
     * @return void
     */
    public function createCustomVariable(string $variable, string $content)
    {
        $variableModel = $this->variableFactory->create();
        $data = [
            'code' => $variable,
            'name' => $variable,
            'html_value' => $content,
            'plain_value' => $content
        ];
        $variableModel->setData($data);
        try {
            $this->variableResource->save($variableModel);
        } catch (Exception $exception) {
            $this->logger->alert($exception->getMessage());
        }
    }

    /**
     * Update Custom Variable
     *
     * @param Variable $model
     * @param string $content
     * @return void
     */
    public function updateCustomVariable(Variable $model, string $content)
    {
        $model->setData('html_value', $content);
        $model->setData('plain_value', $content);
        try {
            $this->variableResource->save($model);
        } catch (Exception $exception) {
            $this->logger->critical($exception->getMessage());
        }
    }

    /**
     * Get CMS Page By Identifier
     *
     * @param string $identifier
     * @return array|void
     */
    public function getCmsPageByIdentifier(string $identifier)
    {
        try {
            $result = [];
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('identifier', $identifier)
                ->create();
            $pages = $this->pageRepository->getList($searchCriteria);
            if ($pages->getTotalCount() > 0) {
                foreach ($pages->getItems() as $page) {
                    $result[$page->getStoreCode()] = $page->getContent();
                }
            }
            return $result;
        } catch (LocalizedException $localizedException) {
            $this->logger->critical($localizedException->getMessage());
        }
    }
}
