<?php

namespace Coddict\BingAdsApiBundle\Service\Factory;

use Coddict\BingAdsApiBundle\CustomerManagement;
use Coddict\BingAdsApiBundle\Service\Authentication\Auth;
use Coddict\BingAdsApiBundle\Service\Bulk\BulkHelper;
use Coddict\BingAdsApiBundle\Service\Customer\CustomerListHelper;
use Microsoft\BingAds\Auth\AuthorizationData;
use Microsoft\BingAds\Auth\OAuthDesktopMobileAuthCodeGrant;
use Microsoft\BingAds\Auth\ServiceClient;
use Psr\Log\LoggerInterface;

class ServiceClientFactory
{
    private AuthorizationData $authorizationData;

    private ServiceClient $serviceClient;

    public function __construct(
        private readonly array  $config,
        private readonly string $uploadDirectory,
        private readonly LoggerInterface $logger
    ) {
        $authentication = (new OAuthDesktopMobileAuthCodeGrant())
            ->withEnvironment($this->config['api_environment'])
            ->withClientId($this->config['client_id'])
            ->withClientSecret($this->config['client_secret'])
            ->withOAuthScope($this->config['oauth_scope']);

        $this->authorizationData = new AuthorizationData();
        $this->authorizationData->withAuthentication($authentication);
        $this->authorizationData->withDeveloperToken($this->config['developer_token']);

        $this->serviceClient = new ServiceClient(
            'CustomerManagementVersion13',
            $this->authorizationData,
            'Production'
        );
    }

    public function createServiceClient(
    ): ServiceClient {
        return $this->serviceClient;
    }

    public function createCustomerListHelper(): CustomerListHelper
    {
        $auth = new Auth(
            $this->config,
            $this->authorizationData,
            new CustomerManagement(
                $this->serviceClient,
                $this->authorizationData
            ),
            $this->serviceClient, $this->serviceClient);

        return new CustomerListHelper(
            $this->uploadDirectory,
            $auth,
            $this->authorizationData,
            new BulkHelper(
                $this->authorizationData,
                $this->serviceClient
            ),
            $this->logger
        );
    }
}