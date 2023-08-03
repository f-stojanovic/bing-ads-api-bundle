<?php

namespace Coddict\BingAdsApiBundle\Service\Bulk;

use Coddict\BingAdsApiBundle\Service\Authentication\Auth;
use Microsoft\BingAds\V13\Bulk\GetBulkDownloadStatusRequest;
use Microsoft\BingAds\V13\Bulk\GetBulkUploadStatusRequest;
use Microsoft\BingAds\V13\Bulk\GetBulkUploadUrlRequest;

final class BulkHelper 
{
    static function getBulkDownloadStatus($requestId)
    {
        Auth::$BulkProxy->setAuthorizationData(Auth::$AuthorizationData);
        Auth::$Proxy = Auth::$BulkProxy;

        $request = new GetBulkDownloadStatusRequest();

        $request->RequestId = $requestId;

        return Auth::$BulkProxy->getService()->getBulkDownloadStatus($request);
    }

    static function getBulkUploadStatus($requestId)
    {
        Auth::$BulkProxy->setAuthorizationData(Auth::$AuthorizationData);
        Auth::$Proxy = Auth::$BulkProxy;

        $request = new GetBulkUploadStatusRequest();

        $request->RequestId = $requestId;

        return Auth::$BulkProxy->getService()->getBulkUploadStatus($request);
    }

    static function getBulkUploadUrl($responseMode, $accountId)
    {
        Auth::$BulkProxy->setAuthorizationData(Auth::$AuthorizationData);
        Auth::$Proxy = Auth::$BulkProxy;

        $request = new GetBulkUploadUrlRequest();

        $request->ResponseMode = $responseMode;
        $request->AccountId = $accountId;

        return Auth::$BulkProxy->getService()->getBulkUploadUrl($request);
    }

    static function outputAdApiError($dataObject)
    {
        if (!empty($dataObject))
        {
            self::OutputStatusMessage("* * * Begin OutputAdApiError * * *");
            self::OutputStatusMessage(sprintf("Code: %s", $dataObject->Code));
            self::OutputStatusMessage(sprintf("Detail: %s", $dataObject->Detail));
            self::OutputStatusMessage(sprintf("ErrorCode: %s", $dataObject->ErrorCode));
            self::OutputStatusMessage(sprintf("Message: %s", $dataObject->Message));
            self::OutputStatusMessage("* * * End OutputAdApiError * * *");
        }
    }

    static function outputArrayOfAdApiError($dataObjects)
    {
        if(count((array)$dataObjects) == 0 || !isset($dataObjects->AdApiError))
        {
            return;
        }
        else if (!is_array($dataObjects->AdApiError))
        {
            self::outputAdApiError($dataObjects->AdApiError);
            return;
        }
        foreach ($dataObjects->AdApiError as $dataObject)
        {
            self::outputAdApiError($dataObject);
        }
    }
    static function outputAdApiFaultDetail($dataObject)
    {
        if (!empty($dataObject))
        {
            self::outputStatusMessage("* * * Begin OutputAdApiFaultDetail * * *");
            self::outputStatusMessage("Errors:");
            self::outputArrayOfAdApiError($dataObject->Errors);
            self::outputStatusMessage("* * * End OutputAdApiFaultDetail * * *");
        }
    }
    static function outputArrayOfAdApiFaultDetail($dataObjects)
    {
        if(count((array)$dataObjects) == 0 || !isset($dataObjects->AdApiFaultDetail))
        {
            return;
        }
        foreach ($dataObjects->AdApiFaultDetail as $dataObject)
        {
            self::outputAdApiFaultDetail($dataObject);
        }
    }

    static function outputApiFaultDetail($dataObject)
    {
        if (!empty($dataObject))
        {
            self::outputStatusMessage("* * * Begin OutputApiFaultDetail * * *");
            self::outputStatusMessage("BatchErrors:");
            self::outputArrayOfBatchError($dataObject->BatchErrors);
            self::outputStatusMessage("OperationErrors:");
            self::outputArrayOfOperationError($dataObject->OperationErrors);
            self::outputStatusMessage("* * * End OutputApiFaultDetail * * *");
        }
    }

