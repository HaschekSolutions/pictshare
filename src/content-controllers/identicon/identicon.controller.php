<?php 

use Bitverse\Identicon\Identicon;
use Bitverse\Identicon\Color\Color;
use Bitverse\Identicon\Generator\RingsGenerator;
use Bitverse\Identicon\Preprocessor\MD5Preprocessor;

class IdenticonController implements ContentController
{
    public const ctype = 'dynamic';

    //returns all extensions registered by this type of content
    public function getRegisteredExtensions(){return array('identicon');}

    public function handleHash($hash,$url)
    {
        unset($url[array_search('identicon',$url)]);
        $url = array_values($url);
        

        $generator = new RingsGenerator();
        $generator->setBackgroundColor(Color::parseHex('#EEEEEE'));

        $identicon = new Identicon(new MD5Preprocessor(), $generator);

        $icon = $identicon->getIcon($url[0]);

        header('Content-type: image/svg+xml');
        echo $icon;
    }

    public function handleUpload($tmpfile,$hash=false)
    {
        return array('status'=>'err','hash'=>$hash,'reason'=>'Cannot upload to Identicons');
    }


}