<?php

namespace App\Support;

/**
 * Class Translator
 * @package App\Support
 */
class Translator
{
    /**
     * @param int    $index
     * @param string $params
     *
     * @return mixed
     */
    public static function translate($index, $params = "")
    {
        $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
        //$lang = 'en';
        switch ($lang) {
            case "de":
                $words[0]  = 'Maximale Dateigröße';
                $words[1]  = 'Es können auch mehrere Bilder auf einmal ausgewählt werden!';
                $words[2]  = 'einfach, gratis, genial';
                $words[3]  = 'Foto hinaufladen';
                $words[4]  = 'Bild';
                $words[5]  = 'Die Datei ' . $params[0] . ' kann nicht hinaufgeladen werden, da der Dateityp "' .
                             $params[1] . '" nicht unterstützt wird.';
                $words[6]  = 'Fehler beim Upload von ' . $params;
                $words[7]  = 'Bild "' . $params . '"" wurde erfolgreich hochgeladen';
                $words[8]  = 'Skaliert auf';
                $words[9]  = 'Kleinansicht';
                $words[10] = 'für Verlinkungen und Miniaturvorschau in Foren';
                $words[11] = 'Allgemeiner Fehler';
                $words[12] = 'Fehler 404 - nicht gefunden';
                $words[13] = 'Fehler 403 - nicht erlaubt';
                $words[14] = 'Kein refferer';
                $words[15] = 'Verlinkte Seiten';
                $words[16] = 'Hinweis: Zugriffe über pictshare.net werden nicht gerechnet';
                $words[17] = 'Dieses Bild wurde ' . $params[0] . ' mal von ' . $params[1] .
                             ' verschiedenen IPs gesehen und hat ' . $params[2] . ' Traffic verursacht';
                $words[18] = 'Dieses Bild wurde von folgenden Ländern aufgerufen: ';
                $words[19] = $params[0] . ' Aufrufe aus ' . $params[1];
                $words[20] = 'Upload-Code';
                $words[21] = 'Falscher Upload Code eingegeben. Upload abgebrochen';

                break;

            default:
                $words[0]  = 'Max filesize';
                $words[1]  = 'You can select multiple pictures at once!';
                $words[2]  = 'easy, free, engenious';
                $words[3]  = 'Upload';
                $words[4]  = 'Picture';
                $words[5]  = 'The file ' . $params[0] . ' can\'t be uploaded since the filetype "' . $params[1] .
                             '" is not supported.';
                $words[6]  = 'Error uploading ' . $params;
                $words[7]  = 'Picture "' . $params . '"" was uploaded successfully';
                $words[8]  = 'Scaled to';
                $words[9]  = 'Thumbnail';
                $words[10] = 'for pasting in Forums, etc..';
                $words[11] = 'Unspecified error';
                $words[12] = 'Error 404 - not found';
                $words[13] = 'Error 403 - not allowed';
                $words[14] = 'No referrer';
                $words[15] = 'Linked sites';
                $words[16] = 'Note: Views from pictshare.net will not be counted';
                $words[17] = 'Was seen ' . $params[0] . ' times by ' . $params[1] . ' unique IPs and produced ' .
                             $params[2] . ' traffic';
                $words[18] = 'This picture was seen from the following countries: ';
                $words[19] = $params[0] . ' views from ' . $params[1];
                $words[20] = 'Upload code';
                $words[21] = 'Invalid upload code provided';
        }

        return $words[$index];
    }
}
