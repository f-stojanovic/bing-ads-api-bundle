<?php

namespace Coddict\BingAdsApiBundle\Service\Bulk;

use Coddict\BingAdsApiBundle\Service\Authentication\Auth;
use Exception;
use Microsoft\BingAds\Auth\AuthorizationData;
use Microsoft\BingAds\V13\Bulk\GetBulkDownloadStatusRequest;
use Microsoft\BingAds\V13\Bulk\GetBulkUploadStatusRequest;
use Microsoft\BingAds\V13\Bulk\GetBulkUploadUrlRequest;

final class BulkHelper
{
    public function __construct(
        private readonly AuthorizationData $authorizationData
    ) { }

    /**
     * @param string $requestId
     * @return mixed
     * @throws Exception
     */
    public function getBulkDownloadStatus(string $requestId): mixed
    {
        Auth::$BulkProxy->SetAuthorizationData($this->authorizationData);
        Auth::$Proxy = Auth::$BulkProxy;

        $request = new GetBulkDownloadStatusRequest();

        $request->RequestId = $requestId;

        return Auth::$BulkProxy->GetService()->getBulkDownloadStatus($request);
    }

    /**
     * @param  $requestId
     * @return mixed
     * @throws Exception
     */
    public function getBulkUploadStatus($requestId): mixed
    {
        Auth::$BulkProxy->SetAuthorizationData($this->authorizationData);
        Auth::$Proxy = Auth::$BulkProxy;

        $request = new GetBulkUploadStatusRequest();

        $request->RequestId = $requestId;

        return Auth::$BulkProxy->GetService()->getBulkUploadStatus($request);
    }

    /**
     * @param $responseMode
     * @param $accountId
     * @return mixed
     * @throws Exception
     */
    public function getBulkUploadUrl($responseMode, $accountId): mixed
    {
        Auth::$BulkProxy->SetAuthorizationData($this->authorizationData);
        Auth::$Proxy = Auth::$BulkProxy;

        $request = new GetBulkUploadUrlRequest();

        $request->ResponseMode = $responseMode;
        $request->AccountId = $accountId;

        return Auth::$BulkProxy->GetService()->getBulkUploadUrl($request);
    }

    /**
     * @param $dataObject
     * @return void
     */
    public function outputAdApiError($dataObject): void
    {
        if (!empty($dataObject))
        {
            $this->outputStatusMessage("* * * Begin OutputAdApiError * * *");
            $this->outputStatusMessage(sprintf("Code: %s", $dataObject->Code));
            $this->outputStatusMessage(sprintf("Detail: %s", $dataObject->Detail));
            $this->outputStatusMessage(sprintf("ErrorCode: %s", $dataObject->ErrorCode));
            $this->outputStatusMessage(sprintf("Message: %s", $dataObject->Message));
            $this->outputStatusMessage("* * * End OutputAdApiError * * *");
        }
    }

    /**
     * @param $dataObjects
     * @return void
     */
    public function outputArrayOfAdApiError($dataObjects): void
    {
        if(count((array)$dataObjects) == 0 || !isset($dataObjects->AdApiError))
        {
            return;
        }
        else if (!is_array($dataObjects->AdApiError))
        {
            $this->outputAdApiError($dataObjects->AdApiError);
            return;
        }
        foreach ($dataObjects->AdApiError as $dataObject)
        {
            $this->outputAdApiError($dataObject);
        }
    }

    /**
     * @param $dataObject
     * @return void
     */
    public function outputAdApiFaultDetail($dataObject): void
    {
        if (!empty($dataObject))
        {
            $this->outputStatusMessage("* * * Begin OutputAdApiFaultDetail * * *");
            $this->outputStatusMessage("Errors:");
            $this->outputArrayOfAdApiError($dataObject->Errors);
            $this->outputStatusMessage("* * * End OutputAdApiFaultDetail * * *");
        }
    }

    /**
     * @param $dataObjects
     * @return void
     */
    public function outputArrayOfAdApiFaultDetail($dataObjects): void
    {
        if(count((array)$dataObjects) == 0 || !isset($dataObjects->AdApiFaultDetail))
        {
            return;
        }
        foreach ($dataObjects->AdApiFaultDetail as $dataObject)
        {
            $this->outputAdApiFaultDetail($dataObject);
        }
    }

    /**
     * @param $dataObject
     * @return void
     */
    public function outputApiFaultDetail($dataObject): void
    {
        if (!empty($dataObject))
        {
            $this->outputStatusMessage("* * * Begin OutputApiFaultDetail * * *");
            $this->outputStatusMessage("BatchErrors:");
            $this->outputArrayOfBatchError($dataObject->BatchErrors);
            $this->outputStatusMessage("OperationErrors:");
            $this->outputArrayOfOperationError($dataObject->OperationErrors);
            $this->outputStatusMessage("* * * End OutputApiFaultDetail * * *");
        }
    }

