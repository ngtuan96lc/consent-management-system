<?php

declare(strict_types=1);

namespace SoftLoft\ConsentManagementSystem\Model;

use Exception;
use GuzzleHttp\ClientFactory;
use GuzzleHttp\Psr7\ResponseFactory;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;
use SoftLoft\ConsentManagementSystem\Api\ConsentRequestInterface;
use Magento\Framework\Webapi\Rest\Request;
use SoftLoft\ConsentManagementSystem\Helper\ConfigHelper;

class ConsentRequest implements ConsentRequestInterface
{
    /**
     * @var ClientFactory
     */
    protected ClientFactory $clientFactory;

    /**
     * @var ResponseFactory
     */
    protected ResponseFactory $responseFactory;

    /**
     * @var ConfigHelper
     */
    protected ConfigHelper $configHelper;

    /**
     * @var Json
     */
    protected Json $json;

    /**
     * @var Curl
     */
    protected Curl $curl;

    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * @param ClientFactory $clientFactory
     * @param ResponseFactory $responseFactory
     * @param ConfigHelper $configHelper
     * @param Json $json
     * @param Curl $curl
     * @param LoggerInterface $logger
     */
    public function __construct(
        ClientFactory $clientFactory,
        ResponseFactory $responseFactory,
        ConfigHelper $configHelper,
        Json $json,
        Curl $curl,
        LoggerInterface $logger
    ) {
        $this->clientFactory = $clientFactory;
        $this->responseFactory = $responseFactory;
        $this->configHelper = $configHelper;
        $this->json = $json;
        $this->curl = $curl;
        $this->logger = $logger;
    }

    /**
     * Send Request
     *
     * @param string $url
     * @param string $method
     * @param array $header
     * @param array $options
     * @return mixed|string
     * @throws Exception
     */
    public function sendRequest(string $url, string $method, array $header, array $options = [])
    {
        try {
            $this->logger->info('Request params ', ['body' => $options]);
            $this->curl->setHeaders($header);
            if ($method == 'POST') {
                $this->curl->post($url, $this->json->serialize($options));
            }
            if ($method == 'GET') {
                $this->curl->get($url);
            }
            $response = $this->curl->getBody();
            $responseJson = $this->json->unserialize($response);

            if ($this->configHelper->validateResponseConsent($this->json->unserialize($response))) {
                $this->logger->info(sprintf('Success {%s} %s', $method, $url), $responseJson);
            } else {
                $this->logger->error("Unable call api", ['response' => $responseJson]);
            }
        } catch (Exception $exception) {
            $this->logger->error(
                sprintf(
                    'Exception {%s} %s',
                    $method,
                    $url,
                ),
                ['exception' => $exception]
            );
            throw $exception;
        }
        return $response;
    }

    /**
     * Get Consent Information
     *
     * @return mixed|string
     * @throws Exception
     */
    public function getConsentInformation()
    {
        $endPoint = $this->configHelper->getApiEndpoint() . ConsentRequestInterface::URI_CONSENT_INFORMATION;
        $params = [
            'channel=' . $this->configHelper->getChannel(),
            'partner=' . $this->configHelper->getPartner(),
            'brand=' . $this->configHelper->getBrand()
        ];
        $url = $endPoint . '?' . implode('&', $params);
        $header = [
            'x-api-key' => $this->configHelper->getApiKey(),
            'Content-Type' => 'application/json'
        ];
        return $this->sendRequest($url, Request::HTTP_METHOD_GET, $header);
    }

    /**
     * Create Consent Record
     *
     * @param string $email
     * @param int $customerId
     * @param string $consentPrivacyVersion
     * @return mixed|string
     * @throws Exception
     */
    public function createConsentRecord(string $email, int $customerId, string $consentPrivacyVersion)
    {
        $url = $this->configHelper->getApiEndpoint() . ConsentRequestInterface::URI_CONSENT_CREATE;
        $options = [
            'email' => $email,
            'ref_id' => $customerId,
            'channel' => $this->configHelper->getChannel(),
            'partner' => $this->configHelper->getPartner(),
            'brand' => $this->configHelper->getBrand(),
            'consent_privacy_version' => $consentPrivacyVersion ?? $this->configHelper->getCurrentVersion(),
            'consent_privacy_status' => true,
            'consent_marketing_status' => true
        ];
        $header = [
            'x-api-key' => $this->configHelper->getApiKey(),
            'Content-Type' => 'application/json'
        ];
        return $this->sendRequest($url, Request::HTTP_METHOD_POST, $header, $options);
    }

    /**
     * Check Customer Consent
     *
     * @param int $refId
     * @param string $email
     * @param string $mobile
     * @return mixed|string
     * @throws Exception
     */
    public function checkCustomerConsent(int $refId, string $email, string $mobile)
    {
        $url = $this->configHelper->getApiEndpoint() . ConsentRequestInterface::URI_CONSENT_CHECK;
        $options = [
            'partner' => $this->configHelper->getPartner(),
            'brand' => $this->configHelper->getBrand(),
            'channel' => $this->configHelper->getChannel(),
            'ref_id' => $refId
        ];
        if ($email) {
            $options['email'] = $email;
        } else {
            $options['mobile'] = $mobile;
        }
        $header = [
            'x-api-key' => $this->configHelper->getApiKey(),
            'Content-Type' => 'application/json'
        ];
        return $this->sendRequest($url, Request::HTTP_METHOD_POST, $header, $options);
    }
}
