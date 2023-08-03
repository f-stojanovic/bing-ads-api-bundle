<?php

namespace Coddict\BingAdsApiBundle\Service\Customer;

use Coddict\BingAdsApiBundle\Service\Authentication\Auth;
use Coddict\BingAdsApiBundle\Service\Bulk\BulkHelper;
use Coddict\BingAdsApiBundle\SoapFault;
use Exception;
use League\Csv\Reader;
use League\Csv\Writer;
use Microsoft\BingAds\V13\Bulk\ResponseMode;
use Symfony\Component\DependencyInjection\ContainerInterface;
use ZipArchive;

class CustomerListHelper
{
    const DEBUG = true;

    private ?Auth $auth;
    private readonly ContainerInterface $container;

    private function getAuth(): Auth
    {
        if (!$this->auth) {
            $this->auth = new Auth();
            $this->auth->authenticate();
        }
        return $this->auth;
    }

    public function addEmailsToBingAdsList(array $emails, $listId): void
    {
        $this->getAuth();

        if (empty($emails)) {
            return;
        }

        $header = ['Type', 'Status', 'Id', 'Parent Id', 'Client Id', 'Modified Time', 'Name', 'Description', 'Scope', 'Audience', 'Action Type', 'Sub Type', 'Text'];
        $records = [
            ['Format Version', '', '', '', '', '', '6.0', '', '', '', '', '', ''],
            ['Customer List', 'Active', $listId, '', '', '', '', '', '', '', 'Replace', '', ''],
        ];

        $customerListItems = [];
        foreach ($emails as $email) {
            $customerListItems[] = [
                'Customer List Item', '', '', $listId, '', '', '', '', '', '', '', 'Email', hash('sha256', $email)
            ];
        }

        $records = array_merge($records, $customerListItems);

        $this->uploadDataToBing('add', $header, $records);
    }

    public function removeEmailsFomBingAdsList(array $emails, $listId): void
    {
        $this->getAuth();

        if (empty($emails)) {
            return;
        }

        $header = ['Type', 'Status', 'Id', 'Parent Id', 'Client Id', 'Modified Time', 'Name', 'Description', 'Scope', 'Audience', 'Action Type', 'Sub Type', 'Text'];

        $records = [
            ['Format Version', '', '', '', '', '', '6.0', '', '', '', '', '', ''],
            ['Customer List', 'Active', $listId, '', '', '', '', '', '', '', 'Remove', '', ''],
        ];

        $listItemToDelete = [];
        foreach ($emails as $email) {
            $listItemToDelete[] = [
                'Customer List Item', '', '', $listId, '', '', '', '', '', '', '', 'Email', hash('sha256', $email)
            ];
        }

        $records = array_merge($records, $listItemToDelete);

        $this->uploadDataToBing('remove', $header, $records);
    }

    private function uploadDataToBing(string $type, array $header, array $records): void
    {
        try {
            $uploadDirectory = $this->container->getParameter('bing_ads_api.upload_directory');
            $csv = Writer::createFromPath($uploadDirectory . "/emails_to_{$type}.csv", 'w');
            $csv->insertOne($header);
            $csv->insertAll($records);

            $bulkFilePath = $uploadDirectory . "/emails_to_{$type}.zip";
            $this->compressFile($uploadDirectory . "/emails_to_{$type}.csv", $bulkFilePath);
            
            $responseMode = ResponseMode::ErrorsAndResults;
            $uploadResponse = BulkHelper::getBulkUploadUrl(
                $responseMode,
                Auth::$AuthorizationData->AccountId
            );
            
            $uploadRequestId = $uploadResponse->RequestId;
            $uploadUrl = $uploadResponse->UploadUrl;

            if (self::DEBUG) {
                print("-----\r\nGetBulkUploadUrl:\r\n");
                printf("RequestId: %s\r\n", $uploadRequestId);
                printf("UploadUrl: %s\r\n", $uploadUrl);
                printf("-----\r\nUploading file from %s.\r\n", $bulkFilePath);  
            }
            
            $uploadSuccess = $this->uploadFile($uploadUrl, $bulkFilePath);
            
            // If the file was not uploaded, do not continue to poll for results.
            if (!$uploadSuccess){
                throw new Exception('Upload failed');
                return;
            }

            $waitTime = 10; 
            // This sample polls every 30 seconds up to 5 minutes.
            // In production you may poll the status every 1 to 2 minutes for up to one hour.
            // If the call succeeds, stop polling. If the call or
            // download fails, the call throws a fault.
            for ($i = 0; $i < 5; $i++) {
                sleep($waitTime);
                
                // Get the upload request status.
                $getBulkUploadStatusResponse = BulkHelper::getBulkUploadStatus(
                    $uploadRequestId
                );

                $requestStatus = $getBulkUploadStatusResponse->RequestStatus;
                $resultFileUrl = $getBulkUploadStatusResponse->ResultFileUrl;

                if (self::DEBUG) {
                    print("-----\r\nGetBulkUploadStatus:\r\n");
                    printf("PercentComplete: %s\r\n", $getBulkUploadStatusResponse->PercentComplete);
                    printf("RequestStatus: %s\r\n", $requestStatus);
                    printf("ResultFileUrl: %s\r\n", $resultFileUrl);
                }
                
                if (($requestStatus != null) && (($requestStatus == "Completed") || ($requestStatus == "CompletedWithErrors"))) {
                    $uploadSuccess = true;
                    break;
                }
            }
            
            if ($uploadSuccess) {
                // Get the upload result file.
                $uploadResultFilePath = __DIR__ . "/../storage/results_{$type}.zip";

                if (self::DEBUG) {
                    printf("-----\r\nDownloading the upload result file from %s...\r\n", $resultFileUrl);
                }

                $this->downloadFile($resultFileUrl, $uploadResultFilePath);

                if (self::DEBUG) {
                    printf("The upload result file was written to %s.\r\n", $uploadResultFilePath);
                }

                $this->decompressFile($uploadResultFilePath, __DIR__ . "/../storage/results_{$type}.csv");

                $files = glob(__DIR__ . "/../storage/*.csv");

                foreach ($files as $file) {
                    $reader = Reader::createFromPath($file);
                    $reader->setHeaderOffset(0);

                    $data = $reader->jsonSerialize();

                    array_shift($data);

                    $errorLines = array_filter($data, function ($item) {
                        return $item['Type'] == 'Customer List Item Error';
                    }, ARRAY_FILTER_USE_BOTH);

                    if (self::DEBUG) {
                        printf("Errors: " . count($errorLines));
                    }

                    // Move file to archive
                    rename($file, __DIR__ . '/../storage/archive/' . basename($file));

                    // Or if you prefer to delete use this
                    // unlink($file);
                }
            }
            else {
                throw new Exception("The request is taking longer than expected.\r\n" +
                    "Save the upload ID (%s) and try again later.", $uploadRequestId);
            }
        } catch (SoapFault $e) {
            echo $e->getMessage();
        } catch (Exception $e) {
            var_dump($e);
            echo $e->getMessage();
        }
    }

