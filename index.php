<?php

use Coddict\BingAdsApiBundle\AuthHelper;
use Coddict\BingAdsApiBundle\Config;
use Coddict\BingAdsApiBundle\Service\Customer\CustomerListHelper;

require_once __DIR__ . "/vendor/autoload.php";

// Auth::Authenticate();

$customerListHelper = new CustomerListHelper();

$emailsToAdd = [];
$customerListHelper->AddEmailsToBingAdsList($emailsToAdd, Config::AudienceId);

$emailsToDelete = [];
$customerListHelper->RemoveEmailsFomBingAdsList($emailsToDelete, Config::AudienceId);
