<?php

declare(strict_types=1);

namespace SoftLoft\ConsentManagementSystem\Api;

/**
 * @api
 */
interface ConsentRequestInterface
{
    public const URI_CONSENT_INFORMATION = 'consent_info';
    public const URI_CONSENT_CREATE = 'consent';
    public const URI_CONSENT_CHECK = 'check_consent_info';
    public const FLAG_CONSENT_VERSION = 'consent_version';
    public const CUSTOM_VARIABLE_MARKETING_DISPLAY_TEXT = 'marketing_display_text';

    /**
     * Send Request
     *
     * @param string $url
     * @param string $method
     * @param array $header
     * @param array $options
     * @return mixed
     */
    public function sendRequest(string $url, string $method, array $header, array $options = []);

    /**
     * Api Get Information
     *
     * @return mixed
     */
    public function getConsentInformation();

    /**
     * Api Create Consent Record
     *
     * @param string $email
     * @param int $customerId
     * @param string $consentPrivacyVersion
     * @return mixed
     */
    public function createConsentRecord(string $email, int $customerId, string $consentPrivacyVersion);

    /**
     * Api Check Customer Consent
     *
     * @param int $refId
     * @param string $email
     * @param string $mobile
     * @return mixed
     */
    public function checkCustomerConsent(int $refId, string $email, string $mobile);
}
