<?php 
/**
 * StorageController interface
 * 
 * Must be implemented by all storage systems
 */

interface StorageController
{
    /**
     * Checks if this storage system is enabled. 
     * For example check if all depenencies are met
     * or config vars are set
     * 
     * @return bool 
     */
    function isEnabled();
    
    /**
     * Is fired whenever a hash is not found locally
     * Use this to look in your storage system for the file
     * 
     * @param string $hash is  the hash of the file requested
     * 
     * @return bool 
     */
    function hashExists($hash);


    /**
     * Returns an array of all items in this storage controller
     * 
     * @return array
     */
    function getItems();

    /**
     * If a file does exist in this storage system, then this method should
     * get the file and put it in the default data directory
     * 
     * The file should be placed in /data/$hash/$hash where the first $hash is obviously
     * a folder that you might have to create first before putting the file in
     * 
     * @param string $hash is the hash of the file that should be pulled from this storage system
     * @param string $location is the location where the downloaded file should be placed
     * 
     * @return bool true if successful
     */
    function pullFile($hash,$location);

    /**
     * Whenever a new file is uploaded this method will be called
     * You should then upload it or do whatever your storage system is meant to do with new files
     * 
     * @param string $hash is the hash of the new file. The file path of this file is always getDataDir().DS.$hash.DS.$hash
     * 
     * @return bool true if successful
     */
    function pushFile($source,$hash);

    /**
     * If deletion of a file is requested, this method is called
     * 
     * @param string $hash is the hash of the file. Delete this hash from your storage system
     * 
     * @return bool true if successful
     */
    function deleteFile($hash);
}