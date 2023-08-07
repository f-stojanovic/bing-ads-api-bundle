<?php

namespace Coddict\BingAdsApiBundle\Service\Authentication;

use Coddict\BingAdsApiBundle\CustomerManagement;
use Exception;
use Microsoft\BingAds\Auth\AuthorizationData;
use Microsoft\BingAds\Auth\OAuthDesktopMobileAuthCodeGrant;
use Microsoft\BingAds\Auth\OAuthTokenRequestException;
use Microsoft\BingAds\Auth\ServiceClient;
use Microsoft\BingAds\V13\CustomerManagement\Paging;
use Microsoft\BingAds\V13\CustomerManagement\Predicate;
use Microsoft\BingAds\V13\CustomerManagement\PredicateOperator;
use Microsoft\BingAds\V13\CustomerManagement\SearchAccountsRequest;

final class Auth
{
    public function __construct(
        private readonly array         $config,
        private AuthorizationData      $authorizationData,
        private readonly CustomerManagement $customerManagement,
        private readonly ServiceClient $customerManagementProxy,
        private readonly ServiceClient $bulkProxy
    ) { }

    /**
     * @return void
     * @throws Exception
     */
    public function authenticate(): void
    {
        // Authenticate with a Microsoft Account.
        $this->authenticateWithOAuth();

        // Set to an empty user identifier to get the current authenticated user,
        // and then search for accounts the user can access.
        $user = $this->customerManagement->getUser(null, true)->User;

        // To retrieve more than 100 accounts, increase the page size up to 1,000.
        // To retrieve more than 1,000 accounts you'll need to implement paging.
        $accounts = $this->searchAccountsByUserId($user->Id, 0, 100)->Accounts;

        // We'll use the first account by default for the examples.
        $this->authorizationData->AccountId = $accounts->AdvertiserAccount[0]->Id;
        $this->authorizationData->CustomerId = $accounts->AdvertiserAccount[0]->ParentCustomerId;

        // Update the proxies with the new authorization data
        $this->customerManagementProxy->setAuthorizationData($this->authorizationData);
        $this->bulkProxy->setAuthorizationData($this->authorizationData);
    }

    /**
     * @param string $userId
     * @param int $pageIndex
     * @param int $pageSize
     * @return mixed
     */
    public function searchAccountsByUserId(string $userId, int $pageIndex, int $pageSize): mixed
    {
        $proxy = $this->customerManagementProxy;
    
        // Specify the page index and number of account results per page.
        $pageInfo = new Paging();
        $pageInfo->Index = $pageIndex;
        $pageInfo->Size = $pageSize;  

        $predicate = new Predicate();
        $predicate->Field = "UserId";
        $predicate->Operator = PredicateOperator::Equals;
        $predicate->Value = $userId; 

        $request = new SearchAccountsRequest();
        $request->Ordering = null;
        $request->PageInfo = $pageInfo;
        $request->Predicates = array($predicate);

        return $proxy->getService()->searchAccounts($request);
    }

    /**
     * @return void
     * @throws Exception
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
