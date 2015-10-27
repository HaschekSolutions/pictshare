<?php

class HTML {

    private $js = array();

    function shortenUrls($data) {
        $data = preg_replace_callback('@(https?://([-\w\.]+)+(:\d+)?(/([\w/_\.]*(\?\S+)?)?)?)@', array(get_class($this), '_fetchTinyUrl'), $data);
        return $data;
    }

    private function _fetchTinyUrl($url) {
        $ch = curl_init();
        $timeout = 5;
        curl_setopt($ch, CURLOPT_URL, 'http://tinyurl.com/api-create.php?url=' . $url[0]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $data = curl_exec($ch);
        curl_close($ch);
        return '<a href="' . $data . '" target = "_blank" >' . $data . '</a>';
    }
    
    function code($data,$class='markdown')
    {
        return '<pre><code class="'.$class.'">'.$data.'</code></pre>';
    }
    
    function form($data,$submitvalue="Speichern",$action="",$submitname="submit")
    {
        return '<form enctype="multipart/form-data" method="POST" onsubmit="doCheck();" action="'.$action.'">'.$data.'<br/><input type="submit" name="'.$submitname.'" value="'.$submitvalue.'" /> </form>';
    }
    
    function number($z,$nachkommastellen=0)
    {
        return number_format($z, $nachkommastellen, ',', '.');
    }
    
    function span($data,$id="",$class="",$zusatz="")
    {
        return '<span id="'.$id.'" class="'.$class.'" '.$zusatz.'>'.$data.'</span>';
    }
    
    function textarea($name,$data='',$cols=50,$rows=10,$forcewysiwyg=false)
    {
        $cs = new CubeshopModel;
        if($_SESSION['user'] && ($cs->hasUserItem('bbcode') || $forcewysiwyg))
            $textarea = $this->getWYSIWYGEditor($name,$data);
        else $textarea = '<textarea name="'.$name.'" cols="'.$cols.'" rows="'.$rows.'">'.$data.'</textarea>';
        
        return $textarea;
    }
    
    function center($data)
    {
        return '<center>'.$data.'</center>';
    }
    
    function displayError($e)
    {
        $text = addslashes($this->error($e));
        return '<script>$(document).ready(function(){error("'.$text.'");});</script>';
    }
    
    function displaySuccess($e)
    {
        $text = addslashes($this->success($e));
        return '<script>$(document).ready(function(){error("'.$text.'");});</script>';
    }
    
    function clear()
    {
        return '<div class="clear"></div>';
    }
    
    function menu($arr, $id = "", $class = "")
    {
        aasort($arr, 'priority');
        $o = '<ul id="' . $id . '" class="' . $class . '">';
        foreach ($arr as $key => $val)
        {
            if ($val['active'])
                $c = 'active';
            else
                $c = '';

            $o.= '<li class="menu_item" page="'.strtolower($key).'" id="page_' . strtolower($key) . '"><a href="' . DS . strtolower($key) . '" class="' . $c . ' '.$val['class'].'">' . $val['text'] . '</a></li>';
        }
        $o.= '</ul>';
        return $o;
    }
    
    function strong($text,$id = "", $class = "")
    {
        return '<strong class="'.$class.'" id="'.$id.'">'.$text.'</strong>';
    }
    
    function dfn($text,$desc,$id = "", $class = "")
    {
        return '<dfn class="'.$class.'" id="'.$id.'" title="'.$desc.'">'.$text.'</dfn>';
    }
    
    function tip($text,$id = "", $class = "")
    {
        return '<span class="tip '.$class.'" id="'.$id.'">'.$text.'</span>';
    }

    function submenu($arr, $id = "", $class = "") {
        if (!is_array($arr))
            return false;
        $o = '<ul id="' . $id . '" class="' . $class . '">';
        foreach ($arr as $key => $val) {
            if ($val['active'])
                $c = 'active';
            else
                $c = '';
            $o.= '<li id="sub_' . strtolower($val['action']) . '"><a href="' . DS . $val['base'] . DS . strtolower($val['action']) . '" class="' . $c . '">' . $val['text'] . '</a></li>';
        }
        $o.= '</ul>';
        return $o;
    }
    
    /**
     * $timestamp = zeitpunt des ablaufens in unix timestamp
     */
    function countdown($timestamp,$prestring="",$id=0,$allownegative=false)
    {
        $a = new Algorithms();
        if(!$id) $id = $a->getRandomHash(8);
        $seconds = $timestamp-time(); 
        //return '<span id="'.$id.'"><script>countdown("#'.$id.'",'.$timestamp.',"'.$prestring.'",'.(time()*1000).',0);</script></span>';
        return '<span id="'.$id.'"><script>countdown("#'.$id.'","","'.$prestring.'",'.($seconds*1000).',"'.$allownegative.'");</script></span>';        
    }

    function sanitize($data)
    {
        return mysql_real_escape_string($data);
    }
    
    function specialchars($text,$utf8=0)
    {
        return htmlspecialchars($text);
    }
    
    /*
     * @param string $name
     * @param string $value
     * @param string $type
     * @param string $id
     * @param string $class
     * @param int $size
     */
    function input($name, $value = '', $type = 'text', $id = '', $class = '', $size = '20',$onClick='',$extra='')
    {
        return '<input type="' . $type . '" onClick="'.$onClick.'" value="' . $value . '" class="' . $class . '" id="' . $id . '" name="' . $name . '" size="' . $size . '" '.$extra.' />';
    }
    
    function button($name,$value,$onclick="return true;",$id='',$class="button")
    {
        return '<input type="button" name="'.$name.'" value="'.$value.'" id="'.$id.'" class="'.$class.'" onClick="'.$onclick.'"/>';
    }
    
    function buttonGoTo($value,$link,$id='',$class='')
    {
        return '<a href="'.$link.'" id="'.$id.'" class="button '.$class.'">'.$value.'</a>';
    }
    
    /*
     * @param array $data the multidimensional array
     * @param bool $header should the first line be a <th> element instead of <td>?
     * @param string $width
     * @param string $id the ID of the table
     * @param string $class the class of the table
     * @param string $tdclass the class of every td element
     */
    function table($data, $header = 1, $width = '100%', $id = '', $class = '', $tdclass = 'text_top', $evenwidth=1,$trclass='')
    {
        if (!is_array($data))
            return false;
        $t = '<table width="' . $width . '" id="' . $id . '" class="' . $class . '">';
        foreach ($data as $key => $val)
        {
            if ($key == 0 && $header)
                $td = 'th'; else
                $td = 'td class="' . $tdclass . '"';
            $t.='<tr class="'.$trclass.'">';
            if($evenwidth) $w = floor(100/count($val)).'%';
            foreach ($val as $j => $tdata)
            {
                if($evenwidth) $w = floor(100/count($val)).'%';
                else $w = 'auto';
                if(is_array($tdata))
                {
                    $text = $tdata['text'];
                    if($tdata['header']) $td = 'th';
                    if($tdata['width']) $w=$tdata['width'];
                    if($tdata['class']) $tdclass .= ' '.$tdata['class'];
                    if($tdata['id']) $tid = $tdata['id'];
                    if($tdata['colspan']) $colspan = 'colspan="'.$tdata['colspan'].'"';
                    $tdata = $text;
                }
                $t.='<' . $td . ' '.$colspan.' width="'.$w.'" class="'.$tclass.'" id="'.$tid.'">' . $tdata . '</' . $td . '>';
                $class='';
                $tid='';
                $colspan='';
            }
            $t.='</tr>';
        }
        $t.='</table>';

        return $t;
    }

    /*
     * @param $err is the error code equivalent of /config/errors.php
     * if $err is not numeric or not found in errors.php, its printed as text
     * @param $class is the html tag class
     */

    function error($err, $class = 'error',$backbutton=false)
    {
        global $error;
        if (is_numeric($err) && $error[$err])
            $err = $error[$err];
        if($backbutton)
            $bb = '<br/><a href="#" onClick="history.back();return false;">Zurück..</a>';
        return '<span class="' . $class . '">' . $err . '</span>'.$bb;
    }
    
    function arrayToString($arr)
    {
        if(!is_array($arr)) return false;
        foreach($arr as $a)
        {
            $o.=$a.';';
        }
        $o = substr($o,0,-1);
        
        return $o;
    }
    
    function success($msg, $class = 'success')
    {
        return '<span class="' . $class . '">' . $msg . '</span>';
    }

    function goToLocation($location = '/', $force = true) {
        $script = '<script>window.location.href="' . $location . '"</script>';
        if ($force)
            exit($script);
        else
            return $script;
    }

    function link($text, $path, $prompt = null, $confirmMessage = "Bist du sicher?",$class="")
    {
        $path = str_replace(' ', '-', $path);
        if ($prompt) {
            $data = '<a class="'.$class.'" href="' . BASE_PATH . '/' . $path . '" onclick="return confirm(\'' . $confirmMessage . '\')">' . $text . '</a>';
        } else {
            $data = '<a class="'.$class.'" href="' . BASE_PATH . '/' . $path . '">' . $text . '</a>';
        }
        return $data;
    }
    
    function liste($lines,$ulid='')
    {
        if(!is_array($lines)) return false;
        $o = '<ul id="'.$ulid.'">';
        foreach($lines as $line)
            $o.='<li>'.$line.'</li>';
        $o.= '</ul>';
        
        return $o;
    }
    
    function getArrowRight()
    {
        return '<img src="/css/imgs/arrow_right.png" height="20px" /> ';
    }

    function includeJs($fileName) {
        $data = '<script src="' . BASE_PATH . '/js/' . $fileName . '.js"></script>';
        return $data;
    }

    function includeCss($fileName) {
        $data = '<style href="' . BASE_PATH . '/css/' . $fileName . '.css"></script>';
        return $data;
    }
    
    function getInfoMessage($message,$text=false)
    {
        $message = str_replace('"', "'", $message);
        $message = str_replace("'", "\'", $message);
        return '<img class="tooltip" title="'.(($message)).'" src="/css/imgs/info.png" />';
        //return '<span onmouseover="Tip(\''.$message.'\')" onmouseout="UnTip()">'.($text?$text:'<img src="/css/imgs/info.png" />').'</span>';
    }
    
    function BBCode($Text) 
    { 
        //$Text = utf8_encode($Text);
         // Replace any html brackets with HTML Entities to prevent executing HTML or script
         // Don't use strip_tags here because it breaks [url] search by replacing & with amp
         $Text = str_replace("<", "&lt;", $Text); 
         $Text = str_replace(">", "&gt;", $Text); 

         // Convert new line chars to html <br /> tags 
         $Text = nl2br($Text); 

         // Set up the parameters for a URL search string 
         $URLSearchString = " a-zA-Z0-9\:\/\-\?\&\.\=\_\~\#\'"; 
         // Set up the parameters for a MAIL search string 
         $MAILSearchString = $URLSearchString . " a-zA-Z0-9\.@";

         // Perform URL Search 
         $Text = preg_replace("/\[url\]([$URLSearchString]*)\[\/url\]/", '<a href=\'$1\' target=\'_blank\'>$1</a>', $Text); 
         $Text = preg_replace("(\[url\=([$URLSearchString]*)\](.+?)\[/url\])", '<a href=\'$1\' target=\'_blank\'>$2</a>', $Text); 
      //$Text = preg_replace("(\[url\=([$URLSearchString]*)\]([$URLSearchString]*)\[/url\])", '<a href="$1" target="_blank">$2</a>', $Text);

         // Perform MAIL Search 
         $Text = preg_replace("(\[mail\]([$MAILSearchString]*)\[/mail\])", '<a href=\'mailto:$1\'>$1</a>', $Text); 
         $Text = preg_replace("/\[mail\=([$MAILSearchString]*)\](.+?)\[\/mail\]/", '<a href=\'mailto:$1\'>$2</a>', $Text); 
       
         // Check for bold text 
         $Text = preg_replace("(\[b\](.+?)\[\/b])is",'<strong>$1</strong>',$Text); 
         
         // Check for H1-H3
         $Text = preg_replace("(\[h1\](.+?)\[\/h1])is",'<h1>$1</h1>',$Text);
         $Text = preg_replace("(\[h2\](.+?)\[\/h2])is",'<h2>$1</h2>',$Text);
         $Text = preg_replace("(\[h3\](.+?)\[\/h3])is",'<h3>$1</h3>',$Text);

         // Check for Italics text 
         $Text = preg_replace("(\[i\](.+?)\[\/i\])is",'<em>$1</em>',$Text); 

         // Check for Underline text 
         $Text = preg_replace("(\[u\](.+?)\[\/u\])is",'<span style=\'text-decoration: underline;\'>$1</span>',$Text);

         // Check for strike-through text 
         $Text = preg_replace("(\[s\](.+?)\[\/s\])is",'<span style=\'text-decoration: line-through;\'>$1</span>',$Text); 

         // Check for over-line text 
         $Text = preg_replace("(\[o\](.+?)\[\/o\])is",'<span style=\'text-decoration: overline;\'>$1</span>',$Text); 

         // Check for colored text 
         $Text = preg_replace("(\[color=(.+?)\](.+?)\[\/color\])is","<span style='color: $1'>$2</span>",$Text); 

         // Check for sized text 
         $Text = preg_replace("(\[size=(.+?)\](.+?)\[\/size\])is","<span style='font-size: $1px'>$2</span>",$Text);

         // Check for list text 
         $Text = preg_replace("/\[ul\](.+?)\[\/ul\]/is", '<ul class=\'listbullet\'>$1</ul>' ,$Text);
         $Text = preg_replace("/\[list\](.+?)\[\/list\]/is", '<ul class=\'listbullet\'>$1</ul>' ,$Text);
         $Text = preg_replace("/\[list=1\](.+?)\[\/list\]/is", '<ul class=\'listdecimal\'>$1</ul>' ,$Text); 
         $Text = preg_replace("/\[list=i\](.+?)\[\/list\]/s", '<ul class=\'listlowerroman\'>$1</ul>' ,$Text); 
         $Text = preg_replace("/\[list=I\](.+?)\[\/list\]/s", '<ul class=\'listupperroman\'>$1</ul>' ,$Text); 
         $Text = preg_replace("/\[list=a\](.+?)\[\/list\]/s", '<ul class=\'listloweralpha\'>$1</ul>' ,$Text); 
         $Text = preg_replace("/\[list=A\](.+?)\[\/list\]/s", '<ul class=\'listupperalpha\'>$1</ul>' ,$Text); 
         $Text = str_replace("[*]", "<li>", $Text); 
         $Text = preg_replace("/\[li\](.+?)\[\/li\]/s", '<li>$1</li>' ,$Text); 

         // Check for font change text 
         $Text = preg_replace("(\[font=(.+?)\](.+?)\[\/font\])","<span style='font-family: $1;'>$2</span>",$Text); 
         
         $Text = preg_replace("(\[code=(.+?)\](.+?)\[\/code])is","<pre>$1 code:<code class=\"$1\">$2</code></pre>",$Text);
         $Text = preg_replace("(\[code\](.+?)\[\/code])is","<pre>Code:<code class=\"markdown\">$1</code></pre>",$Text); 
         
         $Text = preg_replace("(\[spoiler](.+?)\[\/spoiler])is","<div class='spoiler'><input type='button' value='Spoiler anzeigen' onClick=\"$(this).parent().children('.spoiltext').fadeIn();\"/><div class='spoiltext markdown invisible'>$1</div></div>",$Text); 
         //$Text = preg_replace("(\[b\](.+?)\[\/b])is",'<strong>$1</strong>',$Text); 

//         // Declare the format for [code] layout 
//         $CodeLayout = '<table width="90%" border="0" align="center" cellpadding="0" cellspacing="0">
//                             <tr> 
//                                 <td class="quotecodeheader"> Code:</td>
//                             </tr> 
//                             <tr> 
//                                 <td class="codebody">$1</td> 
//                             </tr> 
//                        </table>'; 
//         // Check for [code] text 
//         $Text = preg_replace("/\[code\](.+?)\[\/code\]/is","$CodeLayout", $Text); 
//         // Declare the format for [php] layout 
//         $phpLayout = '<table width="90%" border="0" align="center" cellpadding="0" cellspacing="0">
//                             <tr> 
//                                 <td class="quotecodeheader"> Code:</td>
//                             </tr> 
//                             <tr> 
//                                 <td class="codebody">$1</td> 
//                             </tr> 
//                        </table>'; 
//         // Check for [php] text 
//         $Text = preg_replace("/\[php\](.+?)\[\/php\]/is",$phpLayout, $Text); 

         // Declare the format for [quote] layout 
         $QuoteLayout = '<table width="90%" border="0" align="center" cellpadding="0" cellspacing="0">
                             <tr> 
                                 <td class="quotecodeheader"> Quote:</td>
                             </tr> 
                             <tr> 
                                 <td class="quotebody">$1</td> 
                             </tr> 
                        </table>'; 
                   
         // Check for [quote] text 
         $Text = preg_replace("/\[quote\](.+?)\[\/quote\]/is", $this->tip('Zitat:')."<code>$1</code>", $Text);
         $Text = preg_replace("(\[quote\=([$URLSearchString]*)\](.+?)\[/quote\])", $this->tip('Zitat von $1:')."<code>$2</code>", $Text); 
       
         // Images 
         // [img]pathtoimage[/img] 
         $Text = preg_replace("/\[img\](.+?)\[\/img\]/", '<img src=\'$1\'>', $Text); 
       
         // [img=widthxheight]image source[/img] 
         $Text = preg_replace("/\[img\=([0-9]*)x([0-9]*)\](.+?)\[\/img\]/", '<img src=\'$3\' height=\'$2\' width=\'$1\'>', $Text); 
       
        return $Text; 
    }
    
    function getProgressBar($percent,$alternatetext="",$width='100%',$id="")
    {
        if($percent<0)$percent = 0;
        if($percent>100)$percent = 100;
        if(!$alternatetext) $alternatetext = $percent.'%';
        return '<div width="'.$width.'" class="progressbar_wrapper"><strong style="color:black;float:left;margin-left:50%;margin-top:4px;">'.$alternatetext.'</strong>
                    <div style="width:'.$percent.'%" class="progressbar_progress"></div>
               </div>';
    }
    
    function getContentBox($content,$title='',$id='',$class='',$bid='',$collapse=true,$start=0)
    {
        $flip = $start?'flip':'';
        if($collapse)
            $control = '<a class="contentbox_toggle right '.$flip.'" href="#" onClick="return false;"><img title="Aus/Einfahren" src="/css/imgs/contentBoxenArrow.png" class="flip" /></a>';
        if($start)
            $invis = 'invisible';
        $o = '<div id="'.$bid.'" class="content_box '.$class.'">
                    <div class="content_box_header">'.$control.$title.'</div>
                    <div id="'.$id.'" class="'.$invis.' content_box_content">'.$content.'</div>
              </div>';
        
        return $o;
    }
    
    function div($data,$id='',$class='')
    {
        return '<div id="'.$id.'" class="'.$class.'">'.$data.'</div>';
    }
    
    function select($name,$data=null,$selected=null,$id='')
    {
        $o = '<select id="'.$id.'" name="'.$name.'">';
        if(is_array($data))
            foreach($data as $key=>$val)
            {
                if($selected==$key) $sel = ' selected';
                else $sel = '';
                $o.='<option value="'.$key.'" '.$sel.'>'.$val.'</option>';
            }
        $o.= '</select>';
        
        return $o;
    }
    
    /**
     * <form onsubmit="doCheck();">
     * @param type $name
     * @param type $value
     * @param type $height
     * @return string 
     */
    function getWYSIWYGEditor($name,$value='',$height='150px')
    {
        $o = '<div class="richeditor">
                    <div class="editbar">
                            <button title="bold" onclick="doClick(\'bold\');" type="button"><b>B</b></button>
                            <button title="italic" onclick="doClick(\'italic\');" type="button"><i>I</i></button>
                            <button title="underline" onclick="doClick(\'underline\');" type="button"><u>U</u></button>
                            <button title="hyperlink" onclick="doLink();" type="button" style="background-image:url(\'/css/imgs/url.gif\');"></button>
                            <button title="image" onclick="doImage();" type="button" style="background-image:url(\'/css/imgs/img.gif\');"></button>
                            <button title="list" onclick="doClick(\'InsertUnorderedList\');" type="button" style="background-image:url(\'/css/imgs/icon_list.gif\');"></button>
                            <button title="color" onclick="showColorGrid2(\'none\')" type="button" style="background-image:url(\'/css/imgs/colors.gif\');"></button><span id="colorpicker201" class="colorpicker201"></span>
                            <button title="quote" onclick="doQuote();" type="button" style="background-image:url(\'/css/imgs/icon_quote.png\');"></button>
                            <button title="switch to source" type="button" onclick="javascript:SwitchEditor()" style="background-image:url(\'/css/imgs/icon_html.gif\');"></button>
                    </div>
                    <div class="container">
                    <textarea id="'.$name.'" name="'.$name.'" style="height:'.$height.';width:100%;">'.$value.'</textarea>
                    </div>
            </div>
            <script type="text/javascript">
                    initEditor("'.$name.'", true);
            </script>';

        return $o;
    }

}