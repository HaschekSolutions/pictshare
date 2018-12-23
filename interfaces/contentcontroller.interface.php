<?php

/**
 * Content controller interface for new content types
 */

interface ContentController
{
    /** This method will return all file extensions that will be associated with this content type
     *  for example 'pdf' but it could be anything really. You just need a way later to confirm that a type is what it says it is
     * 
     * 
     * @return array the extensions of files associated with this controller. eg. return array('pdf');
    */
    public function getRegisteredExtensions();

    /** This method will be called whenever the system has to find out if a user requested (existing) hash
     * belongs to this controller.
     * In here the content should be rendered or processed like resized or something.
     * You can decide what it does by working with the $url array which gives you every element in the URL
     * Does not need to return anything for example you can just set the header and print your data right away
     * 
     * @param string $hash the hash (with extension eg '5saB2.pdf') of the file this controller will work with
     * @param array $url contains all URL elements exploded with '/' so you can do your magic. 
    */
    public function handleHash($hash,$url);

    /** This method will be called if the upload script detects the content of a newly uploaded file as one of the
     *  extensions registered at "getRegisteredExtensions".
     *  For Example if someone uploads a PDF and getRegisteredExtensions has registered "pdf", then this method of this
     *  controller will be called
     * 
     * @param string $tmpfile is the location on disk of the temp file that was uploaded. It is your job to put it somewhere, your handleHash method will find it again
     * @param array $hash (optional) if you want your upload to have a certain hash then add it here. This allows for user chosen hashes
    */
    public function handleUpload($tmpfile,$hash=false);
}