<?php  
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
Name : Custom Encryption
Description : Custom Encryption for Codeigniter 3
Version : v0.01
*/
class Encryption_lib {
    var $ci;
    private $key;
    
    function __construct() {
        $this->ci =& get_instance();
        if (!empty($params['encryption_key'])) {
            $this->key = $params['encryption_key'];
        } else {
            // Generate a default key if not provided
            $this->key = openssl_random_pseudo_bytes(32); // 256-bit key for AES-256
        }
    }

    public function encrypt($data) {
        $iv = $this->generate_iv();
        $encryptedData = openssl_encrypt($data, 'aes-256-cbc', $this->key, 0, $iv);
        return [
            'data' => base64_encode($encryptedData),
            'iv' => base64_encode($iv)
        ];
    }

    public function decrypt($encryptedData, $iv) {
        $encryptedData = base64_decode($encryptedData);
        $iv = base64_decode($iv);
        return openssl_decrypt($encryptedData, 'aes-256-cbc', $this->key, 0, $iv);
    }

    public function get_key($source,$generate=false) {
        $source=md5($source);
        if($this->ci->session->$source!==NULL && $generate===false){
            $this->key=$this->ci->session->$source;
        }
        else{
            $this->ci->session->set_userdata($source,$this->key);
        }
        return base64_encode($this->key); // Return the key in a safe format
    }

    private function generate_iv() {
        return openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    }
}

