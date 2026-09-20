<?php

// Join-script library for the 2021 Roblox mobile app dummy server.
// Holds every function used to build + sign the rbxsig2 joinScript so the
// endpoint file (join-game.php) stays free of signing details.
//
// The 2048-bit RSA private key lives at:
//   Webserver\www\privatekey.pem   (next to this file)
// It is the private half of the public key embedded in the app under test.
// Both "BEGIN RSA PRIVATE KEY" (PKCS#1) and "BEGIN PRIVATE KEY" (PKCS#8) PEM
// work, and it is re-read on every request (no server restart needed).

if (!isset($CONFIG)) {
    require_once __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
    $CONFIG = load_config();
}
if (!defined('FAKE_USER_ID')) define('FAKE_USER_ID', $CONFIG['fake_user_id']);
if (!defined('FAKE_USERNAME')) define('FAKE_USERNAME', $CONFIG['fake_username']);
if (!defined('FAKE_DISPLAY_NAME')) define('FAKE_DISPLAY_NAME', $CONFIG['fake_display_name']);
if (!defined('FAKE_UNIVERSE_ID')) define('FAKE_UNIVERSE_ID', '1818');
if (!defined('FAKE_PLACE_ID')) define('FAKE_PLACE_ID', '1818');

// Knobs for join-script generation. Change these to point the client wherever
// your game server actually lives.
$JOIN_SCRIPT_CONFIG = array(
    'baseUrl' => $CONFIG['base_url'],
    'apiBaseUrl' => $CONFIG['api_base_url'],
    'machineAddress' => $CONFIG['machine_address'],
    'serverPort' => (int) $CONFIG['server_port'],
    'membership' => $CONFIG['membership'],
    'accountAgeDays' => (int) $CONFIG['account_age_days'],
    'countryCode' => $CONFIG['country_code'],
    'privateKeyFile' => __DIR__ . DIRECTORY_SEPARATOR . 'privatekey.pem',
);

if (!function_exists('random_bytes_56')) {
    function random_bytes_56($length)
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
}

if (!function_exists('fake_uuid')) {
    function fake_uuid()
    {
        $b = random_bytes_56(16);
        $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
        $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);
        $hex = bin2hex($b);
        return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4) . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20, 12);
    }
}

function join_key_path()
{
    global $JOIN_SCRIPT_CONFIG;
    return $JOIN_SCRIPT_CONFIG['privateKeyFile'];
}

function join_openssl_cli()
{
    // The bundled OpenSSL binary that ships with this Apache, used as an
    // extension-free fallback for signing when the PHP openssl extension is
    // not loaded. Returns the path, or false when unusable.
    $candidates = array(
        __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'apache' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'openssl.exe',
        getenv('OPENSSL_CLI'),
    );
    foreach ($candidates as $candidate) {
        if ($candidate && is_file($candidate) && is_readable($candidate)) {
            return $candidate;
        }
    }
    return false;
}

function join_private_key()
{
    // Returns a PHP key handle when the openssl extension is loaded, otherwise
    // the PEM file path (used by the openssl.exe CLI fallback in join_sign_sha1).
    $pem = @file_get_contents(join_key_path());
    if ($pem === false || $pem === '') {
        return false;
    }
    if (function_exists('openssl_pkey_get_private')) {
        return openssl_pkey_get_private($pem);
    }
    return join_key_path();
}

function join_sign_sha1_cli($data, $pemFile)
{
    // PKCS#1 v1.5 + SHA1 via the bundled openssl.exe, so signing keeps working
    // even when the PHP openssl extension is not loaded. Data goes through a
    // temp file to stay binary-safe.
    if (!function_exists('exec')) {
        return false;
    }
    $cli = join_openssl_cli();
    if ($cli === false) {
        return false;
    }
    $tempDir = function_exists('sys_get_temp_dir') ? rtrim(sys_get_temp_dir(), '\\/') : '.';
    $inFile = @tempnam($tempDir, 'rbs');
    $outFile = @tempnam($tempDir, 'rbs');
    if ($inFile === false || $outFile === false) {
        if ($inFile !== false) @unlink($inFile);
        if ($outFile !== false) @unlink($outFile);
        return false;
    }
    if (@file_put_contents($inFile, $data) === false) {
        @unlink($inFile);
        @unlink($outFile);
        return false;
    }
    $cmd = '"' . $cli . '" dgst -sha1 -sign "' . $pemFile . '" -out "' . $outFile . '" "' . $inFile . '" 2>nul';
    $output = array();
    $ret = -1;
    @exec($cmd, $output, $ret);
    $signature = @file_get_contents($outFile);
    @unlink($inFile);
    @unlink($outFile);
    if ($ret !== 0 || $signature === false || $signature === '') {
        return false;
    }
    return $signature;
}

function join_sign_sha1($data, $key)
{
    // RSACryptoServiceProvider.SignData(..., SHA1) == PKCS#1 v1.5 + SHA1, which
    // is exactly what openssl_sign produces with OPENSSL_ALGO_SHA1.
    if (is_resource($key)) {
        openssl_sign($data, $signature, $key, OPENSSL_ALGO_SHA1);
        return $signature;
    }
    return join_sign_sha1_cli($data, (string)$key);
}

function join_sign_json_2048($key, $json)
{
    // SignJson2048: script = "\r\n" + json, then "--rbxsig2%sig%script".
    $script = "\r\n" . $json;
    $signature = join_sign_sha1($script, $key);
    return '--rbxsig2%' . base64_encode($signature) . '%' . $script;
}