    static function outputArrayOfApiFaultDetail($dataObjects)
    {
        if(count((array)$dataObjects) == 0 || !isset($dataObjects->ApiFaultDetail))
        {
            return;
        }
        foreach ($dataObjects->ApiFaultDetail as $dataObject)
        {
            self::outputApiFaultDetail($dataObject);
        }
    }

    static function outputApplicationFault($dataObject)
    {
        if (!empty($dataObject))
        {
            self::outputStatusMessage("* * * Begin OutputApplicationFault * * *");
            self::outputStatusMessage(sprintf("TrackingId: %s", $dataObject->TrackingId));
            if($dataObject->Type === "AdApiFaultDetail")
            {
                self::outputAdApiFaultDetail($dataObject);
            }
            if($dataObject->Type === "ApiFaultDetail")
            {
                self::outputApiFaultDetail($dataObject);
            }
            self::outputStatusMessage("* * * End OutputApplicationFault * * *");
        }
    }

    static function outputArrayOfApplicationFault($dataObjects)
    {
        if(count((array)$dataObjects) == 0 || !isset($dataObjects->ApplicationFault))
        {
            return;
        }
        foreach ($dataObjects->ApplicationFault as $dataObject)
        {
            self::outputApplicationFault($dataObject);
        }
    }

    static function outputBatchError($dataObject)
    {
        if (!empty($dataObject))
        {
            self::outputStatusMessage("* * * Begin OutputBatchError * * *");
            self::outputStatusMessage(sprintf("Code: %s", $dataObject->Code));
            self::outputStatusMessage(sprintf("Details: %s", $dataObject->Details));
            self::outputStatusMessage(sprintf("ErrorCode: %s", $dataObject->ErrorCode));
            self::outputStatusMessage(sprintf("FieldPath: %s", $dataObject->FieldPath));
            self::outputStatusMessage("ForwardCompatibilityMap:");
            self::outputArrayOfKeyValuePairOfstringstring($dataObject->ForwardCompatibilityMap);
            self::outputStatusMessage(sprintf("Index: %s", $dataObject->Index));
            self::outputStatusMessage(sprintf("Message: %s", $dataObject->Message));
            self::outputStatusMessage(sprintf("Type: %s", $dataObject->Type));
            if($dataObject->Type === "EditorialError")
            {
                self::outputEditorialError($dataObject);
            }
            self::outputStatusMessage("* * * End OutputBatchError * * *");
        }
    }

    static function outputArrayOfBatchError($dataObjects)
    {
        if(count((array)$dataObjects) == 0 || !isset($dataObjects->BatchError))
        {
            return;
        }
        foreach ($dataObjects->BatchError as $dataObject)
        {
            self::outputBatchError($dataObject);
        }
    }

    static function outputCampaignScope($dataObject)
    {
        if (!empty($dataObject))
        {
            self::outputStatusMessage("* * * Begin OutputCampaignScope * * *");
            self::outputStatusMessage(sprintf("CampaignId: %s", $dataObject->CampaignId));
            self::outputStatusMessage(sprintf("ParentAccountId: %s", $dataObject->ParentAccountId));
            self::outputStatusMessage("* * * End OutputCampaignScope * * *");
        }
    }

    static function outputArrayOfCampaignScope($dataObjects)
    {
        if(count((array)$dataObjects) == 0 || !isset($dataObjects->CampaignScope))
        {
            return;
        }
        foreach ($dataObjects->CampaignScope as $dataObject)
        {
            self::outputCampaignScope($dataObject);
        }
    }
    static function outputEditorialError($dataObject)
    {
        if (!empty($dataObject))
        {
            self::outputStatusMessage("* * * Begin OutputEditorialError * * *");
            self::outputStatusMessage(sprintf("Appealable: %s", $dataObject->Appealable));
            self::outputStatusMessage(sprintf("DisapprovedText: %s", $dataObject->DisapprovedText));
            self::outputStatusMessage(sprintf("Location: %s", $dataObject->Location));
            self::outputStatusMessage(sprintf("PublisherCountry: %s", $dataObject->PublisherCountry));
            self::outputStatusMessage(sprintf("ReasonCode: %s", $dataObject->ReasonCode));
            self::outputStatusMessage("* * * End OutputEditorialError * * *");
        }
    }

