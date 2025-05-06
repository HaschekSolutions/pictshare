<?php 

class PlaceholderController implements ContentController
{
    public const ctype = 'dynamic';

    //returns all extensions registered by this type of content
    public function getRegisteredExtensions(){return array('placeholder');}

    public function handleHash($hash,$url)
    {
        $path = getDataDir().DS.$hash.DS.$hash;
        
        include_once(dirname(__FILE__).DS.'placeholdergenerator.php');
        $pg = new PlaceholderGenerator();

        foreach($url as $u)
        {
            if(isSize($u))
                $modifiers['size'] = $u;
            if(startsWith($u,'color-'))
            {
                $u = substr($u,6);
                $colors = explode('-',$u);
                foreach($colors as $c)
                    if(isColor($c))
                        $modifiers['colors'][] = (ctype_xdigit($c)?$c:color_name_to_hex($c));

                if(count($modifiers['colors'])>4)
                    $modifiers['colors'] = array_slice($modifiers['colors'],0,4);
            }
        }
        
        $img = $pg->generateImage($modifiers);

        $img = $pg->gradient($img, $modifiers['colors']);
        $img = $pg->addSizeText($img,$modifiers);

        header ("Content-type: image/jpeg");
        header ("ETag: $hash");
        header('Cache-control: public, max-age=31536000');
        
        imagejpeg($img,null,(defined('JPEG_COMPRESSION')?JPEG_COMPRESSION:90));
    }

    public function handleUpload($tmpfile,$hash=false)
    {
        return array('status'=>'err','hash'=>$hash,'reason'=>'Cannot upload to placeholder image');
    }


}