<?php 

class UrlController implements ContentController
{
    public const ctype = 'static';
    
    //returns all extensions registered by this type of content
    public function getRegisteredExtensions(){return array('url');}
    public function handleHash($hash,$url,$path=false){}
    public function handleUpload($tmpfile,$hash=false,$passthrough=false){}
}