function join_script_datetime()
{
    // DateTime.UtcNow.ToString("M/d/yyyy h:mm:ss tt"), hour zero-padded.
    return gmdate('n/j/Y h:i:s A');
}

function join_session_cookie()
{
    // PHP rewrites dots in cookie names to underscores.
    if (isset($_COOKIE['_PUPPYSECURITY'])) return $_COOKIE['_PUPPYSECURITY'];
    if (isset($_COOKIE['PUPPYSECURITY'])) return $_COOKIE['PUPPYSECURITY'];
    return '';
}

function build_join_script($placeId, $key, $jobId = null)
{
    // GetJoinScript (Games.cs) + GenerateClientTicketV4 (Signer.cs), hand-built
    // JSON string because 32-bit PHP cannot json_encode these big ints.
    global $JOIN_SCRIPT_CONFIG;
    if ($jobId === null || $jobId === '') {
        $jobId = fake_uuid();
    }
    $membership = $JOIN_SCRIPT_CONFIG['membership'];
    $accountAgeDays = $JOIN_SCRIPT_CONFIG['accountAgeDays'];
    $cookie = join_session_cookie();
    $machineAddress = $JOIN_SCRIPT_CONFIG['machineAddress'];
    $serverPort = $JOIN_SCRIPT_CONFIG['serverPort'];
    $baseUrl = $JOIN_SCRIPT_CONFIG['baseUrl'];
    $characterAppearanceUrl = $JOIN_SCRIPT_CONFIG['apiBaseUrl'] . '/v1.1/avatar-fetch?userId=' . FAKE_USER_ID . '&placeId=' . $placeId;
    $formattedDateTime = join_script_datetime();
    $countryCode = $JOIN_SCRIPT_CONFIG['countryCode'];

    // GenerateClientTicketV4 (years 2020 / 2021)
    $ticket2 = FAKE_USER_ID . "\n" . FAKE_USERNAME . "\n" . $characterAppearanceUrl . "\n" . $jobId . "\n" . $formattedDateTime;
    $ticket = $formattedDateTime . "\n" . $jobId . "\n" . FAKE_USER_ID . "\n" . FAKE_USER_ID . "\n0\n" . $accountAgeDays . "\nf\n" . strlen(FAKE_USERNAME) . "\n" . FAKE_USERNAME . "\n" . strlen($membership) . "\n" . $membership . "\n" . strlen($countryCode) . "\n" . $countryCode . "\n0\n\n" . strlen(FAKE_USERNAME) . "\n" . FAKE_USERNAME;
    $clientTicket = $formattedDateTime . ';' . base64_encode(join_sign_sha1($ticket2, $key)) . ';' . base64_encode(join_sign_sha1($ticket, $key)) . ';4';

    $sessionId = fake_uuid() . '|' . $jobId . '|0|' . $machineAddress . '|8|' . $formattedDateTime . '|0|null|' . $cookie . '|null|null|null';

    $joinScriptJson = '{"ClientPort":0,' .
        '"MachineAddress":' . json_encode($machineAddress) . ',' .
        '"ServerPort":' . $serverPort . ',' .
        '"PingUrl":"",' .
        '"PingInterval":0,' .
        '"UserName":' . json_encode(FAKE_USERNAME) . ',' .
        '"SeleniumTestMode":false,' .
        '"UserId":' . FAKE_USER_ID . ',' .
        '"SuperSafeChat":false,' .
        '"CharacterAppearance":' . json_encode($characterAppearanceUrl) . ',' .
        '"ClientTicket":' . json_encode($clientTicket) . ',' .
        '"NewClientTicket":' . json_encode($clientTicket) . ',' .
        '"GameChatType":"AllUsers",' .
        '"GameId":' . json_encode($jobId) . ',' .
        '"PlaceId":' . $placeId . ',' .
        '"MeasurementUrl":"",' .
        '"WaitingForCharacterGuid":' . json_encode(fake_uuid()) . ',' .
        '"BaseUrl":' . json_encode($baseUrl) . ',' .
        '"ChatStyle":"ClassicAndBubble",' .
        '"VendorId":0,' .
        '"ScreenShotInfo":"",' .
        '"VideoInfo":"",' .
        '"CreatorId":' . FAKE_USER_ID . ',' .
        '"CreatorTypeEnum":"User",' .
        '"MembershipType":' . json_encode($membership) . ',' .
        '"AccountAge":' . $accountAgeDays . ',' .
        '"CookieStoreFirstTimePlayKey":"rbx_evt_ftp",' .
        '"CookieStoreFiveMinutePlayKey":"rbx_evt_fmp",' .
        '"CookieStoreEnabled":true,' .
        '"IsRobloxPlace":false,' .
        '"GenerateTeleportJoin":true,' .
        '"IsUnknownOrUnder13":false,' .
        '"SessionId":' . json_encode($sessionId) . ',' .
        '"DataCenterId":0,' .
        '"UniverseId":' . FAKE_UNIVERSE_ID . ',' .
        '"BrowserTrackerId":0,' .
        '"UsePortraitMode":false,' .
        '"FollowUserId":0,' .
        '"characterAppearanceId":' . FAKE_USER_ID . ',' .
        '"DisplayName":' . json_encode(FAKE_DISPLAY_NAME) . ',' .
        '"RobloxLocale":"RobloxLocale",' .
        '"GameLocale":"en_us",' .
        '"CountryCode":"US"}';

    return array(
        'jobId' => $jobId,
        'cookie' => $cookie,
        'json' => $joinScriptJson,
        'signed' => join_sign_json_2048($key, $joinScriptJson),
    );
}