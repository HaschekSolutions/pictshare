<?php 

/**
 * Backblaze B2 wrapper without external depenecies
 *
 * @author Christian Haschek <christian@haschek.at>
 */

class Backblaze
{
    private $token;
    private $apiURL;
    private $bucket;
    private $dlURL;
    private $ulURL;
    private $ulToken;
    private $bucket_name;
    private $files;

    function __construct()
    {
        if( BACKBLAZE !== true || !defined('BACKBLAZE_ID') || !defined('BACKBLAZE_KEY') || !defined('BACKBLAZE_BUCKET_ID'))
            return;
        $this->authorize();
        $this->bucket = BACKBLAZE_BUCKET_ID;
        $this->bucket_name = (( defined('BACKBLAZE_BUCKET_NAME') && BACKBLAZE_BUCKET_NAME != "")?BACKBLAZE_BUCKET_NAME:$this->bucketIdToName($bucket));
    }

    function authorize()
    {
        $account_id = BACKBLAZE_ID; // Obtained from your B2 account page
        $application_key = BACKBLAZE_KEY; // Obtained from your B2 account page
        $credentials = base64_encode($account_id . ":" . $application_key);
        $url = "https://api.backblazeb2.com/b2api/v1/b2_authorize_account";

        $session = curl_init($url);

        // Add headers
        $headers = array();
        $headers[] = "Accept: application/json";
        $headers[] = "Authorization: Basic " . $credentials;
        curl_setopt($session, CURLOPT_HTTPHEADER, $headers);  // Add headers

        curl_setopt($session, CURLOPT_HTTPGET, true);  // HTTP GET
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true); // Receive server response
        $server_output = curl_exec($session);
        curl_close ($session);
        $data = json_decode($server_output,true);
        
