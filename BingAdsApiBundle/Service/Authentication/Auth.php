<?php

namespace Coddict\BingAdsApiBundle\Service\Authentication;

use Coddict\BingAdsApiBundle\Config;
use Coddict\BingAdsApiBundle\CustomerManagement;
use Microsoft\BingAds\Auth\AuthorizationData;
use Microsoft\BingAds\Auth\OAuthDesktopMobileAuthCodeGrant;
use Microsoft\BingAds\Auth\OAuthTokenRequestException;
use Microsoft\BingAds\Auth\ServiceClient;
use Microsoft\BingAds\Auth\ServiceClientType;
use Microsoft\BingAds\V13\CustomerManagement\Paging;
use Microsoft\BingAds\V13\CustomerManagement\Predicate;
use Microsoft\BingAds\V13\CustomerManagement\PredicateOperator;
use Microsoft\BingAds\V13\CustomerManagement\SearchAccountsRequest;

final class Auth
{
    static $AuthorizationData;

    static $CustomerManagementProxy;

    static $Proxy;

    static $BulkProxy;

    public function authenticate(): void
    {   
        // Authenticate with a Microsoft Account.
        $this->authenticateWithOAuth();

        self::$CustomerManagementProxy = new ServiceClient(
            ServiceClientType::CustomerManagementVersion13, 
            self::$AuthorizationData, 
            Config::ApiEnvironment
        );
            
        // Set to an empty user identifier to get the current authenticated user,
        // and then search for accounts the user can access.
        $user = CustomerManagement::getUser(null, true)->User;

        // To retrieve more than 100 accounts, increase the page size up to 1,000.
        // To retrieve more than 1,000 accounts you'll need to implement paging.
        $accounts = $this->searchAccountsByUserId($user->Id, 0, 100)->Accounts;
        
        // We'll use the first account by default for the examples. 
        self::$AuthorizationData->AccountId = $accounts->AdvertiserAccount[0]->Id;
        self::$AuthorizationData->CustomerId = $accounts->AdvertiserAccount[0]->ParentCustomerId;

        self::$BulkProxy = new ServiceClient(
            ServiceClientType::BulkVersion13, 
            self::$AuthorizationData, 
            Config::ApiEnvironment
        );

        self::$CustomerManagementProxy = new ServiceClient(
            ServiceClientType::CustomerManagementVersion13, 
            self::$AuthorizationData, 
            Config::ApiEnvironment
        );
    }

    public function searchAccountsByUserId($userId, $pageIndex, $pageSize)
    {
        self::$Proxy = self::$CustomerManagementProxy;
    
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

        return self::$Proxy->getService()->searchAccounts($request);
    }

    public function authenticateWithOAuth(): void
    {
        $authentication = (new OAuthDesktopMobileAuthCodeGrant())
            ->withEnvironment(Config::ApiEnvironment)
            ->withClientId(Config::ClientId)
            ->withClientSecret(Config::ClientSecret)
            ->withOAuthScope(Config::OAuthScope);
            
        self::$AuthorizationData = (new AuthorizationData())
            ->withAuthentication($authentication)
            ->withDeveloperToken(Config::DeveloperToken);

        try {
            $refreshToken = self::readOAuthRefreshToken();

            if ($refreshToken != null)  {
                self::$AuthorizationData->Authentication->requestOAuthTokensByRefreshToken($refreshToken);
                self::writeOAuthRefreshToken(
                    self::$AuthorizationData->Authentication->OAuthTokens->RefreshToken
                );
            }
            else {
                self::requestUserConsent();
            }            
        } catch(OAuthTokenRequestException $e) {
            self::requestUserConsent();
        }
    }

    public function requestUserConsent(): void
    {
        print "You need to provide consent for the application to access your Microsoft Advertising accounts. " .
              "Copy and paste this authorization endpoint into a web browser and sign in with a Microsoft account " . 
              "with access to a Microsoft Advertising account: \n\n" . self::$AuthorizationData->Authentication->getAuthorizationEndpoint() .
              "\n\nAfter you have granted consent in the web browser for the application to access your Microsoft Advertising accounts, " .
              "please enter the response URI that includes the authorization 'code' parameter: \n\n";
        
        $responseUri = fgets(STDIN);
        print "\n";

        self::$AuthorizationData->Authentication->requestOAuthTokensByResponseUri(trim($responseUri));
        self::writeOAuthRefreshToken(self::$AuthorizationData->Authentication->OAuthTokens->RefreshToken);
    }

    public function readOAuthRefreshToken(): bool|string|null
    {
        $refreshToken = null;
        
        if (file_exists(Config::OAuthRefreshTokenPath) && filesize(Config::OAuthRefreshTokenPath) > 0) {
            $refreshTokenfile = @\fopen(Config::OAuthRefreshTokenPath, "r");
            $refreshToken = fread($refreshTokenfile, filesize(Config::OAuthRefreshTokenPath));
            fclose($refreshTokenfile);
        }

        return $refreshToken;
    }

    public function writeOAuthRefreshToken($refreshToken): void
    {        
        $refreshTokenfile = @\fopen(Config::OAuthRefreshTokenPath, "wb");
        if (file_exists(Config::OAuthRefreshTokenPath)) {
            fwrite($refreshTokenfile, $refreshToken);
            fclose($refreshTokenfile);
        }

        return;
    }
}
