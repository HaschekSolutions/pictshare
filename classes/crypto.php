<?php 

class Crypto
{
    function encrypt($inputfile,$outputfile)
    {
        if(!file_exists($inputfile)) return;
        $data = base64_encode(file_get_contents($inputfile));
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = sodium_crypto_secretbox($data, $nonce, ENCRYPTION_KEY);
        $encoded = base64_encode($nonce . $ciphertext);
        file_put_contents($outputfile,$encoded);
    }

    function decrypt($inputfile,$outputfile)
    {
        if(!file_exists($inputfile)) return;
        $decoded = base64_decode(file_get_contents($inputfile));
        $nonce = mb_substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
        $ciphertext = mb_substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');
        $plaintext = sodium_crypto_secretbox_open($ciphertext, $nonce, ENCRYPTION_KEY);
        file_put_contents($outputfile,base64_decode($plaintext));
    }
}