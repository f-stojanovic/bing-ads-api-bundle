<?php

namespace Coddict\BingAdsApiBundle\Service\Factory;

use Coddict\BingAdsApiBundle\Exception\RefreshTokenNotFoundException;
use Coddict\BingAdsApiBundle\Service\Authentication\Auth;
use Coddict\BingAdsApiBundle\CustomerManagement;
use Coddict\BingAdsApiBundle\Service\Bulk\BulkHelper;
use Coddict\BingAdsApiBundle\Service\Customer\CustomerListHelper;
use Microsoft\BingAds\Auth\AuthorizationData;
use Microsoft\BingAds\Auth\OAuthDesktopMobileAuthCodeGrant;
use Microsoft\BingAds\Auth\OAuthTokenRequestException;
use Microsoft\BingAds\Auth\ServiceClient;
use Microsoft\BingAds\Auth\ServiceClientType;
use Psr\Log\LoggerInterface;

class ServiceClientFactory
{
    private AuthorizationData $authorizationData;
    private ServiceClient $serviceClient;

    public function __construct(
        private readonly array  $config,
        private readonly LoggerInterface $logger,
        private readonly BingTokenIO $io
    ) {
        $this->authenticateWithOAuth();
        $this->createServiceClient();
    }

    private function createServiceClient(): void
    {
        $this->serviceClient = new ServiceClient(
            ServiceClientType::CustomerManagementVersion13,
            $this->authorizationData,
            'Production'
        );
    }

    public function createServiceClientInstance(): ServiceClient
    {
        return $this->serviceClient;
    }

    public function createCustomerListHelper(): CustomerListHelper
    {
        $auth = $this->createAuthInstance();
        $bulkHelper = $this->createBulkHelperInstance();

        return new CustomerListHelper(
            $auth,
            $this->authorizationData,
            $bulkHelper,
            $this->logger
        );
    }

    private function createAuthInstance(): Auth
    {
        return new Auth(
            $this->config,
            $this->authorizationData,
            new CustomerManagement(
                $this->serviceClient,
                $this->authorizationData
            ),
            $this->serviceClient
        );
    }

    private function createBulkHelperInstance(): BulkHelper
    {
        return new BulkHelper(
            $this->authorizationData
        );
    }

    /**
     * You need to provide consent for the application to access your Microsoft Advertising accounts
     * Copy and paste this authorization endpoint into a web browser and sign in with a Microsoft account
     * with access to a Microsoft Advertising account: $this->authorizationData->Authentication->getAuthorizationEndpoint()
     * After you have granted consent in the web browser for the application to access your Microsoft Advertising accounts,
     * please enter the response URI that includes the authorization 'code' parameter:
     *
     * @return void
     * @throws OAuthTokenRequestException
     * @throws RefreshTokenNotFoundException
     */
    public function authenticateWithOAuth(): void
    {
        $authentication = (new OAuthDesktopMobileAuthCodeGrant())
            ->withEnvironment($this->config['api_environment'])
            ->withClientId($this->config['client_id'])
            ->withClientSecret($this->config['client_secret'])
            ->withOAuthScope($this->config['oauth_scope']);

        $this->authorizationData = (new AuthorizationData())
            ->withAuthentication($authentication)
            ->withDeveloperToken($this->config['developer_token']);

        try {
            $refreshToken = $this->io->readToken();

            if ($refreshToken != null)  {
                $this->authorizationData->Authentication->requestOAuthTokensByRefreshToken($refreshToken);
                $this->io->writeToken($this->authorizationData->Authentication->OAuthTokens->RefreshToken);
            }
            else {
                throw new RefreshTokenNotFoundException('The refresh token is invalid.');
            }
        } catch(OAuthTokenRequestException $e) {
            throw (new OAuthTokenRequestException())
                ->withError( "OAuth token request failed:" . $e->getMessage());
        }
    }
}