    /**
     * @param $dataObjects
     * @return void
     */
    public function outputArrayOfApiFaultDetail($dataObjects): void
    {
        if(count((array)$dataObjects) == 0 || !isset($dataObjects->ApiFaultDetail))
        {
            return;
        }
        foreach ($dataObjects->ApiFaultDetail as $dataObject)
        {
            $this->outputApiFaultDetail($dataObject);
        }
    }

    /**
     * @param $dataObject
     * @return void
     */
    public function outputApplicationFault($dataObject): void
    {
        if (!empty($dataObject))
        {
            $this->outputStatusMessage("* * * Begin OutputApplicationFault * * *");
            $this->outputStatusMessage(sprintf("TrackingId: %s", $dataObject->TrackingId));
            if($dataObject->Type === "AdApiFaultDetail")
            {
                $this->outputAdApiFaultDetail($dataObject);
            }
            if($dataObject->Type === "ApiFaultDetail")
            {
                $this->outputApiFaultDetail($dataObject);
            }
            $this->outputStatusMessage("* * * End OutputApplicationFault * * *");
        }
    }

    /**
     * @param $dataObjects
     * @return void
     */
    public function outputArrayOfApplicationFault($dataObjects): void
    {
        if(count((array)$dataObjects) == 0 || !isset($dataObjects->ApplicationFault))
        {
            return;
        }
        foreach ($dataObjects->ApplicationFault as $dataObject)
        {
            $this->outputApplicationFault($dataObject);
        }
    }

    /**
     * @param $dataObject
     * @return void
     */
    public function outputBatchError($dataObject): void
    {
        if (!empty($dataObject))
        {
            $this->outputStatusMessage("* * * Begin OutputBatchError * * *");
            $this->outputStatusMessage(sprintf("Code: %s", $dataObject->Code));
            $this->outputStatusMessage(sprintf("Details: %s", $dataObject->Details));
            $this->outputStatusMessage(sprintf("ErrorCode: %s", $dataObject->ErrorCode));
            $this->outputStatusMessage(sprintf("FieldPath: %s", $dataObject->FieldPath));
            $this->outputStatusMessage("ForwardCompatibilityMap:");
            $this->outputArrayOfKeyValuePairOfstringstring($dataObject->ForwardCompatibilityMap);
            $this->outputStatusMessage(sprintf("Index: %s", $dataObject->Index));
            $this->outputStatusMessage(sprintf("Message: %s", $dataObject->Message));
            $this->outputStatusMessage(sprintf("Type: %s", $dataObject->Type));
            if($dataObject->Type === "EditorialError")
            {
                $this->outputEditorialError($dataObject);
            }
            $this->outputStatusMessage("* * * End OutputBatchError * * *");
        }
    }

    /**
     * @param $dataObjects
     * @return void
     */
    public function outputArrayOfBatchError($dataObjects): void
    {
        if(count((array)$dataObjects) == 0 || !isset($dataObjects->BatchError))
        {
            return;
        }
        foreach ($dataObjects->BatchError as $dataObject)
        {
            $this->outputBatchError($dataObject);
        }
    }

    /**
     * @param $dataObject
     * @return void
     */
    public function outputCampaignScope($dataObject): void
    {
        if (!empty($dataObject))
        {
            $this->outputStatusMessage("* * * Begin OutputCampaignScope * * *");
            $this->outputStatusMessage(sprintf("CampaignId: %s", $dataObject->CampaignId));
            $this->outputStatusMessage(sprintf("ParentAccountId: %s", $dataObject->ParentAccountId));
            $this->outputStatusMessage("* * * End OutputCampaignScope * * *");
        }
    }

    /**
     * @param $dataObjects
     * @return void
     */
    public function outputArrayOfCampaignScope($dataObjects): void
    {
        if(count((array)$dataObjects) == 0 || !isset($dataObjects->CampaignScope))
        {
            return;
        }
        foreach ($dataObjects->CampaignScope as $dataObject)
        {
            $this->outputCampaignScope($dataObject);
        }
    }

    /**
     * @param $dataObject
     * @return void
     */
    public function outputEditorialError($dataObject): void
    {
        if (!empty($dataObject))
        {
            $this->outputStatusMessage("* * * Begin OutputEditorialError * * *");
            $this->outputStatusMessage(sprintf("Appealable: %s", $dataObject->Appealable));
            $this->outputStatusMessage(sprintf("DisapprovedText: %s", $dataObject->DisapprovedText));
            $this->outputStatusMessage(sprintf("Location: %s", $dataObject->Location));
            $this->outputStatusMessage(sprintf("PublisherCountry: %s", $dataObject->PublisherCountry));
            $this->outputStatusMessage(sprintf("ReasonCode: %s", $dataObject->ReasonCode));
            $this->outputStatusMessage("* * * End OutputEditorialError * * *");
        }
    }

