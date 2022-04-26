<?php

declare(strict_types=1);

namespace SoftLoft\ConsentManagementSystem\Controller\Adminhtml\Version;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Controller\ResultFactory;
use SoftLoft\ConsentManagementSystem\Cron\UpdateVersionConsent;

class Update extends Action implements HttpGetActionInterface
{
    /**
     * @var UpdateVersionConsent
     */
    protected UpdateVersionConsent $updateVersionConsent;

    /**
     * @var RedirectInterface
     */
    protected RedirectInterface $redirect;

    /**
     * @param Context $context
     * @param UpdateVersionConsent $updateVersionConsent
     * @param RedirectInterface $redirect
     */
    public function __construct(
        Context $context,
        UpdateVersionConsent $updateVersionConsent,
        RedirectInterface $redirect
    ) {
        parent::__construct($context);
        $this->updateVersionConsent = $updateVersionConsent;
        $this->redirect = $redirect;
    }

    /**
     * Update Version Consent
     *
     * @return Redirect
     */
    public function execute()
    {
        $process = $this->updateVersionConsent->execute();
        if ($process) {
            $this->messageManager->addSuccessMessage(__('You updated the consent privacy policy.'));
        } else {
            $this->messageManager->addSuccessMessage(__('Something went wrong, please try again.'));

        }

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->redirect->getRefererUrl());
        return $resultRedirect;
    }
}
