<?php 

class UrlController implements ContentController
{
    //returns all extensions registered by this type of content
    public function getRegisteredExtensions(){return array('url');}
    public function handleHash($hash,$url){}
    public function handleUpload($tmpfile,$hash=false){}
}