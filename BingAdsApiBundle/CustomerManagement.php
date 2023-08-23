<?php

namespace Coddict\BingAdsApiBundle;

use Exception;
use Microsoft\BingAds\Auth\AuthorizationData;
use Microsoft\BingAds\Auth\ServiceClient;
use Microsoft\BingAds\V13\CustomerManagement\GetUserRequest;

final class CustomerManagement
{
    public function __construct(
        public ServiceClient $customerManagementProxy,
        public AuthorizationData $authorizationData
    ) { }

    /**
     * @param int|null $userId
     * @return mixed
     * @throws Exception
     */
    public function getUser(?int $userId): mixed
    {
        $this->customerManagementProxy->setAuthorizationData($this->authorizationData);

        $request = new GetUserRequest();
        $request->UserId = $userId;

        return $this->customerManagementProxy->getService()->getUser($request);
    }
}
