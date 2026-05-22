<?php 

class MarkdownController implements ContentController
{
    public const ctype = 'static';

    public $mimes = [
        'text/markdown',
        'text/x-markdown',
    ];
    
    //returns all extensions registered by this type of content
    public function getRegisteredExtensions(){return array('md','markdown');}

    public function handleHash($hash,$url,$path=false)
    {
        $path = getDataDir().DS.$hash.DS.$hash;

        if(in_array('raw',$url))
        {
            header('Content-Type: text/plain; charset=utf-8');
            echo file_get_contents($path);
        }
        else if(in_array('download',$url))
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
        }
        else {
            $fileSize = filesize($path);
            $content = file_get_contents($path);
            
            $parsedown = new Parsedown();
            $htmlContent = $parsedown->text($content);

            return renderTemplate('markdown.html.php', [
                'hash' => $hash, 
                'content' => $htmlContent, 
                'filesize' => $fileSize,
                'slogan' => (defined('TITLE')?TITLE:'PictShare Markdown Viewer')
            ]);
        }
    }

    public function handleUpload($tmpfile,$hash=false,$passthrough=false)
    {
        if($hash===false)
        {
            $hash = getNewHash('md',6);
        }
        else
        {
            if(!endswith($hash,'.md'))
                $hash.='.md';
            if(isExistingHash($hash))
                return array('status'=>'err','hash'=>$hash,'reason'=>'Custom hash already exists');
        }

        if($passthrough===false)
            storeFile($tmpfile,$hash,true);
        
        return array('status'=>'ok','hash'=>$hash,'url'=>getURL().$hash);
    }
}