<?php 

class Encryption{

    /**
     * $key must have been generated at some point with: random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES)
     */
    function encryptText($text,$key)
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = sodium_crypto_secretbox($text, $nonce, $key);
        $encoded = base64_encode($nonce . $ciphertext);
        return $encoded;
    }

    /**
     * $key must have been generated at some point with: random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES)
     */
    function decryptText($encoded,$key)
    {
        $decoded = base64_decode($encoded);
        $nonce = mb_substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');


        $ciphertext = mb_substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');
        $plaintext = sodium_crypto_secretbox_open($ciphertext, $nonce, $key);
        return $plaintext;
    }


    /**
     * $key must have been generated at some point with: sodium_crypto_secretstream_xchacha20poly1305_keygen();
     */
    function encryptFile($infile,$enc_outfile,$key)
    {
        $chunk_size = 4096;

        $fd_in = fopen($infile, 'rb');
        $fd_out = fopen($enc_outfile, 'wb');

        list($stream, $header) = sodium_crypto_secretstream_xchacha20poly1305_init_push($key);

        fwrite($fd_out, $header);

        $tag = SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_MESSAGE;
        do {
            $chunk = fread($fd_in, $chunk_size);
            if (feof($fd_in)) {
                $tag = SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL;
            }
            $encrypted_chunk = sodium_crypto_secretstream_xchacha20poly1305_push($stream, $chunk, '', $tag);
            fwrite($fd_out, $encrypted_chunk);
        } while ($tag !== SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL);

        fclose($fd_out);
        fclose($fd_in);
    }

    /**
     * $key must have been generated at some point with: sodium_crypto_secretstream_xchacha20poly1305_keygen();
     */
    function decryptFile($enc_infile,$outfile,$key)
    {
        $fd_in = fopen($enc_infile, 'rb');
        $fd_out = fopen($outfile, 'wb');
        $chunk_size = 4096;

        $header = fread($fd_in, SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES);

        $stream = sodium_crypto_secretstream_xchacha20poly1305_init_pull($header, $key);
        do {
            $chunk = fread($fd_in, $chunk_size + SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_ABYTES);
            list($decrypted_chunk, $tag) = sodium_crypto_secretstream_xchacha20poly1305_pull($stream, $chunk);
            fwrite($fd_out, $decrypted_chunk);
        } while (!feof($fd_in) && $tag !== SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL);
        $ok = feof($fd_in);

        fclose($fd_out);
        fclose($fd_in);

        if (!$ok) {
            die('Invalid/corrupted input');
        }
    }
}