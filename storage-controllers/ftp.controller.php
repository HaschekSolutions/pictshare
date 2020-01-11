<?php 

class FTPStorage implements StorageController
{
    private $connection;
    private $login;

    function __destruct()
    {
        if($this->connection)
        ftp_close($this->connection);
    }

    function connect()
    {
        if(!$this->connection)
            $this->connection = ftp_connect(FTP_SERVER);
        if(!$this->login)
            $this->login = ftp_login($this->connection, FTP_USER, FTP_PASS);

        // Was the connection successful?
        if ((!$this->connection) || (!$this->login)) {
            $this->connection = false;
            return false;
        }
        return true;
    }

    function isEnabled()
    {
        return (defined('FTP_SERVER') && FTP_SERVER &&
        defined('FTP_USER') && FTP_USER &&
        defined('FTP_PASS') && FTP_PASS);
    }
    
    function hashExists($hash)
    {
        if(!$this->connect()) return null;
        $subdir = $this->hashToDir($hash);
        $ftpfilepath = FTP_BASEDIR.$subdir.'/'.$hash;
        if(@ftp_chdir($this->connection, FTP_BASEDIR.$subdir))
            return (ftp_size($this->connection,$ftpfilepath)>0?true:false);
        return false;
    }

    function getItems($dev=false)
    {
        if(!$this->connect()) return false;
        ftp_pasv($this->connection, TRUE);
        return $this->ftp_list_files_recursive(FTP_BASEDIR);
    }

    function pullFile($hash,$location)
    {
        if(!$this->connect()) return false;
        $subdir = $this->hashToDir($hash);
        $ftpfilepath = FTP_BASEDIR.$subdir.'/'.$hash;
        return ftp_get($this->connection, $location, $ftpfilepath, FTP_BINARY);
    }

    function pushFile($source,$hash)
    {
        if(!$this->connect()) return false;
        $subdir = $this->hashToDir($hash);
        $ftpfilepath = FTP_BASEDIR.$subdir.'/'.$hash;
        $this->ftp_mksubdirs($subdir);

        return ftp_put($this->connection, $ftpfilepath, $source, FTP_BINARY);
    }

    function deleteFile($hash) 
    {
        if(!$this->connect()) return false;
        $ftpfilepath = FTP_BASEDIR.$hash;
        return (ftp_delete($this->connection,$ftpfilepath)?true:false);
    }

    function hashToDir($hash)
    {
        $md5 = md5($hash);
        $dir = $md5[0].'/'.$md5[1].'/'.$md5[2];

        return $dir;
    }

    function ftp_mksubdirs($ftpath)
    {
        if(!$this->connect()) return false;
        @ftp_chdir($this->connection, FTP_BASEDIR); 
        $parts = array_filter(explode('/',$ftpath));
        foreach($parts as $part){
           if(!@ftp_chdir($this->connection, $part)){
              ftp_mkdir($this->connection, $part);
              ftp_chdir($this->connection, $part);
           }
        }
    }

    function ftp_list_files_recursive($path)
    {
        if(!$this->connect()) return false;
        $items = ftp_mlsd($this->connection, $path);
        $result = array();

        if(is_array($items))
        foreach ($items as $item)
        {
            $name = $item['name'];
            $type = $item['type'];
            $filepath = $path.'/'. $name;

            if ($type == 'dir')
            {
                $result =
                    array_merge($result, $this->ftp_list_files_recursive($filepath));
            }
            else if(mightBeAHash($name) || endswith($name,'.enc'))
            {
                $result[] = $name;
            }
        }
        return $result;
    }
}