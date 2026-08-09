<?php

use enshrined\svgSanitize\Sanitizer;
use enshrined\svgSanitize\data\AllowedTags;
use enshrined\svgSanitize\data\AllowedAttributes;
use enshrined\svgSanitize\data\TagInterface;
use enshrined\svgSanitize\data\AttributeInterface;

/**
 * Adds a few purely-declarative SMIL elements/attributes that enshrined/svg-sanitize
 * omits from its defaults (attributecolor/animatemotion/animatetransform are allowed,
 * plain animate/set are not; from/to are not, only values/keyTimes/calcMode are).
 * None of these carry script or event-handler risk - same class as the tags/attrs
 * already whitelisted - so we extend rather than replace the defaults.
 */
class SvgAllowedTags implements TagInterface
{
    public static function getTags()
    {
        return array_merge(AllowedTags::getTags(), ['animate', 'set']);
    }
}

class SvgAllowedAttributes implements AttributeInterface
{
    public static function getAttributes()
    {
        return array_merge(AllowedAttributes::getAttributes(), ['from', 'to']);
    }
}

class SvgController implements ContentController
{
    public const ctype = 'static';

    public $mimes = [
        'image/svg+xml',
    ];

    //returns all extensions registered by this type of content
    public function getRegisteredExtensions(){return array('svg');}

    public function handleUpload($tmpfile,$hash=false,$passthrough=false)
    {
        $dirty = file_get_contents($tmpfile);

        $sanitizer = new Sanitizer();
        $sanitizer->setAllowedTags(new SvgAllowedTags());
        $sanitizer->setAllowedAttrs(new SvgAllowedAttributes());
        $sanitizer->removeRemoteReferences(true); //strips CSS url()-style remote refs (filter/mask/fill SSRF vector)

        $clean = $sanitizer->sanitize($dirty);

        if ($clean === false || stripos($clean, '<svg') === false)
            return array('status'=>'err','reason'=>'Invalid or unsafe SVG file');

        $clean = $this->stripAbsoluteHrefs($clean);

        file_put_contents($tmpfile, $clean);

        if($hash===false)
        {
            $hash = getNewHash('svg',6);
        }
        else
        {
            if(!endswith($hash,'.svg'))
                $hash.='.svg';
            if(isHashTaken($hash))
                return array('status'=>'err','hash'=>$hash,'reason'=>'Custom hash already exists');
        }

        if($passthrough===false)
            storeFile($tmpfile,$hash,true);

        return array('status'=>'ok','hash'=>$hash,'url'=>getURL().$hash);
    }

    public function handleHash($hash,$url,$path=false)
    {
        if($path===false)
            $path = getDataDir().DS.$hash.DS.$hash;

        //defense in depth: even a sanitized SVG is only as safe as the browser's
        //handling of it, so lock down execution/rendering context at the HTTP layer too
        header('X-Content-Type-Options: nosniff');
        header("Content-Security-Policy: sandbox; script-src 'none'");

        if(in_array('download',$url))
        {
            if (file_exists($path)) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="'.basename($path).'"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($path));
                serveFile($path);
                exit;
            }
            return;
        }

        header('Content-Type: image/svg+xml; charset=utf-8');
        header('Last-Modified: '.gmdate('D, d M Y H:i:s ', filemtime($path)).'GMT');
        header("ETag: $hash");
        header('Cache-control: public, max-age=31536000');
        serveFile($path);
    }

    /**
     * enshrined/svg-sanitize's removeRemoteReferences() only strips CSS url()-wrapped
     * remote refs, not plain xlink:href/href on <image>/<a>/<use>. Left alone, a hosted
     * "image" could act as a tracker/redirector beacon fetching third-party URLs whenever
     * someone views it - so strip absolute/protocol-relative hrefs ourselves, keeping
     * local #fragment and relative references intact.
     */
    private function stripAbsoluteHrefs(string $svg): string
    {
        $doc = new DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $loaded = $doc->loadXML($svg, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        if (!$loaded) return $svg;

        foreach ($doc->getElementsByTagName('*') as $el)
        {
            foreach (iterator_to_array($el->attributes ?? []) as $attr)
            {
                if (strtolower($attr->name) !== 'href') continue;
                $value = trim($attr->value);
                if ($value === '' || $value[0] === '#') continue;
                if (preg_match('~^([a-z][a-z0-9+.\-]*:)?//|^[a-z][a-z0-9+.\-]*:~i', $value))
                    $el->removeAttributeNode($attr);
            }
        }

        return $doc->saveXML();
    }
}
