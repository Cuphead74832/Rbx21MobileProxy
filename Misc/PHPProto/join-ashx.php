<?php

// Endpoint handler for /Game/Join.ashx. Fully standalone: index.php only
// requires this file, and the signing lives in joinscript.php.
//
//   GET /Game/Join.ashx?jobId=xxx -> raw signed joinScript (--rbxsig2%...) text
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
);

$key = join_private_key();
if ($key === false) {
    send_json_error(0, 'Join-signing key not found. Place your 2048-bit RSA private key at: ' . join_key_path(), 500);
}

// BypassController.JoinGame returns SignJoinScript(...) directly, so the
// body is the RAW signed script text (--rbxsig2%...), not a JSON string.
$jobId = isset($_GET['jobId']) ? $_GET['jobId'] : fake_uuid();
$js = build_join_script($JOIN_RESPONSE_CONFIG['placeId'], $key, $jobId);
http_response_code(200);
header('Content-Type: text/plain; charset=utf-8');
echo $js['signed'];
exit;