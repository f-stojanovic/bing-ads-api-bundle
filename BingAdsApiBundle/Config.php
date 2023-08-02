<?php

namespace Coddict\BingAdsApiBundle;

use Microsoft\BingAds\Auth\ApiEnvironment;
use Microsoft\BingAds\Auth\OAuthScope;

/**
 * Should be deleted, we moved this to bing_ads_api.yaml file
 */
final class Config
{
    // const DeveloperToken = '110AE62Y43928768';
    // const ApiEnvironment = ApiEnvironment::Production;
    // const OAuthScope = OAuthScope::MSADS_MANAGE;
    // const OAuthRefreshTokenPath = 'refresh.txt';
    // const ClientId = '676c0e3d-77f6-466a-b981-83ac36bbeff7';
    // const ClientSecret = 'BKS8Q~M5Iw3QM5xXQ2wS9Ulfet-NgibPFJy3vcJs';

    const DeveloperToken = '11083549E3423572';
    const ApiEnvironment = ApiEnvironment::Production;
    const OAuthScope = OAuthScope::MSADS_MANAGE;
    const OAuthRefreshTokenPath = 'refresh.txt';
    const ClientId = '1f4e3cf4-2719-494d-b563-91377c2408cb';
    const ClientSecret = 'Hp48Q~VLVwbpQgcN77iL6zlIjMnSGRbHHXgNndjj';
    const AudienceId = 818044107;
}
