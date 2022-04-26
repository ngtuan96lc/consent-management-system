<?php

declare(strict_types=1);

namespace SoftLoft\ConsentManagementSystem\Plugin;

use Magento\Config\Block\System\Config\Form;
use Magento\Config\Model\Config\Structure;
use Magento\Framework\UrlInterface;
use SoftLoft\ConsentManagementSystem\Helper\ConfigHelper;

class FormConfigPlugin
{
    /**
     * @var Structure
     */
    protected Structure $structure;

    /**
     * @var UrlInterface
     */
    protected UrlInterface $urlBuilder;

    /**
     * @var ConfigHelper
     */
    protected ConfigHelper $configHelper;

    /**
     * @param Structure $structure
     * @param UrlInterface $urlBuilder
     * @param ConfigHelper $configHelper
     */
    public function __construct(
        Structure $structure,
        UrlInterface $urlBuilder,
        ConfigHelper $configHelper
    ) {
        $this->structure = $structure;
        $this->urlBuilder = $urlBuilder;
        $this->configHelper = $configHelper;
    }

    /**
     * Plugin After GetFormHtml
     *
     * @param Form $subject
     * @param string $result
     * @return string
     */
    public function afterGetFormHtml(Form $subject, string $result)
    {
        $html = '';
        if ($subject->getSectionCode() == 'consent_integration') {
            $url = $this->urlBuilder->getUrl('consent/version/update');
            $currentVersion = $this->configHelper->getCurrentVersion();
            $html = '<div class="consent-information" style="text-align: center;">';
            $html .= '<div><span id="consent-version"'
                . ' style="background-color: #e9fbdb; font-size: 16px; font-weight: 600; padding: 10px;
                 display: inline-block; margin: 5px 0;">'
                . __('Consent Privacy Policy Version: ') . $currentVersion
                . '</span></div>';
            $html .= '<div><span id="btn-update_consent-version"'
                . ' style="display: inline-block; padding: 10px; background-color: #1787e0; margin: 5px 0;">'
                . '<a href="' . $url . '" style="color: white; font-weight: 600;">' . __('Update Version') . '</a>'
                . ' </span></div>';
            $html .= '</div>';
        }
        return $html . $result;
    }
}
