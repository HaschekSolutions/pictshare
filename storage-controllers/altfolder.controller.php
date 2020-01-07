<?php 

class AltfolderStorage implements StorageController
{
    function isEnabled()
    {
        return (defined('ALT_FOLDER') && ALT_FOLDER && is_dir(ALT_FOLDER));
    }
    
    function hashExists($hash)
    {
        $altname=ALT_FOLDER.DS.$hash;
		return file_exists($altname);
    }

    function pullFile($hash,$location)
    {
        $altname=ALT_FOLDER.DS.$hash;
		if(file_exists($altname))
		{
            copy($altname,$location);
		}
    }

    function pushFile($source,$hash)
    {
        $altname=ALT_FOLDER.DS.$hash;
        $orig = ROOT.DS.'data'.DS.$hash.DS.$hash;
		if(file_exists($orig) && !$this->hashExists($hash))
		{
            copy($source,$altname);
            return true;
        }
        
        return false;
    }

    function deleteFile($hash)
    {
        $altname=ALT_FOLDER.DS.$hash;
		if(file_exists($altname))
		{
			unlink($altname);
		}
    }
}