    static function outputArrayOfEditorialError($dataObjects)
    {
        if(count((array)$dataObjects) == 0 || !isset($dataObjects->EditorialError))
        {
            return;
        }
        else if (!is_array($dataObjects->EditorialError))
        {
            self::outputEditorialError($dataObjects->EditorialError);
            return;
        }
        foreach ($dataObjects->EditorialError as $dataObject)
        {
            self::outputEditorialError($dataObject);
        }
    }

    static function outputKeyValuePairOfstringstring($dataObject)
    {
        if (!empty($dataObject))
        {
            self::outputStatusMessage("* * * Begin OutputKeyValuePairOfstringstring * * *");
            self::outputStatusMessage(sprintf("key: %s", $dataObject->key));
            self::outputStatusMessage(sprintf("value: %s", $dataObject->value));
            self::outputStatusMessage("* * * End OutputKeyValuePairOfstringstring * * *");
        }
    }

    static function outputArrayOfKeyValuePairOfstringstring($dataObjects)
    {
        if(count((array)$dataObjects) == 0 || !isset($dataObjects->KeyValuePairOfstringstring))
        {
            return;
        }
        foreach ($dataObjects->KeyValuePairOfstringstring as $dataObject)
        {
            self::outputKeyValuePairOfstringstring($dataObject);
        }
    }

    static function outputOperationError($dataObject)
    {
        if (!empty($dataObject))
        {
            self::outputStatusMessage("* * * Begin OutputOperationError * * *");
            self::outputStatusMessage(sprintf("Code: %s", $dataObject->Code));
            self::outputStatusMessage(sprintf("Details: %s", $dataObject->Details));
            self::outputStatusMessage(sprintf("ErrorCode: %s", $dataObject->ErrorCode));
            self::outputStatusMessage(sprintf("Message: %s", $dataObject->Message));
            self::outputStatusMessage("* * * End OutputOperationError * * *");
        }
    }

    static function outputArrayOfOperationError($dataObjects)
    {
        if(count((array)$dataObjects) == 0 || !isset($dataObjects->OperationError))
        {
            return;
        }
        else if (!is_array($dataObjects->OperationError))
        {
            self::outputOperationError($dataObjects->OperationError);
            return;
        }
        foreach ($dataObjects->OperationError as $dataObject)
        {
            self::outputOperationError($dataObject);
        }
    }

    static function outputCompressionType($valueSet)
    {
        self::outputStatusMessage("* * * Begin OutputCompressionType * * *");
        self::outputStatusMessage(sprintf("Values in %s", $valueSet->type));
        foreach ($valueSet->string as $value)
        {
            self::outputStatusMessage($value);
        }
        self::outputStatusMessage("* * * End OutputCompressionType * * *");
    }

    static function outputArrayOfCompressionType($valueSets)
    {
        if(count((array)$valueSets) == 0)
        {
            return;
        }
        self::outputStatusMessage("* * * Begin OutputArrayOfCompressionType * * *");
        foreach ($valueSets->CompressionType as $valueSet)
        {
            self::outputCompressionType($valueSet);
        }
        self::outputStatusMessage("* * * End OutputArrayOfCompressionType * * *");
    }

    static function outputDataScope($valueSet)
    {
        self::outputStatusMessage("* * * Begin OutputDataScope * * *");
        self::outputStatusMessage(sprintf("Values in %s", $valueSet->type));
        foreach ($valueSet->string as $value)
        {
            self::outputStatusMessage($value);
        }
        self::outputStatusMessage("* * * End OutputDataScope * * *");
    }

    static function outputArrayOfDataScope($valueSets)
    {
        if(count((array)$valueSets) == 0)
        {
            return;
        }
        self::outputStatusMessage("* * * Begin OutputArrayOfDataScope * * *");
        foreach ($valueSets->DataScope as $valueSet)
        {
            self::outputDataScope($valueSet);
        }
        self::outputStatusMessage("* * * End OutputArrayOfDataScope * * *");
    }

