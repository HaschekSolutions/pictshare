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
        $ftpfilepath = FTP_BASEDIR.$hash;
        return (ftp_size($this->connection,$ftpfilepath)>0?true:false);
    }

    function getItems($dev=false)
    {
        if(!$this->connect()) return false;
        $list = array();
        $files = ftp_mlsd($this->connection,FTP_BASEDIR);
        foreach ($files as $filearray)
        {
            $filename = $filearray['name'];
            if($filearray['type']=='dir' || startsWith($filename,'.') || !mightBeAHash($filename)) continue;
            $list[] = $filename;
        }

        return $list;
    }

    function pullFile($hash,$location)
    {
        if(!$this->connect()) return false;
        $ftpfilepath = FTP_BASEDIR.$hash;
        return ftp_get($this->connection, $location, $ftpfilepath, FTP_BINARY);
    }

    function pushFile($source,$hash)
    {
        if(!$this->connect()) return false;
        $ftpfilepath = FTP_BASEDIR.$hash;
        return ftp_put($this->connection, $ftpfilepath, $source, FTP_BINARY);
    }

    function deleteFile($hash)
    {
        if(!$this->connect()) return false;
        $ftpfilepath = FTP_BASEDIR.$hash;
        return (ftp_delete($this->connection,$ftpfilepath)?true:false);
    }
}