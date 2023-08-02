<?php

namespace Coddict\BingAdsApiBundle;

use Coddict\BingAdsApiBundle\Service\Authentication\Auth;
use Microsoft\BingAds\V13\CustomerManagement\GetUserRequest;

class CustomerManagement
{
    static function GetUser($userId)
    {
        Auth::$CustomerManagementProxy->SetAuthorizationData(Auth::$AuthorizationData);

        Auth::$Proxy = Auth::$CustomerManagementProxy;

        $request = new GetUserRequest();

        $request->UserId = $userId;

        return Auth::$CustomerManagementProxy->GetService()->GetUser($request);

    }
}