        $this->token = $data['authorizationToken'];
        $this->apiURL = $data['apiUrl'];
        $this->dlURL = $data['downloadUrl'];
    }

    function upload($hash)
    {
        if(!$this->ulURL)
            $this->getUploadInfo();
        $file_name = $hash;
        $my_file = ROOT.DS.'upload'.DS.$hash.DS.$hash;
        $handle = fopen($my_file, 'r');
        $read_file = fread($handle,filesize($my_file));

        $upload_url = $this->ulURL; // Provided by b2_get_upload_url
        $upload_auth_token = $this->ulToken; // Provided by b2_get_upload_url
        $bucket_id = $this->bucket;  // The ID of the bucket
        $content_type = "text/plain";
        $sha1_of_file_data = sha1_file($my_file);

        $session = curl_init($upload_url);

        // Add read file as post field
        curl_setopt($session, CURLOPT_POSTFIELDS, $read_file); 

        // Add headers
        $headers = array();
        $headers[] = "Authorization: " . $upload_auth_token;
        $headers[] = "X-Bz-File-Name: " . $file_name;
        $headers[] = "Content-Type: " . $content_type;
        $headers[] = "X-Bz-Content-Sha1: " . $sha1_of_file_data;
        curl_setopt($session, CURLOPT_HTTPHEADER, $headers); 

        curl_setopt($session, CURLOPT_POST, true); // HTTP POST
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);  // Receive server response
        $server_output = curl_exec($session); // Let's do this!
        curl_close ($session); // Clean up
        //var_dump($server_output); // Tell me about the rabbits, George!
    }

    function getUploadInfo()
    {
        $api_url = $this->apiURL; // From b2_authorize_account call
        $auth_token = $this->token; // From b2_authorize_account call
        $bucket_id = $this->bucket;  // The ID of the bucket you want to upload to

        $session = curl_init($api_url .  "/b2api/v1/b2_get_upload_url");

        // Add post fields
        $data = array("bucketId" => $bucket_id);
        $post_fields = json_encode($data);
        curl_setopt($session, CURLOPT_POSTFIELDS, $post_fields); 

        // Add headers
        $headers = array();
        $headers[] = "Authorization: " . $auth_token;
        curl_setopt($session, CURLOPT_HTTPHEADER, $headers); 

        curl_setopt($session, CURLOPT_POST, true); // HTTP POST
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);  // Receive server response
        $server_output = curl_exec($session); // Let's do this!
        curl_close ($session); // Clean up
        $data = json_decode($server_output,true); // Tell me about the rabbits, George!
        $this->ulURL = $data['uploadUrl'];
        //var_dump("upload url at load: ".$data['uploadUrl']);
        $this->ulToken = $data['authorizationToken'];

        //var_dump($data);
    }

    function download($hash)
    {
        if(file_exists(ROOT.DS.'upload'.DS.$hash.DS.$hash)) return false;
        $download_url = $this->dlURL; // From b2_authorize_account call
        $bucket_name = $this->bucket_name;  // The NAME of the bucket you want to download from
        $file_name = $hash; // The name of the file you want to download
        $auth_token = $this->token; // From b2_authorize_account call
        $uri = $download_url . "/file/" . $bucket_name . "/" . $file_name;

        $session = curl_init($uri);

        // Add headers
        $headers = array();
        $headers[] = "Authorization: " . $auth_token;
        curl_setopt($session, CURLOPT_HTTPHEADER, $headers); 

        curl_setopt($session, CURLOPT_HTTPGET, true); // HTTP POST
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);  // Receive server response
        $server_output = curl_exec($session); // Let's do this!
        curl_close ($session); // Clean up
        $is_binary = preg_match('~[^\x20-\x7E\t\r\n]~', $server_output); // Tell me about the rabbits, George!
        if(!$is_binary) return false;

        mkdir(ROOT.DS.'upload'.DS.$hash);
		$file = ROOT.DS.'upload'.DS.$hash.DS.$hash;
		
        file_put_contents($file, $server_output);
        return true;
    }

    function bucketIdToName($bucket)
    {
        $api_url = $this->apiURL; // From b2_authorize_account call
        $auth_token = $this->token; // From b2_authorize_account call
        $account_id = BACKBLAZE_ID;

        $session = curl_init($api_url .  "/b2api/v1/b2_list_buckets");

        // Add post fields
        $data = array("accountId" => $account_id);
        $post_fields = json_encode($data);
        curl_setopt($session, CURLOPT_POSTFIELDS, $post_fields); 

        // Add headers
        $headers = array();
        $headers[] = "Authorization: " . $auth_token;
        curl_setopt($session, CURLOPT_HTTPHEADER, $headers); 

        curl_setopt($session, CURLOPT_POST, true); // HTTP POST
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);  // Receive server response
        $server_output = curl_exec($session); // Let's do this!
        curl_close ($session); // Clean up
        $data = json_decode($server_output,true); // Tell me about the rabbits, George!
        if(is_array($data))
            foreach($data['buckets'] as $bucket)
            {
                if($bucket['bucketId']==$this->bucket) return $bucket['bucketName'];
            }
        return false;
    }

    function deleteFile($hash,$file_id=false)
    {
        $api_url = $this->apiURL; // From b2_authorize_account call
        $auth_token = $this->token; // From b2_authorize_account call
        $file_name = $hash; // The file name of the file you want to delete
        if(!$file_id)
            $file_id = $this->fileExistsInBucket($hash);

        $session = curl_init($api_url .  "/b2api/v1/b2_delete_file_version");

        // Add post fields
        $data = array("fileId" => $file_id, "fileName" => $file_name);
        $post_fields = json_encode($data);
        curl_setopt($session, CURLOPT_POSTFIELDS, $post_fields); 

        // Add headers
        $headers = array();
        $headers[] = "Authorization: " . $auth_token;
        curl_setopt($session, CURLOPT_HTTPHEADER, $headers); 

        curl_setopt($session, CURLOPT_POST, true); // HTTP POST
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);  // Receive server response
        $server_output = curl_exec($session); // Let's do this!
        curl_close ($session); // Clean up
    }

    function fileExistsInBucket($hash)
    {
        $api_url = $this->apiURL; // From b2_authorize_account call
        $auth_token = $this->token; // From b2_authorize_account call
        $bucket_id = $this->bucket;  // The ID of the bucket

        $session = curl_init($api_url .  "/b2api/v1/b2_list_file_names");

        // Add post fields
        $data = array("bucketId" => $bucket_id,
                      "startFileName" => $hash);
        $post_fields = json_encode($data);
        curl_setopt($session, CURLOPT_POSTFIELDS, $post_fields); 

        // Add headers
        $headers = array();
        $headers[] = "Authorization: " . $auth_token;
        curl_setopt($session, CURLOPT_HTTPHEADER, $headers); 

        curl_setopt($session, CURLOPT_POST, true); // HTTP POST
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);  // Receive server response
        $server_output = curl_exec($session); // Let's do this!
        curl_close ($session); // Clean up
        $data = json_decode($server_output,true);

        foreach($data['files'] as $file)
        {
            //it's either the first one or it doesn't exist
            if($file['fileName']==$hash)
                return $file['fileId'];
            else return false;
        }

        return false;
    }

    function getAllFilesInBucket($startFileName=null)
    {
        $api_url = $this->apiURL; // From b2_authorize_account call
        $auth_token = $this->token; // From b2_authorize_account call
        $bucket_id = $this->bucket;  // The ID of the bucket

        $session = curl_init($api_url .  "/b2api/v1/b2_list_file_names");

        // Add post fields
        $data = array("bucketId" => $bucket_id,
                      "startFileName" => $startFileName);
        $post_fields = json_encode($data);
        curl_setopt($session, CURLOPT_POSTFIELDS, $post_fields); 

        // Add headers
        $headers = array();
        $headers[] = "Authorization: " . $auth_token;
        curl_setopt($session, CURLOPT_HTTPHEADER, $headers); 

        curl_setopt($session, CURLOPT_POST, true); // HTTP POST
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);  // Receive server response
        $server_output = curl_exec($session); // Let's do this!
        curl_close ($session); // Clean up
        $data = json_decode($server_output,true);

        foreach($data['files'] as $file)
        {
            $name = $file['fileName'];
            $id = $file['fileId'];
            $this->files[$name] = $id;
        }

        if($data['nextFileName'])
            $this->getAllFilesInBucket($data['nextFileName']);

        return $this->files;
    }
}