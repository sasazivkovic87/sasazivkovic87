<?php

namespace App\Service;


class CryptService
{
    public function __construct(
        string $cipher = "aes-128-gcm",
        string $key = null,
        int $options = 0,
        string $tag = null
    )
    {
        $this->cipher = $cipher;
        $this->key = $key;
        $this->options = $options;
        $this->tag = $tag;
        $this->ivlen = openssl_cipher_iv_length($this->cipher);
    	$this->iv = openssl_random_pseudo_bytes($this->ivlen);
    }

    public function encrypt(string $data): string
    {
    	return openssl_encrypt($data, $this->cipher, $this->key, $this->options, $this->iv, $this->tag);
    }

    public function decrypt(string $data): string
    {
    	return openssl_decrypt($data, $this->cipher, $this->key, $this->options, $this->iv, $this->tag);
    }
}
