<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/*
Name : ENV Loader
Description : ENV Loader
Version : v1.0
*/
class Env_loader {

    public function __construct() {
        $this->load_env();
    }

    private function load_env() {
        $env_file = FCPATH . '.env';
        if (file_exists($env_file)) {
            $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                // Skip comments
                if (strpos(trim($line), '#') === 0) continue;

                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                $_ENV[$key] = $value;
                if (!defined($key)) {
                    define($key, $value);
                }
            }
        }
    }
}
