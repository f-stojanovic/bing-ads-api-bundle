<?php

namespace Coddict\BingAdsApiBundle\Service\Customer;

use Coddict\BingAdsApiBundle\Exception\UploadFailedException;
use Coddict\BingAdsApiBundle\Service\Authentication\Auth;
use Coddict\BingAdsApiBundle\Service\Bulk\BulkHelper;
use Coddict\BingAdsApiBundle\SoapFault;
use Exception;
use League\Csv\Writer;
use Microsoft\BingAds\Auth\AuthorizationData;
use Microsoft\BingAds\V13\Bulk\ResponseMode;
use SplTempFileObject;
use ZipArchive;
use Psr\Log\LoggerInterface;

class CustomerListHelper
{
    public function __construct(
        private readonly Auth               $auth,
        private readonly AuthorizationData  $authorizationData,
        private readonly BulkHelper         $bulkHelper,
        private readonly LoggerInterface    $logger
    ) { }

    /**
     * @param array $emails
     * @param string $listId
     * @return void
     * @throws Exception
     */
    public function addEmailsToBingAdsList(array $emails, string $listId): void
    {
        $this->auth->authenticate();

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

    /**
     * @param array $emails
     * @param string $listId
     * @return void
     * @throws Exception
     */
    public function removeEmailsFomBingAdsList(array $emails, string $listId): void
    {
        $this->auth->authenticate();

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

    /**
     * @param string $type
     * @param array $header
     * @param array $records
     * @return void
     * @throws Exception
     */
    private function uploadDataToBing(string $type, array $header, array $records): void
    {
        try {
            $csv = Writer::createFromFileObject(new SplTempFileObject());
            $csv->insertOne($header);
            $csv->insertAll($records);

            $bulkFilePath = $csv->getPathname();

            $responseMode = ResponseMode::ErrorsAndResults;
            $uploadResponse = $this->bulkHelper->getBulkUploadUrl(
                $responseMode,
                $this->authorizationData->AccountId
            );

            $uploadRequestId = $uploadResponse->RequestId;
            $uploadUrl = $uploadResponse->UploadUrl;

            $this->logger->debug("-----\r\nGetBulkUploadUrl:\r\n");
            $this->logger->debug(sprintf("RequestId: %s\r\n", $uploadRequestId));
            $this->logger->debug(sprintf("UploadUrl: %s\r\n", $uploadUrl));
            $this->logger->debug(sprintf("-----\r\nUploading file from %s.\r\n", $bulkFilePath));

            $uploadSuccess = $this->uploadFile($uploadUrl, $bulkFilePath);

            if (!$uploadSuccess){
                throw new UploadFailedException('Upload failed');
                return;
            }

        } catch (\SoapFault $e) {
            throw new UploadFailedException("An issue occurred while communicating with the Bing API.", 0, $e);
        }
    }

    /**
     * @param string $fromZipArchive
     * @param string $toExtractedFile
     * @return void
     * @throws Exception
     */
    private function decompressFile(string $fromZipArchive, string $toExtractedFile): void
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

    /**
     * @param string $fromExtractedFile
     * @param string $toZipArchive
     * @return void
     * @throws Exception
     */
    private function compressFile(string $fromExtractedFile, string $toZipArchive): void
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

    /**
     * @param string $downloadUrl
     * @param string $filePath
     * @return void
     * @throws Exception
     */
    private function downloadFile(string $downloadUrl, string $filePath): void
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

    /**
     * @param string $uploadUrl
     * @param string $filePath
     * @return bool
     * @throws Exception
     */
    private function uploadFile(string $uploadUrl, string $filePath): bool
    {
        $ch = curl_init($uploadUrl);

        curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);

        if (!isset($this->authorizationData)) {
            throw new Exception("AuthorizationData is not set.");
        }

        // Set the authorization headers.
        if (isset($this->authorizationData->Authentication->Type)) {
            $authorizationHeaders = array();
            $authorizationHeaders[] = "DeveloperToken: " . $this->authorizationData->DeveloperToken;
            $authorizationHeaders[] = "CustomerId: " . $this->authorizationData->CustomerId;
            $authorizationHeaders[] = "CustomerAccountId: " . $this->authorizationData->AccountId;

            if (isset($this->authorizationData->Authentication->OAuthTokens)) {
                $authorizationHeaders[] = "AuthenticationToken: " . $this->authorizationData->Authentication->OAuthTokens->AccessToken;
            }
        }
        else {
            throw new Exception("Invalid Authentication Type.");
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $authorizationHeaders);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);

        $file = curl_file_create($filePath, "text/csv", "payload.csv");
        curl_setopt($ch, CURLOPT_POSTFIELDS, array("payload" => $file));
        $result = curl_exec($ch);
        $info = curl_getinfo($ch);
        $http_code = $info['http_code'];

        if (curl_errno($ch)) {
            $this->logger->debug("Curl Error: " . curl_error($ch) . "\r\n");
        }
        else {
            $this->logger->debug("Upload Result:\n" . $result . "\r\n");
            $this->logger->debug("HTTP Result Code:\n" . $http_code . "\r\n");
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