    static function outputDownloadEntity($valueSet)
    {
        self::outputStatusMessage("* * * Begin OutputDownloadEntity * * *");
        self::outputStatusMessage(sprintf("Values in %s", $valueSet->type));
        foreach ($valueSet->string as $value)
        {
            self::outputStatusMessage($value);
        }
        self::outputStatusMessage("* * * End OutputDownloadEntity * * *");
    }

    static function outputArrayOfDownloadEntity($valueSets)
    {
        if(count((array)$valueSets) == 0)
        {
            return;
        }
        self::outputStatusMessage("* * * Begin OutputArrayOfDownloadEntity * * *");
        foreach ($valueSets->DownloadEntity as $valueSet)
        {
            self::outputDownloadEntity($valueSet);
        }
        self::outputStatusMessage("* * * End OutputArrayOfDownloadEntity * * *");
    }

    static function outputDownloadFileType($valueSet)
    {
        self::outputStatusMessage("* * * Begin OutputDownloadFileType * * *");
        self::outputStatusMessage(sprintf("Values in %s", $valueSet->type));
        foreach ($valueSet->string as $value)
        {
            self::outputStatusMessage($value);
        }
        self::outputStatusMessage("* * * End OutputDownloadFileType * * *");
    }

    static function outputArrayOfDownloadFileType($valueSets)
    {
        if(count((array)$valueSets) == 0)
        {
            return;
        }
        self::outputStatusMessage("* * * Begin OutputArrayOfDownloadFileType * * *");
        foreach ($valueSets->DownloadFileType as $valueSet)
        {
            self::outputDownloadFileType($valueSet);
        }
        self::outputStatusMessage("* * * End OutputArrayOfDownloadFileType * * *");
    }

    static function outputResponseMode($valueSet)
    {
        self::outputStatusMessage("* * * Begin OutputResponseMode * * *");
        self::outputStatusMessage(sprintf("Values in %s", $valueSet->type));
        foreach ($valueSet->string as $value)
        {
            self::outputStatusMessage($value);
        }
        self::outputStatusMessage("* * * End OutputResponseMode * * *");
    }
    static function outputArrayOfResponseMode($valueSets)
    {
        if(count((array)$valueSets) == 0)
        {
            return;
        }
        self::outputStatusMessage("* * * Begin OutputArrayOfResponseMode * * *");
        foreach ($valueSets->ResponseMode as $valueSet)
        {
            self::outputResponseMode($valueSet);
        }
        self::outputStatusMessage("* * * End OutputArrayOfResponseMode * * *");
    }

    static function outputStatusMessage($message)
    {
        printf(" % s\n", $message);
    }

    static function outputArrayOfString($items)
    {
        if(count((array)$items) == 0 || !isset($items->string))
        {
            return;
        }
        self::outputStatusMessage("* * * Begin OutputArrayOfString * * *");
        foreach ($items->string as $item)
        {
            self::outputStatusMessage(sprintf("%s", $item));
        }
        self::outputStatusMessage("* * * End OutputArrayOfString * * *");
    }

    static function outputArrayOfLong($items)
    {
        if(count((array)$items) == 0 || !isset($items->long))
        {
            return;
        }
        self::outputStatusMessage("* * * Begin OutputArrayOfLong * * *");
        foreach ($items->long as $item)
        {
            self::outputStatusMessage(sprintf("%s", $item));
        }
        self::outputStatusMessage("* * * End OutputArrayOfLong * * *");
    }

    static function outputArrayOfInt($items)
    {
        if(count((array)$items) == 0 || !isset($items->int))
        {
            return;
        }
        self::outputStatusMessage("* * * Begin OutputArrayOfInt * * *");
        foreach ($items->int as $item)
        {
            self::outputStatusMessage(sprintf("%s", $item));
        }
        self::outputStatusMessage("* * * End OutputArrayOfInt * * *");
    }
}