    /**
     * @param $dataObjects
     * @return void
     */
    public function outputArrayOfEditorialError($dataObjects): void
    {
        if(count((array)$dataObjects) == 0 || !isset($dataObjects->EditorialError))
        {
            return;
        }
        else if (!is_array($dataObjects->EditorialError))
        {
            $this->outputEditorialError($dataObjects->EditorialError);
            return;
        }
        foreach ($dataObjects->EditorialError as $dataObject)
        {
            $this->outputEditorialError($dataObject);
        }
    }

    /**
     * @param $dataObject
     * @return void
     */
    public function outputKeyValuePairOfstringstring($dataObject): void
    {
        if (!empty($dataObject))
        {
            $this->outputStatusMessage("* * * Begin OutputKeyValuePairOfstringstring * * *");
            $this->outputStatusMessage(sprintf("key: %s", $dataObject->key));
            $this->outputStatusMessage(sprintf("value: %s", $dataObject->value));
            $this->outputStatusMessage("* * * End OutputKeyValuePairOfstringstring * * *");
        }
    }

    /**
     * @param $dataObjects
     * @return void
     */
    public function outputArrayOfKeyValuePairOfstringstring($dataObjects): void
    {
        if(count((array)$dataObjects) == 0 || !isset($dataObjects->KeyValuePairOfstringstring))
        {
            return;
        }
        foreach ($dataObjects->KeyValuePairOfstringstring as $dataObject)
        {
            $this->outputKeyValuePairOfstringstring($dataObject);
        }
    }

    /**
     * @param $dataObject
     * @return void
     */
    public function outputOperationError($dataObject): void
    {
        if (!empty($dataObject))
        {
            $this->outputStatusMessage("* * * Begin OutputOperationError * * *");
            $this->outputStatusMessage(sprintf("Code: %s", $dataObject->Code));
            $this->outputStatusMessage(sprintf("Details: %s", $dataObject->Details));
            $this->outputStatusMessage(sprintf("ErrorCode: %s", $dataObject->ErrorCode));
            $this->outputStatusMessage(sprintf("Message: %s", $dataObject->Message));
            $this->outputStatusMessage("* * * End OutputOperationError * * *");
        }
    }

    /**
     * @param $dataObjects
     * @return void
     */
    public function outputArrayOfOperationError($dataObjects): void
    {
        if(count((array)$dataObjects) == 0 || !isset($dataObjects->OperationError))
        {
            return;
        }
        else if (!is_array($dataObjects->OperationError))
        {
            $this->outputOperationError($dataObjects->OperationError);
            return;
        }
        foreach ($dataObjects->OperationError as $dataObject)
        {
            $this->outputOperationError($dataObject);
        }
    }

    /**
     * @param $valueSet
     * @return void
     */
    public function outputCompressionType($valueSet): void
    {
        $this->outputStatusMessage("* * * Begin OutputCompressionType * * *");
        $this->outputStatusMessage(sprintf("Values in %s", $valueSet->type));
        foreach ($valueSet->string as $value)
        {
            $this->outputStatusMessage($value);
        }
        $this->outputStatusMessage("* * * End OutputCompressionType * * *");
    }

    /**
     * @param $valueSets
     * @return void
     */
    public function outputArrayOfCompressionType($valueSets): void
    {
        if(count((array)$valueSets) == 0)
        {
            return;
        }
        $this->outputStatusMessage("* * * Begin OutputArrayOfCompressionType * * *");
        foreach ($valueSets->CompressionType as $valueSet)
        {
            $this->outputCompressionType($valueSet);
        }
        $this->outputStatusMessage("* * * End OutputArrayOfCompressionType * * *");
    }

    /**
     * @param $valueSet
     * @return void
     */
    public function outputDataScope($valueSet): void
    {
        $this->outputStatusMessage("* * * Begin OutputDataScope * * *");
        $this->outputStatusMessage(sprintf("Values in %s", $valueSet->type));
        foreach ($valueSet->string as $value)
        {
            $this->outputStatusMessage($value);
        }
        $this->outputStatusMessage("* * * End OutputDataScope * * *");
    }

    /**
     * @param $valueSets
     * @return void
     */
    public function outputArrayOfDataScope($valueSets): void
    {
        if(count((array)$valueSets) == 0)
        {
            return;
        }
        $this->outputStatusMessage("* * * Begin OutputArrayOfDataScope * * *");
        foreach ($valueSets->DataScope as $valueSet)
        {
            $this->outputDataScope($valueSet);
        }
        $this->outputStatusMessage("* * * End OutputArrayOfDataScope * * *");
    }