    private function decompressFile($fromZipArchive, $toExtractedFile): void
    {
        $archive = new ZipArchive;

        if ($archive->open($fromZipArchive) === TRUE) {
            $archive->extractTo(dirname($toExtractedFile));
            $archive->close();
        }
        else {
            throw new Exception ("Decompress operation from ZIP file failed.");
        }
    }

    private function compressFile($fromExtractedFile, $toZipArchive): void
    {
        $archive = new ZipArchive;

        if ($archive->open($toZipArchive, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            $archive->addFile($fromExtractedFile, basename($fromExtractedFile));
            $archive->close();
        }
        else {
            throw new Exception ("Compress operation to ZIP file failed.");
        }
    }

    private function downloadFile($downloadUrl, $filePath): void
    {
        if (!$reader = fopen($downloadUrl, 'rb')) {
            throw new Exception("Failed to open URL " . $downloadUrl . ".");
        }
    
        if (!$writer = fopen($filePath, 'wb')) {
            fclose($reader);
            throw new Exception("Failed to create ZIP file " . $filePath . ".");
        }
    
        $bufferSize = 100 * 1024;
    
        while (!feof($reader)) {
            if (false === ($buffer = fread($reader, $bufferSize))) {
                fclose($reader);
                fclose($writer);
                throw new Exception("Read operation from URL failed.");
            }
    
            if (fwrite($writer, $buffer) === false) {
                fclose($reader);
                fclose($writer);
                throw new Exception ("Write operation to ZIP file failed.");
            }
        }
    
        fclose($reader);
        fflush($writer);
        fclose($writer);
    }
    
    private function uploadFile($uploadUrl, $filePath): bool
    {
        date_default_timezone_set("UTC");
        $ch = curl_init($uploadUrl);
    
        curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
    
        if (!isset(Auth::$AuthorizationData)) {
            throw new Exception("AuthorizationData is not set.");
        }
        
        // Set the authorization headers.
        if (isset(Auth::$AuthorizationData->Authentication) && isset(Auth::$AuthorizationData->Authentication->Type)) {
            $authorizationHeaders = array();
            $authorizationHeaders[] = "DeveloperToken: " . Auth::$AuthorizationData->DeveloperToken;
            $authorizationHeaders[] = "CustomerId: " . Auth::$AuthorizationData->CustomerId;
            $authorizationHeaders[] = "CustomerAccountId: " . Auth::$AuthorizationData->AccountId;
            
            if (isset(Auth::$AuthorizationData->Authentication->OAuthTokens)) {
                $authorizationHeaders[] = "AuthenticationToken: " . Auth::$AuthorizationData->Authentication->OAuthTokens->AccessToken;
            }
        }
        else {
            throw new Exception("Invalid Authentication Type.");
        }
    
        curl_setopt($ch, CURLOPT_HTTPHEADER, $authorizationHeaders);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
    
        $file = curl_file_create($filePath, "application/zip", "payload.zip");
        curl_setopt($ch, CURLOPT_POSTFIELDS, array("payload" => $file));
    
        $result = curl_exec($ch);
        $info = curl_getinfo($ch);
        $http_code = $info['http_code'];
                  
        if (curl_errno($ch)) {
            if (self::DEBUG) {
                print "Curl Error: " . curl_error($ch) . "\r\n";
            }
        }
        else {
            if (self::DEBUG) {
                print "Upload Result:\n" . $result . "\r\n";
                print "HTTP Result Code:\n" . $http_code . "\r\n";
            }
        }
                 
        curl_close($ch);
        
        if ($http_code == 200){
            return true;
        }
        else {
            return false;
        }
    }
    
}
