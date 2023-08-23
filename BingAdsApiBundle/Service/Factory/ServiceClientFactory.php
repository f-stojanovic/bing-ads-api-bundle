<?php

namespace Coddict\BingAdsApiBundle\Service\Factory;

use Coddict\BingAdsApiBundle\Service\Authentication\Auth;
use Coddict\BingAdsApiBundle\CustomerManagement;
use Coddict\BingAdsApiBundle\Service\Bulk\BulkHelper;
use Coddict\BingAdsApiBundle\Service\Customer\CustomerListHelper;
use Exception;
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
        private readonly string $uploadDirectory,
        private readonly LoggerInterface $logger
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
            $this->uploadDirectory,
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
            )
        );
    }

    private function createBulkHelperInstance(): BulkHelper
    {
        return new BulkHelper(
            $this->authorizationData,
            $this->serviceClient
        );
    }

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
            $refreshToken = $this->readOAuthRefreshToken();

            if ($refreshToken != null)  {
                $this->authorizationData->Authentication->requestOAuthTokensByRefreshToken($refreshToken);
                $this->writeOAuthRefreshToken(
                    $this->authorizationData->Authentication->OAuthTokens->RefreshToken
                );
            }
            else {
                $this->requestUserConsent();
            }
        } catch(OAuthTokenRequestException $e) {
            $this->requestUserConsent();
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    public function requestUserConsent(): void
    {
        print "You need to provide consent for the application to access your Microsoft Advertising accounts. " .
            "Copy and paste this authorization endpoint into a web browser and sign in with a Microsoft account " .
            "with access to a Microsoft Advertising account: \n\n" . $this->authorizationData->Authentication->getAuthorizationEndpoint() .
            "\n\nAfter you have granted consent in the web browser for the application to access your Microsoft Advertising accounts, " .
            "please enter the response URI that includes the authorization 'code' parameter: \n\n";

        $responseUri = fgets(STDIN);
        print "\n";

        $this->authorizationData->Authentication->requestOAuthTokensByResponseUri(trim($responseUri));
        $this->writeOAuthRefreshToken($this->authorizationData->Authentication->OAuthTokens->RefreshToken);
    }

    /**
     * @return string|null
     * @throws Exception
     */
    public function readOAuthRefreshToken(): ?string
    {
        $refreshToken = null;

        if (file_exists($this->config['oauth_refresh_token_path']) && filesize($this->config['oauth_refresh_token_path']) > 0) {
            $refreshTokenFile = fopen($this->config['oauth_refresh_token_path'], "r");
            if ($refreshTokenFile !== false) {
                $refreshToken = fread($refreshTokenFile, filesize($this->config['oauth_refresh_token_path']));
                fclose($refreshTokenFile);
            } else {
                throw new Exception("Failed to open the refresh token file for reading.");
            }
        }

        return $refreshToken;
    }

    /**
     * @param string $refreshToken
     * @return void
     * @throws Exception
     */
    public function writeOAuthRefreshToken(string $refreshToken): void
    {
        $refreshTokenFile = fopen($this->config['oauth_refresh_token_path'], "wb");
        if ($refreshTokenFile !== false) {
            if (fwrite($refreshTokenFile, $refreshToken) === false) {
                fclose($refreshTokenFile);
                throw new Exception("Failed to write the refresh token to the file.");
            }
            fclose($refreshTokenFile);
        } else {
            throw new Exception("Failed to open the refresh token file for writing.");
        }
    }
}