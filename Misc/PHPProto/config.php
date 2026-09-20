<?php

define('CONFIG_FILE', __DIR__ . DIRECTORY_SEPARATOR . 'config.txt');

function config_random_bytes($length)
{
    if (function_exists('openssl_random_pseudo_bytes')) {
        return openssl_random_pseudo_bytes($length);
    }
    $result = '';
    for ($i = 0; $i < $length; $i++) {
        $result .= chr(mt_rand(0, 255));
    }
    return $result;
}

function config_random_digits($length)
{
    $result = '';
    for ($i = 0; $i < $length; $i++) {
        $result .= (string) mt_rand(0, 9);
    }
    if ($result !== '' && $result[0] === '0') {
        $result[0] = (string) mt_rand(1, 9);
    }
    return $result;
}

function load_config()
{
    $cfg = array();
    if (is_file(CONFIG_FILE)) {
        $lines = @file(CONFIG_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines !== false) {
            foreach ($lines as $line) {
                $line = trim($line);
                if (strpos($line, '=') === false) {
                    continue;
                }
                list($key, $value) = explode('=', $line, 2);
                $cfg[trim($key)] = trim($value);
            }
        }
    }
    $dirty = false;
    $defaults = array(
        'base_url' => 'http://www.roblox.com',
        'api_base_url' => 'http://api.roblox.com',
        'machine_address' => '127.0.0.1',
        'server_port' => '2005',
        'membership' => 'None',
        'account_age_days' => '1000',
        'country_code' => 'US',
        'fake_user_id' => '9' . config_random_digits(10),
    );
    $defaults['fake_username'] = 'Mobile' . str_pad((string) mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
    $defaults['fake_display_name'] = $defaults['fake_username'];
    foreach ($defaults as $key => $value) {
        if (!isset($cfg[$key]) || $cfg[$key] === '') {
            $cfg[$key] = $value;
            $dirty = true;
        }
    }
    if ($dirty) {
        $fp = @fopen(CONFIG_FILE, 'w');
        if ($fp !== false) {
            foreach ($cfg as $key => $value) {
                fwrite($fp, $key . '=' . $value . "\n");
            }
            fclose($fp);
        }
    }
    return $cfg;
}