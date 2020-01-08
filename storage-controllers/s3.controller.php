<?php

/**
 * Config needed
 * 
 * S3_BUCKET
 * S3_ACCESS_KEY
 * S3_SECRET_KEY
 * (optional) S3_ENDPOINT
 */

class S3Storage implements StorageController
{
	private $s3;
	function connect(){
		require ROOT.DS.'storage-controllers'.DS.'s3'.DS.'aws-autoloader.php';
		$this->s3 = new Aws\S3\S3Client([
			'version' => 'latest',
			'region'  => 'us-east-1',
			'endpoint' => S3_ENDPOINT,
			'use_path_style_endpoint' => true,
			'credentials' => [
					'key'    => S3_ACCESS_KEY,
					'secret' => S3_SECRET_KEY,
				],
		]);
	}

    function isEnabled()
    {
        return (defined('S3_BUCKET') && S3_BUCKET);
    }
    
    function hashExists($hash)
    {
		if(!$this->s3)$this->connect();

		return $this->s3->doesObjectExist(S3_BUCKET,$hash);
	}
	
	function getItems()
	{
		if(!$this->s3)$this->connect();

		$KeyCount = 9999;
		$keys = 10;
		$lastkey = false;
		$count = 0;
		$items = array();
		while($KeyCount>=$keys)
		{
			$objects = $this->s3->listObjectsV2([
				'Bucket' => S3_BUCKET,
				'MaxKeys'=> $keys,
				'StartAfter'=>($lastkey?$lastkey:'')
			]);

			++$count;
			foreach ($objects['Contents'] as $object){
				//echo $count." {$object['Key']}\n";
				$lastkey = $object['Key'];
				$items[] = $lastkey;
			}

			if($dev===true)
				echo "Got ".($count*$keys)." files                  \r";

			$KeyCount = $objects['KeyCount'];
		}

		return $items;
	}

    function pullFile($hash,$location)
    {
		if(!$this->s3)$this->connect();

		if(!$this->hashExists($hash)) return false;

		$this->s3->getObject([
			'Bucket' => S3_BUCKET,
			'Key'    => $hash,
			'SaveAs' => $location
	   ]);
	   return true;
    }

    function pushFile($source,$hash)
    {
		if(!$this->s3)$this->connect();
		
		$this->s3->putObject([
			'Bucket' => S3_BUCKET,
			'Key'    => $hash,
			'SourceFile' => $source
		]);

		return true;
    }

    function deleteFile($hash)
    {
		if(!$this->s3)$this->connect();

		$this->s3->deleteObject([
			'Bucket' => S3_BUCKET,
			'Key'    => $hash
		]);
    }
}