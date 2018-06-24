<?php

declare(strict_types=1);

namespace PictShare\Classes\StorageProviders;

use PictShare\Classes\Configuration;

/**
 * Backblaze B2 wrapper without external dependencies.
 *
 * @author Christian Haschek <christian@haschek.at>
 *
 * @TODO Refactor all the duplicate curl* calls.
 * @TODO Remove the local filesystem dependencies. Let those be handled by LocalStorageProvider.
 */
class BackblazeStorageProvider implements StorageProviderInterface
{
    const GET_UPLOAD_URL_ENDPOINT      = '/b2api/v1/b2_get_upload_url';
    const LIST_FILE_NAMES_ENDPOINT     = '/b2api/v1/b2_list_file_names';
    const LIST_BUCKETS_ENDPOINT        = '/b2api/v1/b2_list_buckets';
    const DELETE_FILE_VERSION_ENDPOINT = '/b2api/v1/b2_delete_file_version';
    const AUTHORIZE_URL                = 'https://api.backblazeb2.com/b2api/v1/b2_authorize_account';

    /**
     * @var string
     */
    private $token;

    /**
     * @var string
     */
    private $ulToken;

    /**
     * @var string
     */
    private $apiURL;

    /**
     * @var string
     */
    private $dlURL;

    /**
     * @var string
     */
    private $ulURL;

    /**
     * @var mixed
     */
    private $backblazeId;

    /**
     * @var string
     */
    private $backblazeKey;

    /**
     * @var int
     */
    private $bucketId;

    /**
     * @var string
     */
    private $bucketName;

    /**
     * @var array
     */
    private $files;

    /**
     * @var string
     */
    private $localBaseDir;


    /**
     * Backblaze constructor.
     */
    public function __construct()
    {
        if (!$this->isEnabled()) {
            return;
        }

        $this->authorize();

        $this->backblazeId  = Configuration::getValue(Configuration::BACKBLAZE_ID);
        $this->backblazeKey = Configuration::getValue(Configuration::BACKBLAZE_KEY);
        $this->bucketId     = Configuration::getValue(Configuration::BACKBLAZE_BUCKET_ID);
        $this->bucketName   = Configuration::getValue(Configuration::BACKBLAZE_BUCKET_NAME)
            ?? $this->bucketIdToName($this->bucketId);

        $this->localBaseDir = UPLOAD_DIR;
    }

