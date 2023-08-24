<?php

namespace Coddict\BingAdsApiBundle\Service\Authentication;

use Coddict\BingAdsApiBundle\CustomerManagement;
use Exception;
use Microsoft\BingAds\Auth\AuthorizationData;
use Microsoft\BingAds\Auth\ServiceClient;
use Microsoft\BingAds\Auth\ServiceClientType;
use Microsoft\BingAds\V13\CustomerManagement\Paging;
use Microsoft\BingAds\V13\CustomerManagement\Predicate;
use Microsoft\BingAds\V13\CustomerManagement\PredicateOperator;
use Microsoft\BingAds\V13\CustomerManagement\SearchAccountsRequest;

final class Auth
{
    static $Proxy;
    static $BulkProxy;

    public function __construct(
        private readonly array              $config,
        private readonly AuthorizationData  $authorizationData,
        private readonly CustomerManagement $customerManagement,
        private ServiceClient $customerManagementProxy,
    ) { }

    /**
     * @return void
     * @throws Exception
     */
    public function authenticate(): void
    {
        $env = $this->config['api_environment'];

        // Set to an empty user identifier to get the current authenticated user,
        // and then search for accounts the user can access.
        $user = $this->customerManagement->getUser(null, true)->User;

        // To retrieve more than 100 accounts, increase the page size up to 1,000.
        // To retrieve more than 1,000 accounts you'll need to implement paging.
        $accounts = $this->searchAccountsByUserId($user->Id, 0, 100)->Accounts;

        // We'll use the first account by default for the examples.
        $this->authorizationData->AccountId = $accounts->AdvertiserAccount[0]->Id;
        $this->authorizationData->CustomerId = $accounts->AdvertiserAccount[0]->ParentCustomerId;

        self::$BulkProxy = new ServiceClient(
            ServiceClientType::BulkVersion13,
            $this->authorizationData,
            $env
        );

        $this->customerManagementProxy = new ServiceClient(
            ServiceClientType::CustomerManagementVersion13,
            $this->authorizationData,
            $env
        );
    }

    /**
     * @param string $userId
     * @param int $pageIndex
     * @param int $pageSize
     * @return mixed
     */
    public function searchAccountsByUserId(string $userId, int $pageIndex, int $pageSize): mixed
    {
        self::$Proxy = $this->customerManagementProxy;

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

        return $this->customerManagementProxy->GetService()->SearchAccounts($request);
    }
}
