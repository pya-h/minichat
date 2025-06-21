<?php
// generates 2048-bit RSA keypair and returns base64 PEM strings for public and private keys
function generate_rsa_keypair()
{
    $config = [
        "private_key_type" => OPENSSL_KEYTYPE_RSA,
        "private_key_bits" => 2048,
    ];
    $res = openssl_pkey_new($config);
    openssl_pkey_export($res, $privateKeyPem);
    $pubKeyDetails = openssl_pkey_get_details($res);
    $publicKeyPem = $pubKeyDetails["key"];

    $publicKeyBase64 = base64_encode($publicKeyPem);
    $privateKeyBase64 = base64_encode($privateKeyPem);

    return [$publicKeyBase64, $privateKeyBase64];
}

// decrypts base64 ciphertext with base64 private key PEM
function decrypt_message($ciphertextBase64, $privateKeyBase64)
{
    $privateKeyPem = base64_decode($privateKeyBase64);
    $privateKey = openssl_pkey_get_private($privateKeyPem);
    if (!$privateKey) return false;

    $ciphertext = base64_decode($ciphertextBase64);
    $plaintext = null;
    $success = openssl_private_decrypt($ciphertext, $plaintext, $privateKey, OPENSSL_PKCS1_OAEP_PADDING);
    if ($success) return $plaintext;
    return false;
}
