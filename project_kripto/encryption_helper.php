<?php
class EncryptionHelper
{
    private static $desKey = "kuncides"; // DES key (harus 8 karakter)
    private static $caesarShift = 3; // Shift untuk Caesar Cipher

    // Inisialisasi enkripsi (Validasi kunci DES)
    public static function initialize()
    {
        if (strlen(self::$desKey) !== 8) {
            die("Kunci DES harus 8 karakter.");
        }
    }

    // Fungsi enkripsi DES
    private static function encryptDES($message)
    {
        $cipher = "DES-ECB";
        $encrypted = openssl_encrypt($message, $cipher, self::$desKey, OPENSSL_RAW_DATA);
        return base64_encode($encrypted); // Encode hasil DES ke Base64
    }

    // Fungsi Caesar Cipher untuk mengenkripsi teks
    private static function encryptCaesarCipher($message)
    {
        $encryptedMessage = '';
        $shift = self::$caesarShift % 26; // Shift alfabet
        foreach (str_split($message) as $char) {
            if (ctype_alpha($char)) {
                $offset = ctype_upper($char) ? ord('A') : ord('a');
                $encryptedMessage .= chr((ord($char) - $offset + $shift) % 26 + $offset);
            } else {
                $encryptedMessage .= $char; // Karakter non-alfabet tetap
            }
        }
        return $encryptedMessage;
    }

    // Kombinasi enkripsi DES + Caesar Cipher
    public static function doubleEncrypt($message)
    {
        $encryptedDES = self::encryptDES($message); // Hasil DES dienkripsi ke Base64
        return self::encryptCaesarCipher($encryptedDES); // Lalu diproses Caesar Cipher
    }

    // Fungsi dekripsi Caesar Cipher
    private static function decryptCaesarCipher($message)
    {
        $decryptedMessage = '';
        $shift = self::$caesarShift % 26;
        foreach (str_split($message) as $char) {
            if (ctype_alpha($char)) {
                $offset = ctype_upper($char) ? ord('A') : ord('a');
                $decryptedMessage .= chr((ord($char) - $offset - $shift + 26) % 26 + $offset);
            } else {
                $decryptedMessage .= $char;
            }
        }
        return $decryptedMessage;
    }

    // Fungsi dekripsi kombinasi DES + Caesar Cipher
    public static function decryptCombined($encryptedMessage)
    {
        $decryptedCaesar = self::decryptCaesarCipher($encryptedMessage); // Kembalikan Caesar
        $decryptedDES = base64_decode($decryptedCaesar); // Decode dari Base64
        return openssl_decrypt($decryptedDES, "DES-ECB", self::$desKey, OPENSSL_RAW_DATA);
    }
}
