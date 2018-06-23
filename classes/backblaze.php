<?php

declare(strict_types=1);

/**
 * Backblaze B2 wrapper without external dependencies.
 *
 * @author Christian Haschek <christian@haschek.at>
 *
 * @TODO Refactor all the duplicate curl* calls.
 */
class Backblaze
{
    const GET_UPLOAD_URL_ENDPOINT      = '/b2api/v1/b2_get_upload_url';
    const LIST_FILE_NAMES_ENDPOINT     = '/b2api/v1/b2_list_file_names';
    const LIST_BUCKETS_ENDPOINT        = '/b2api/v1/b2_list_buckets';
    const DELETE_FILE_VERSION_ENDPOINT = '/b2api/v1/b2_delete_file_version';
    const AUTHORIZE_URL                = 'https://api.backblazeb2.com/b2api/v1/b2_authorize_account';
    const LOCAL_UPLOAD_DIR             = 'upload';

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
        if (
            !defined('BACKBLAZE')
            || (defined('BACKBLAZE') && BACKBLAZE !== true)
            || !defined('BACKBLAZE_ID')
            || !defined('BACKBLAZE_KEY')
            || !defined('BACKBLAZE_BUCKET_ID')
        ) {
            return;
        }

        $this->authorize();

        $this->bucketId   = BACKBLAZE_BUCKET_ID;
        $this->bucketName = ((defined('BACKBLAZE_BUCKET_NAME') && BACKBLAZE_BUCKET_NAME !== '')
            ? BACKBLAZE_BUCKET_NAME
            : $this->bucketIdToName($this->bucketId));

        $this->localBaseDir = ROOT . DS . self::LOCAL_UPLOAD_DIR . DS;
    }

    /**
     * @param string $hash
     */
    public function upload(string $hash)
    {
        if (!$this->ulURL) {
            $this->getUploadInfo();
        }

        $fileName    = $this->localBaseDir . $hash . DS . $hash;
        $handle      = fopen($fileName, 'rb');
        $fileContent = fread($handle, filesize($fileName));
        $session     = curl_init($this->ulURL);

        curl_setopt($session, CURLOPT_POSTFIELDS, $fileContent);

        $headers = [
            'Authorization: '     . $this->ulToken,
            'X-Bz-File-Name: '    . $hash,
            'X-Bz-Content-Sha1: ' . sha1_file($fileName),
            'Content-Type: text/plain',
        ];

        curl_setopt($session, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($session, CURLOPT_POST, true);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
        curl_close($session);
    }

    /**
     * @param string $hash
     *
     * @return bool
     */
    public function download(string $hash): bool
    {
        if (file_exists($this->localBaseDir . $hash . DS . $hash)) {
            return false;
        }

        $uri     = $this->dlURL . '/file/' . $this->bucketName . '/' . $hash;
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

        $dirName = $this->localBaseDir . $hash;

        if (!mkdir($dirName) && !is_dir($dirName)) {
            return false;
        }

        $fileName = $dirName . DS . $hash;

        file_put_contents($fileName, $response);

        return true;
    }

    /**
     * @param string $hash
     */
    public function deleteFile(string $hash)
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
     * Authorize with BackBlaze.
     */
    public function authorize()
    {
        $credentials = base64_encode(BACKBLAZE_ID . ':' . BACKBLAZE_KEY);
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
     * @param string|null $startFileName
     *
     * @return array
     */
    public function getAllFilesInBucket(string $startFileName = null): array
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
     * @param int $bucketId
     *
     * @return string|null
     */
    private function bucketIdToName(int $bucketId)
    {
        $session = curl_init($this->apiURL . self::LIST_BUCKETS_ENDPOINT);

        $data = ['accountId' => BACKBLAZE_ID];
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

        if (is_array($data)) {
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