    /**
     * @inheritdoc
     *
     * @throws \RuntimeException
     */
    final public function get(string $originalFileName, string $variationFileName)
    {
        $uri     = $this->dlURL . '/file/' . $this->bucketName . '/' . $variationFileName;
        $session = curl_init($uri);

        $headers = [
            'Authorization: ' . $this->token,
        ];

        curl_setopt($session, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($session, CURLOPT_HTTPGET, true);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($session);

        curl_close($session);

        $isBinary = preg_match('~[^\x20-\x7E\t\r\n]~', $response);

        if (!$isBinary) {
            return false;
        }

        return $response;
    }

    /**
     * @inheritdoc
     */
    final public function save(string $originalFileName, string $variationFileName, string $fileContent)
    {
        if (!$this->ulURL) {
            $this->getUploadInfo();
        }

        $fileName = $this->localBaseDir . $originalFileName . '/' . $variationFileName;
        $session  = curl_init($this->ulURL);

        curl_setopt($session, CURLOPT_POSTFIELDS, $fileContent);

        $headers = [
            'Authorization: '     . $this->ulToken,
            'X-Bz-File-Name: '    . $variationFileName,
            'X-Bz-Content-Sha1: ' . sha1_file($fileName),
            'Content-Type: text/plain',
        ];

        curl_setopt($session, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($session, CURLOPT_POST, true);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
        curl_close($session);
    }

    /**
     * @inheritdoc
     */
    final public function delete(string $hash)
    {
        $fileId  = $this->fileExistsInBucket($hash);
        $session = curl_init($this->apiURL . self::DELETE_FILE_VERSION_ENDPOINT);

        $data = [
            'fileId'   => $fileId,
            'fileName' => $hash,
        ];
        $postFields = json_encode($data);

        curl_setopt($session, CURLOPT_POSTFIELDS, $postFields);

        $headers = [
            'Authorization: ' . $this->token,
        ];

        curl_setopt($session, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($session, CURLOPT_POST, true);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
        curl_exec($session);
        curl_close($session);
    }

    /**
     * @inheritdoc
     */
    final public function fileExists(string $fileName): bool
    {
        return $this->fileExistsInBucket($fileName) !== null;
    }

    /**
     * @inheritdoc
     */
    final public function isEnabled(): bool
    {
        return Configuration::isBackblazeEnabled();
    }

    /**
     * @param string|null $startFileName
     *
     * @return array
     */
    final public function getAllFilesInBucket(string $startFileName = null): array
    {
        $session = curl_init($this->apiURL . self::LIST_FILE_NAMES_ENDPOINT);

        $data = [
            'bucketId'      => $this->bucketId,
            'startFileName' => $startFileName
        ];
        $postFields = json_encode($data);

        curl_setopt($session, CURLOPT_POSTFIELDS, $postFields);

        $headers = [
            'Authorization: ' . $this->token,
        ];

        curl_setopt($session, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($session, CURLOPT_POST, true);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($session);

        curl_close($session);

        $data = json_decode($response, true);

        foreach ($data['files'] as $file) {
            $name = $file['fileName'];
            $id   = $file['fileId'];
            $this->files[$name] = $id;
        }

        if ($data['nextFileName']) {
            $this->getAllFilesInBucket($data['nextFileName']);
        }

        return $this->files;
    }

    /**
     * Authorize with BackBlaze.
     */
    private function authorize()
    {
        $credentials = base64_encode($this->backblazeId . ':' . $this->backblazeKey);
        $session     = curl_init(self::AUTHORIZE_URL);

        $headers = [
            'Accept: application/json',
            'Authorization: Basic ' . $credentials,
        ];

        curl_setopt($session, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($session, CURLOPT_HTTPGET, true);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($session);

        curl_close($session);

        $data = json_decode($response, true);

        $this->token  = $data['authorizationToken'];
        $this->apiURL = $data['apiUrl'];
        $this->dlURL  = $data['downloadUrl'];
    }

    /**
     * @param int $bucketId
     *
     * @return string|null
     */
    private function bucketIdToName(int $bucketId)
    {
        $session = curl_init($this->apiURL . self::LIST_BUCKETS_ENDPOINT);

        $data = ['accountId' => $this->backblazeId];
        $postFields = json_encode($data);

        curl_setopt($session, CURLOPT_POSTFIELDS, $postFields);

        $headers = [
            'Authorization: ' . $this->token,
        ];

        curl_setopt($session, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($session, CURLOPT_POST, true);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($session);

        curl_close($session);

        $data = json_decode($response, true);

        if (\is_array($data)) {
            foreach ($data['buckets'] as $bucket) {
                if ((int) $bucket['bucketId'] === $bucketId) {
                    return $bucket['bucketName'];
                }
            }
        }

        return null;
    }

    /**
     * @param string $hash
     *
     * @return int|null
     */
    private function fileExistsInBucket(string $hash)
    {
        $session = curl_init($this->apiURL . self::LIST_FILE_NAMES_ENDPOINT);

        $data = [
            'bucketId'      => $this->bucketId,
            'startFileName' => $hash
        ];
        $postFields = json_encode($data);

        curl_setopt($session, CURLOPT_POSTFIELDS, $postFields);

        $headers = [
            'Authorization: ' . $this->token,
        ];

        curl_setopt($session, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($session, CURLOPT_POST, true);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($session);

        curl_close($session);

        $data = json_decode($response, true);
        $file = \reset($data['files']); // It's either the first one or it doesn't exist.

        if ($file['fileName'] === $hash) {
            return (int) $file['fileId'];
        }

        return null;
    }

    /**
     * Get the upload URL and auth token.
     */
    private function getUploadInfo()
    {
        $session = curl_init($this->apiURL . self::GET_UPLOAD_URL_ENDPOINT);

        $data = ['bucketId' => $this->bucketId];
        $postFields = json_encode($data);

        curl_setopt($session, CURLOPT_POSTFIELDS, $postFields);

        $headers = [
            'Authorization: ' . $this->token,
        ];

        curl_setopt($session, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($session, CURLOPT_POST, true);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($session);

        curl_close($session);

        $data = json_decode($response, true);

        $this->ulURL   = $data['uploadUrl'];
        $this->ulToken = $data['authorizationToken'];
    }
}
