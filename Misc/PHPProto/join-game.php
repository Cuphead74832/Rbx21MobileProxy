<?php

// Endpoint handler for the 2021 Roblox mobile join flow. Fully standalone:
// index.php only requires this file, and the signing lives in joinscript.php.
//
//   POST /v1/join-game            -> PlaceLaunchResponse (signed joinScript inline)
//   GET  /Game/Join.ashx?jobId=x  -> signed joinScript as a JSON string
//
// Response-level configuration is in $JOIN_RESPONSE_CONFIG below; join-script
// pointers (hosts/ports) are configured at the top of joinscript.php.

require_once __DIR__ . DIRECTORY_SEPARATOR . 'joinscript.php';

if (!function_exists('send_json')) {
    function send_json($body, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        header('X-Content-Type-Options: nosniff');
        echo $body;
        exit;
    }
}

if (!function_exists('send_json_error')) {
    function send_json_error($code, $message, $status = 400)
    {
        $payload = json_encode(array(
            'errors' => array(
                array('code' => $code, 'message' => $message),
            ),
        ));
        send_json($payload, $status);
    }
}

// Response-level config. Defaults mirror the stack; adjust as the client needs.
$JOIN_RESPONSE_CONFIG = array(
    'placeId' => FAKE_PLACE_ID,
    'status' => 2, // JoinStatus.Joining
    'message' => 'Server found (%s)',
);

function join_request_path()
{
    $path = parse_url(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/', PHP_URL_PATH);
    if (!$path || $path === '') {
        $path = '/';
    }
    $path = rtrim($path, '/');
    return $path === '' ? '/' : $path;
}

$path = join_request_path();
$key = join_private_key();
if ($key === false) {
    send_json_error(0, 'Join-signing key not found. Place your 2048-bit RSA private key at: ' . join_key_path(), 500);
}

if ($path === '/v1/join-game') {
    // BypassController.JoinGameMobile -> RequestGame(special=true) ->
    // PlaceLaunchResponse with status=Joining(2) + signed inline joinScript.
    $rawBody = file_get_contents('php://input');
    $placeId = $JOIN_RESPONSE_CONFIG['placeId'];
    if (preg_match('/"placeId"\s*:\s*(\d+)/', (string)$rawBody, $m)) {
        $placeId = $m[1];
    }
    $js = build_join_script($placeId, $key);
    $response = '{"jobId":' . json_encode($js['jobId']) . ',' .
        '"status":' . (int)$JOIN_RESPONSE_CONFIG['status'] . ',' .
        '"joinScriptUrl":' . json_encode($JOIN_SCRIPT_CONFIG['baseUrl'] . '/Game/Join.ashx?jobId=' . $js['jobId']) . ',' .
        '"authenticationUrl":' . json_encode($JOIN_SCRIPT_CONFIG['baseUrl'] . '/Login/Negotiate.ashx') . ',' .
        '"authenticationTicket":' . json_encode($js['cookie']) . ',' .
        '"message":' . json_encode(sprintf($JOIN_RESPONSE_CONFIG['message'], $js['jobId'])) . ',' .
        '"joinScript":' . json_encode($js['signed']) . '}';
    send_json($response);
}

send_json_error(0, 'Not found', 404);