    /**
     * @param $valueSet
     * @return void
     */
    public function outputDownloadEntity($valueSet): void
    {
        $this->outputStatusMessage("* * * Begin OutputDownloadEntity * * *");
        $this->outputStatusMessage(sprintf("Values in %s", $valueSet->type));
        foreach ($valueSet->string as $value)
        {
            $this->outputStatusMessage($value);
        }
        $this->outputStatusMessage("* * * End OutputDownloadEntity * * *");
    }

    /**
     * @param $valueSets
     * @return void
     */
    public function outputArrayOfDownloadEntity($valueSets): void
    {
        if(count((array)$valueSets) == 0)
        {
            return;
        }
        $this->outputStatusMessage("* * * Begin OutputArrayOfDownloadEntity * * *");
        foreach ($valueSets->DownloadEntity as $valueSet)
        {
            $this->outputDownloadEntity($valueSet);
        }
        $this->outputStatusMessage("* * * End OutputArrayOfDownloadEntity * * *");
    }

    /**
     * @param $valueSet
     * @return void
     */
    public function outputDownloadFileType($valueSet): void
    {
        $this->outputStatusMessage("* * * Begin OutputDownloadFileType * * *");
        $this->outputStatusMessage(sprintf("Values in %s", $valueSet->type));
        foreach ($valueSet->string as $value)
        {
            $this->outputStatusMessage($value);
        }
        $this->outputStatusMessage("* * * End OutputDownloadFileType * * *");
    }

    /**
     * @param $valueSets
     * @return void
     */
    public function outputArrayOfDownloadFileType($valueSets): void
    {
        if(count((array)$valueSets) == 0)
        {
            return;
        }
        $this->outputStatusMessage("* * * Begin OutputArrayOfDownloadFileType * * *");
        foreach ($valueSets->DownloadFileType as $valueSet)
        {
            $this->outputDownloadFileType($valueSet);
        }
        $this->outputStatusMessage("* * * End OutputArrayOfDownloadFileType * * *");
    }

    /**
     * @param $valueSet
     * @return void
     */
    public function outputResponseMode($valueSet): void
    {
        $this->outputStatusMessage("* * * Begin OutputResponseMode * * *");
        $this->outputStatusMessage(sprintf("Values in %s", $valueSet->type));
        foreach ($valueSet->string as $value)
        {
            $this->outputStatusMessage($value);
        }
        $this->outputStatusMessage("* * * End OutputResponseMode * * *");
    }

    /**
     * @param $valueSets
     * @return void
     */
    public function outputArrayOfResponseMode($valueSets): void
    {
        if(count((array)$valueSets) == 0)
        {
            return;
        }
        $this->outputStatusMessage("* * * Begin OutputArrayOfResponseMode * * *");
        foreach ($valueSets->ResponseMode as $valueSet)
        {
            $this->outputResponseMode($valueSet);
        }
        $this->outputStatusMessage("* * * End OutputArrayOfResponseMode * * *");
    }

    /**
     * @param $message
     * @return void
     */
    public function outputStatusMessage($message): void
    {
        printf(" % s\n", $message);
    }

    /**
     * @param $items
     * @return void
     */
    public function outputArrayOfString($items): void
    {
        if(count((array)$items) == 0 || !isset($items->string))
        {
            return;
        }
        $this->outputStatusMessage("* * * Begin OutputArrayOfString * * *");
        foreach ($items->string as $item)
        {
            $this->outputStatusMessage(sprintf("%s", $item));
        }
        $this->outputStatusMessage("* * * End OutputArrayOfString * * *");
    }

    /**
     * @param $items
     * @return void
     */
    public function outputArrayOfLong($items): void
    {
        if(count((array)$items) == 0 || !isset($items->long))
        {
            return;
        }
        $this->outputStatusMessage("* * * Begin OutputArrayOfLong * * *");
        foreach ($items->long as $item)
        {
            $this->outputStatusMessage(sprintf("%s", $item));
        }
        $this->outputStatusMessage("* * * End OutputArrayOfLong * * *");
    }

    /**
     * @param $items
     * @return void
     */
    public function outputArrayOfInt($items): void
    {
        if(count((array)$items) == 0 || !isset($items->int))
        {
            return;
        }
        $this->outputStatusMessage("* * * Begin OutputArrayOfInt * * *");
        foreach ($items->int as $item)
        {
            $this->outputStatusMessage(sprintf("%s", $item));
        }
        $this->outputStatusMessage("* * * End OutputArrayOfInt * * *");
    }
}

