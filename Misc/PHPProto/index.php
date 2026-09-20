<?php

// Dummy replica webserver for the 2021 Roblox mobile app.
// Serves canned JSON replicas of the original .NET endpoint responses,
// keeping the original request paths intact under /v1/...

define('JSON_DIR', __DIR__ . DIRECTORY_SEPARATOR . 'data');

// Fake identity used by mocked authenticated endpoints, consistent with the
// SubjectTargetId used in the /v1/enrollments replica. IDs are strings because
// the server runs 32-bit PHP and these exceed PHP_INT_MAX.
require __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
$CONFIG = load_config();
define('FAKE_USER_ID', $CONFIG['fake_user_id']);
define('FAKE_USERNAME', $CONFIG['fake_username']);
define('FAKE_DISPLAY_NAME', $CONFIG['fake_display_name']);

// Fake game used by the spoofed games endpoints.
define('FAKE_UNIVERSE_ID', '1818');
define('FAKE_PLACE_ID', '1818');
define('FAKE_GAME_NAME', 'Classic: Crossroads');
define('FAKE_GAME_DESCRIPTION', 'The classic ROBLOX level is back.');
define('FAKE_GAME_GENRE', 7); // Genre.Adventure

function fake_uuid()
{
    $b = random_bytes_56(16);
    $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
    $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);
    $hex = bin2hex($b);
    return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4) . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20, 12);
}

function fake_game_list_entry($universeId = FAKE_UNIVERSE_ID, $placeId = FAKE_PLACE_ID)
{
    // GameListEntry, declared lowercase in the DTO. Hand-built JSON string
    // because the server runs 32-bit PHP (big ints would overflow json_encode).
    return '{"universeId":' . $universeId . ',"name":"' . FAKE_GAME_NAME . '","placeId":' . $placeId . ',"rootPlaceId":' . $placeId . ',"gameDescription":"' . FAKE_GAME_DESCRIPTION . '","playerCount":1,"visitCount":69420,"creatorId":' . FAKE_USER_ID . ',"creatorType":1,"creatorName":"' . FAKE_USERNAME . '","genre":' . FAKE_GAME_GENRE . ',"totalUpVotes":420,"totalDownVotes":0,"analyticsIdentifier":null,"price":0,"isShowSponsoredLabel":false,"nativeAdData":"","isSponsored":false,"year":2023,"imageToken":"T_' . $placeId . '_icon"}';
}

function fake_universe_info($universeId = FAKE_UNIVERSE_ID, $placeId = FAKE_PLACE_ID)
{
    // MultiGetUniverseEntry, declared lowercase in the DTO.
    return '{"id":' . $universeId . ',"rootPlaceId":' . $placeId . ',"isPublic":true,"name":"' . FAKE_GAME_NAME . '","description":"' . FAKE_GAME_DESCRIPTION . '","sourceName":"' . FAKE_GAME_NAME . '","sourceDescription":"' . FAKE_GAME_DESCRIPTION . '","genre":' . FAKE_GAME_GENRE . ',"creator":{"id":' . FAKE_USER_ID . ',"name":"' . FAKE_USERNAME . '","type":1,"isRNVAccount":false,"hasVerifiedBadge":true},"favoritedCount":1000,"isFavoritedByUser":false,"isAllGenre":false,"universeAvatarType":1,"privacyType":1,"studioAccessToApisAllowed":false,"price":0,"isGenreEnforced":false,"playing":1,"created":"2021-06-01T00:00:00","updated":"2021-06-01T00:00:00","maxPlayers":50,"visits":69420,"createVipServersAllowed":true,"robloxPlaceId":' . $placeId . '}';
}

function fake_place_entry($placeId = FAKE_PLACE_ID, $universeId = FAKE_UNIVERSE_ID)
{
    // PlaceEntry, declared lowercase in the DTO.
    return '{"placeId":' . $placeId . ',"name":"' . FAKE_GAME_NAME . '","description":"' . FAKE_GAME_DESCRIPTION . '","year":2023,"robloxPlaceId":' . $placeId . ',"builderId":' . FAKE_USER_ID . ',"builderType":1,"builder":"' . FAKE_USERNAME . '","universeId":' . $universeId . ',"universeRootPlaceId":' . $placeId . ',"price":0,"playerCount":1,"isPlayable":true,"imageToken":"T_' . $placeId . '_icon","reasonProhibited":"None","maxPlayerCount":50,"genre":' . FAKE_GAME_GENRE . ',"moderationStatus":1,"created":"2021-06-01T00:00:00","updated":"2021-06-01T00:00:00"}';
}

function fake_game_sorts_json()
{
    // GamesController.GetGameSorts (lowercase anonymous names).
    return '{"sorts":[{"token":"popular","name":"Popular","displayName":"Popular","gameSetTypeId":1,"gameSetTargetId":90,"timeOptionsAvailable":false,"genreOptionsAvailable":false,"numberOfRows":1,"numberOfGames":0,"isDefaultSort":true,"contextUniverseId":null,"contextCountryRegionId":null,"tokenExpiryInSeconds":86400},{"token":"classics","name":"Classics","displayName":"Classics","gameSetTypeId":2,"gameSetTargetId":91,"timeOptionsAvailable":false,"genreOptionsAvailable":false,"numberOfRows":1,"numberOfGames":0,"isDefaultSort":true,"contextUniverseId":null,"contextCountryRegionId":null,"tokenExpiryInSeconds":86400},{"token":"mostFavorited","name":"Most Favorited","displayName":"Most Favorited","gameSetTypeId":4,"gameSetTargetId":93,"timeOptionsAvailable":false,"genreOptionsAvailable":false,"numberOfRows":1,"numberOfGames":0,"isDefaultSort":true,"contextUniverseId":null,"contextCountryRegionId":null,"tokenExpiryInSeconds":86400}],"timeFilters":[{"token":"Now","name":"Now","tokenExpiryInSeconds":3600},{"token":"PastDay","name":"PastDay","tokenExpiryInSeconds":3600},{"token":"PastWeek","name":"PastWeek","tokenExpiryInSeconds":3600},{"token":"PastMonth","name":"PastMonth","tokenExpiryInSeconds":3600},{"token":"AllTime","name":"AllTime","tokenExpiryInSeconds":3600}],"genreFilters":[{"token":"T638364961735517991_1_89de","name":"All","tokenExpiryInSeconds":3600},{"token":"T638364961735518009_19_3d2","name":"Building","tokenExpiryInSeconds":3600},{"token":"T638364961735518045_11_3de6","name":"Horror","tokenExpiryInSeconds":3600},{"token":"T638364961735518062_7_558c","name":"Town and City","tokenExpiryInSeconds":3600},{"token":"T638364961735518076_17_c371","name":"Military","tokenExpiryInSeconds":3600},{"token":"T638364961735518094_15_2056","name":"Comedy","tokenExpiryInSeconds":3600},{"token":"T638364961735518107_8_6d4f","name":"Medieval","tokenExpiryInSeconds":3600},{"token":"T638364961735518120_13_c168","name":"Adventure","tokenExpiryInSeconds":3600},{"token":"T638364961735518134_9_e6aa","name":"Sci-Fi","tokenExpiryInSeconds":3600},{"token":"T638364961735518156_12_13fb","name":"Naval","tokenExpiryInSeconds":3600},{"token":"T638364961735518169_20_46a","name":"FPS","tokenExpiryInSeconds":3600},{"token":"T638364961735518183_21_4bbf","name":"RPG","tokenExpiryInSeconds":3600},{"token":"T638364961735518192_14_efc6","name":"Sports","tokenExpiryInSeconds":3600},{"token":"T638364961735518205_10_fa83","name":"Fighting","tokenExpiryInSeconds":3600},{"token":"T638364961735518223_16_5d38","name":"Western","tokenExpiryInSeconds":3600}],"gameFilters":[{"token":"T638364961735518263_Any_56d2","name":"Any","tokenExpiryInSeconds":3600},{"token":"T638364961735518277_Classic_a1f4","name":"Classic","tokenExpiryInSeconds":3600}],"pageContext":{"pageId":"f5b1510e-3810-42ab-8135-8ffa5ef221ba","isSeeAllPage":null},"gameSortStyle":null}';
}

function base64url_encode($input)
{
    return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
}

function random_bytes_56($length)
{
    // PHP 7.0 has random_bytes(); Apache serves PHP 5.6 where it does not exist.
    // Prefer the CSPRNG if available, fall back to mt_rand-based bytes.
    if (function_exists('openssl_random_pseudo_bytes')) {
        return openssl_random_pseudo_bytes($length);
    }
    $result = '';
    for ($i = 0; $i < $length; $i++) {
        $result .= chr(mt_rand(0, 255));
    }
    return $result;
}

function make_fake_session_token()
{
    // JWT-shaped token (header.payload.signature) like RobloxSessionTokenCodec
    // produces; random bytes stand in for the HMAC-SHA512 signature.
    $header = base64url_encode('{"typ":"JWT","alg":"HS512"}');
    $payload = base64url_encode('{"sessionId":"' . bin2hex(random_bytes_56(16)) . '","createdAt":' . time() . '}');
    $signature = base64url_encode(random_bytes_56(64));
    return $header . '.' . $payload . '.' . $signature;
}

function send_json($body, $status = 200)
{
    http_response_code($status);
    header('Content-Type: application/json');
    header('X-Content-Type-Options: nosniff');
    echo $body;
    exit;
}

function send_json_error($code, $message, $status = 400)
{
    $payload = json_encode(array(
        'errors' => array(
            array('code' => $code, 'message' => $message),
        ),
    ));
    send_json($payload, $status);
}

function read_json_file($name)
{
    $path = JSON_DIR . DIRECTORY_SEPARATOR . $name;
    if (!is_file($path)) {
        return false;
    }
    return file_get_contents($path);
}



$requestPath = parse_url(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/', PHP_URL_PATH);
if (!$requestPath || $requestPath === '') {
    $requestPath = '/';
}
$requestPath = rtrim($requestPath, '/');
if ($requestPath === '') {
    $requestPath = '/';
}

switch ($requestPath) {
    case '/v1/settings/application':
        // Mirrors FeatureFlagsRoblox.GetApplicationSettingsModern
        $applicationNames = array(
            'RCCService2019',
            'PCDesktopClient2019',
            'RCCService2020',
            'PCStudioApp',
            'PCStudio221',
            'PCStudio223',
            'RCCService2021',
            'RCCServiceGDASTGWG72713',
            'PCDesktopClient',
            'PCDesktopClient2021',
            'PCDesktopCli223',
            'AndroidApp',
            'iOSApp',
        );

        $applicationName = isset($_REQUEST['applicationName']) ? $_REQUEST['applicationName'] : null;

        if (!in_array($applicationName, $applicationNames, true)) {
            send_json_error(1, 'Invalid application name: ' . $applicationName);
        }

        if ($applicationName === 'PCStudio221') {
            $applicationName = 'PCDesktopClient2021';
        }
        if ($applicationName === 'RCCServiceGDASTGWG72713') {
            $applicationName = 'RCCService2021';
        }

        $body = read_json_file($applicationName . '.json');
        if ($body === false) {
            send_json_error(0, 'Feature flags not found for ' . $applicationName);
        }
        send_json($body);
        break;

    case '/v1/locales/user-localization-locus-supported-locales':
        $body = read_json_file('Supportedlocales.json');
        if ($body === false) {
            send_json_error(0, 'Supported locales not found', 500);
        }
        send_json($body);
        break;

    case '/v1.1/Counters/Increment':
        // BypassController.TelemetryFunctions -> 200 OK, empty body
        http_response_code(200);
        exit;

    case '/mobile/pbe':
        // Telemetry.PBE -> 200 OK, empty body
        http_response_code(200);
        exit;

    case '/mobileapi/check-app-version':
        // BypassController.CheckAppVersion
        send_json('{"data":{"UpgradeAction":"None"}}');
        break;

    case '/v1/enrollments':
        // Telemetry.Enrollments
        send_json('{"data":[{"SubjectType":"BrowserTracker","SubjectTargetId":' . FAKE_USER_ID . ',"ExperimentName":"AllUsers.DevelopSplashScreen.GreenStartCreatingButton","Status":"Inactive","Variation":null}]}');
        break;

    case '/v1/get-enrollments':
        // Telemetry.GetEnrollments
        send_json('[]');
        break;

    case '/client/pbe':
        // Telemetry.PBE -> 200 OK, empty body
        http_response_code(200);
        exit;

    case '/v1.0/SequenceStatistics/BatchAddToSequencesV2':
        // BypassController.TelemetryFunctions
        http_response_code(200);
        exit;

    case '/notifications/signalr/negotiate':
        // BypassController.TelemetryFunctions
        http_response_code(200);
        exit;

    case '/universal-app-configuration/v1/behaviors/app-patch/content':
        // UniversalApp.AppPatch (anonymous type, PascalCase preserved)
        send_json('{"SchemeVersion":"1","CanaryUserIds":[],"CanaryPercentage":0}');
        break;

    case '/universal-app-configuration/v1/behaviors/app-policy/content':
        // UniversalApp.AppPolicy -> raw AppPolicy.json from JsonDataDirectory
        $body = read_json_file('AppPolicy.json');
        if ($body === false) {
            send_json_error(0, 'App policy not found', 500);
        }
        send_json($body);
        break;

    case '/users/account-info':
        // UsersController.AccountInfo (Roblox.Services.Api), PascalCase preserved
        send_json('{"UserId":' . FAKE_USER_ID . ',"Username":"' . FAKE_USERNAME . '","DisplayName":"' . FAKE_DISPLAY_NAME . '","HasPasswordSet":true,"Email":"' . FAKE_USERNAME . '@mobile.com","MembershipType":3,"RobuxBalance":0,"AgeBracket":0,"Roles":[],"EmailNotificationEnabled":false,"PasswordNotifcationEnabled":false}');
        break;

    case '/device/initialize':
        // DeviceController.Initialize (Roblox.Services.Api), both GET and POST.
        // camelCase anonymous type: browserTrackerId + appDeviceIdentifier (null).
        send_json('{"browserTrackerId":1234567890,"appDeviceIdentifier":null}');
        break;

    case '/v1/login':
        // RobloxLogin.LoginV1 but mocked: accepts ANY credentials, always 200,
        // sets fake .ROBLOSECURITY + .PUPPYSECURITY cookies (RobloxSessionCookieWriter).
        $token = make_fake_session_token();
        header('Set-Cookie: .ROBLOSECURITY=' . $token . '; Path=/; HttpOnly; SameSite=Lax; Max-Age=1209600', false);
        header('Set-Cookie: .PUPPYSECURITY=' . $token . '; Path=/; HttpOnly; SameSite=Lax; Max-Age=1209600', false);
        send_json('{"user":{"id":' . FAKE_USER_ID . ',"name":"' . FAKE_USERNAME . '","displayName":"' . FAKE_DISPLAY_NAME . '"},"isBanned":false}');
        break;

    case '/v1.1/Counters/BatchIncrement':
        // BypassController.TelemetryFunctions
        http_response_code(200);
        exit;

    case '/pe':
        // Telemetry.GetPe -> Array.Empty<object>()
        send_json('[]');
        break;

    case '/v2/chat-settings':
    case '/v1/chat-settings':
        // Chat.GetChatSettings (both RobloxApi\Chat.cs and v2\Chat.cs).
        // chatEnabled = FeatureFlags.IsEnabled(FeatureFlag.WebsiteChat), which
        // defaults to true when no Redis snapshot is present.
        send_json('{"chatEnabled":true,"isActiveChatUser":true}');
        break;

    case '/v2/get-rollout-settings':
        // BypassController.ChatRollout
        $featureNames = isset($_REQUEST['featureNames']) ? $_REQUEST['featureNames'] : '';
        send_json('{"rolloutFeatures":[{"featureName":' . json_encode($featureNames) . ',"isRolloutEnabled":true}]}');
        break;

    case '/v2/push-notifications/register-android-native':
        // Not in this backend; the app only expects a success.
        http_response_code(200);
        exit;

    case '/v1/performance/measurements':
        // MetricsControllerV1.ReportMeasurements -> void
        http_response_code(200);
        exit;

    case '/alerts/alert-info':
        // ApiControllerV1.GetAlert -> no global alert
        send_json('{"IsVisible":false,"Text":"","LinkText":"","LinkUrl":""}');
        break;

    case '/incoming-items/counts':
        // BypassController.IncomingItems
        send_json('{"success":true}');
        break;

    case '/v1/join-game':
        // Handled entirely by the standalone join-game.php endpoint file.
        require __DIR__ . DIRECTORY_SEPARATOR . 'join-game.php';
        exit;

    case '/Game/Join.ashx':
    case '/game/join.ashx':
    case '/games/join.ashx':
        // Handled entirely by the standalone join-ashx.php endpoint file.
        // Real route is "game/join.ashx"; aliases cover what the app requests.
        require __DIR__ . DIRECTORY_SEPARATOR . 'join-ashx.php';
        exit;

    case '/Game/JoinRate.ashx':
        // Launch telemetry: launcher GETs ?c=<metricType>&r=<result>&d=<ms>
        // (RobloxLaunch._reportDuration) and ignores the body. 2xx is enough.
        http_response_code(200);
        header('Content-Type: text/plain');
        echo '';
        exit;

    case '/asset':
    case '/v1/asset':
        // AssetDeliveryController.GetAssetDelivery -> raw asset bytes.
        // id comes from the ?id= query param (primary) or the AssetId header.
        $assetId = isset($_GET['id']) ? $_GET['id'] : (isset($_SERVER['HTTP_ASSETID']) ? $_SERVER['HTTP_ASSETID'] : '1818');
        if ($assetId === '') {
            send_json_error(0, 'Missing asset id', 400);
        }
        $assetPath = __DIR__ . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'places' . DIRECTORY_SEPARATOR . $assetId;
        if (!is_file($assetPath)) {
            http_response_code(404);
            header('Content-Type: text/plain');
            echo 'Asset not found';
            exit;
        }
        http_response_code(200);
        header('Content-Type: application/octet-stream');
        header('Content-Length: ' . filesize($assetPath));
        readfile($assetPath);
        exit;

    case '/marketplace/productinfo':
        // MarketplaceController.GetProductInfo (Roblox.Services.Api).
        // Hand-built JSON string (mirrors the PascalCase anonymous object).
        $assetId = isset($_GET['assetId']) ? $_GET['assetId'] : '1818';
        $response = '{"TargetId":' . $assetId . ',' .
            '"AssetId":' . $assetId . ',' .
            '"ProductId":' . $assetId . ',' .
            '"Name":"Classic: Crossroads",' .
            '"Description":"The classic ROBLOX level is back.",' .
            '"AssetTypeId":9,' .
            '"Creator":{"Id":' . FAKE_USER_ID . ',"Name":"' . FAKE_USERNAME . '","CreatorType":1,"CreatorTargetId":' . FAKE_USER_ID . '},' .
            '"IconImageAssetId":0,' .
            '"Created":"2008-05-01T00:00:00",' .
            '"Updated":"2021-06-01T00:00:00",' .
            '"PriceInRobux":0,' .
            '"PriceInTickets":null,' .
            '"Sales":0,' .
            '"IsNew":false,' .
            '"IsForSale":true,' .
            '"IsPublicDomain":true,' .
            '"IsLimited":false,' .
            '"IsLimitedUnique":false,' .
            '"Remaining":0,' .
            '"MinimumMembershipLevel":0}';
        send_json($response);
        break;

    case '/v1/games':
        // GamesController.MultiGetUniverseInfo -> {data: [MultiGetUniverseEntry]}. 
        // universeIds is a comma-separated list; every requested id resolves to
        // the fake game (id 1818), so the client can always reach the join flow.
        $universeIds = isset($_GET['universeIds']) ? (string)$_GET['universeIds'] : '';
        preg_match_all('/[0-9]+/', $universeIds, $matches);
        $universes = array();
        foreach ($matches[0] as $id) {
            $universes[] = fake_universe_info($id, FAKE_PLACE_ID);
        }
        if (count($universes) === 0) {
            $universes[] = fake_universe_info();
        }
        send_json('{"data":[' . implode(',', $universes) . ']}');
        break;

    case '/v1/games/list':
        // GamesController.GetGamesList -> {games: [...]}
        send_json('{"games":[' . fake_game_list_entry() . ']}');
        break;

    case '/v1/games/sorts':
        // GamesController.GetGameSorts (camelCase/lowercase anonymous names)
        send_json(fake_game_sorts_json());
        break;

    case '/v1/games/multiget-playability-status':
        // GamesController.MultiGetPlayabilityStatus -> [{playabilityStatus, isPlayable, universeId}]
        $ids = isset($_SERVER['QUERY_STRING']) ? urldecode($_SERVER['QUERY_STRING']) : '';
        preg_match_all('/([0-9]+)/', $ids, $matches);
        $entries = '';
        $seen = array();
        foreach ($matches[1] as $id) {
            if (isset($seen[$id])) continue;
            $seen[$id] = true;
            if ($entries !== '') $entries .= ',';
            $entries .= '{"playabilityStatus":"Playable","isPlayable":true,"universeId":' . $id . '}';
        }
        send_json('[' . $entries . ']');
        break;

    case '/v1/games/multiget-place-details':
        // GamesController.MultiGetPlaceDetails -> [PlaceEntry].
        // placeIds is a comma-separated list; every requested id resolves to
        // the fake game place (id 1818).
        $placeIds = isset($_GET['placeIds']) ? (string)$_GET['placeIds'] : '';
        preg_match_all('/[0-9]+/', $placeIds, $matches);
        $places = array();
        foreach ($matches[0] as $id) {
            $places[] = fake_place_entry($id, FAKE_UNIVERSE_ID);
        }
        if (count($places) === 0) {
            $places[] = fake_place_entry();
        }
        send_json('[' . implode(',', $places) . ']');
        break;

    case '/games/getgameinstancesjson':
        // WebController.GetGameServers -> fake "continue" server rows
        send_json('{"PlaceId":' . FAKE_PLACE_ID . ',"ShowShutdownAllButton":false,"Collection":[{"placeId":' . FAKE_PLACE_ID . ',"Capacity":50,"Ping":60,"Fps":60,"ShowSlowGameMessage":false,"UserCanJoin":true,"ShowShutdownButton":false,"jobId":"' . fake_uuid() . '","FriendsMouseover":"","FriendsDescription":"","PlayersCapacity":"1 of 50","RobloxAppJoinScript":"","CurrentPlayers":[{"Id":' . FAKE_USER_ID . ',"Username":"' . FAKE_USERNAME . '","Thumbnail":{"IsFinal":true,"Url":"/Thumbs/Avatar-Headshot.ashx?userid=' . FAKE_USER_ID . '"}}]}],"TotalCollectionSize":1}');
        break;

    case '/':
        // Launcher page: "Go!" deep-links the Roblox app to the game on 1818.
        http_response_code(200);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Roblox Launcher</title>' .
            '<style>body{font-family:sans-serif;background:#1b1b2b;color:#fff;display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;margin:0}button{font-size:22px;padding:16px 48px;border:0;border-radius:8px;background:#00a2ff;color:#fff;cursor:pointer}button:hover{background:#007acc}p{color:#aaa;margin-top:20px}</style>' .
            '</head><body>' .
            '<button id="go">Go!</button>' .
            '<p>Opens Roblox place 1818</p>' .
            '<script>document.getElementById("go").addEventListener("click",function(){window.location.href="roblox://placeid=1818";});</script>' .
            '</body></html>';
        exit;

    default:
        if (strpos($requestPath, '/games-autocomplete/v1/get-suggestion') === 0) {
            // apis.roblox.com/games-autocomplete/v1/get-suggestion/<partial-name>
            $partial = substr($requestPath, strlen('/games-autocomplete/v1/get-suggestion'));
            $partial = trim($partial, '/');
            $partial = urldecode($partial);
            $suggestions = array();
            if ($partial !== '') {
                $suggestions[] = array(
                    'value' => FAKE_GAME_NAME,
                    'fullValue' => FAKE_GAME_NAME,
                    'pinnedIndex' => 0,
                );
            }
            send_json(json_encode(array('body' => $partial, 'suggestions' => $suggestions)));
        }

        if (preg_match('#^/v1/games/recommendations/game/(\d+)$#', $requestPath, $m)) {
            // GamesController.GetRecommendedGames
            send_json('{"games":[' . fake_game_list_entry() . ']}');
        }

        if (strpos($requestPath, '/v1/themes/User/') === 0) {
            // 2021 themes API, not in this backend. Mirrors the stack's
            // GetUserTheme shape: {"themeType":<ThemeTypes.Light>}.
            send_json('{"themeType":1}');
        }

        if (preg_match('#^/assets/(\d+)/versions$#', $requestPath, $m)) {
            // Client-side asset-version probe. NOT ported from TEMPORAL: this
            // source only reverse-proxies /assets/* (YARP catch-all), so there
            // is no controller to mirror. Shape mirrors the closest real one,
            // DevelopControllerV2.GetAssetVersions' {previousPageCursor,
            // nextPageCursor, data:[...]} envelope. Adjust if the mobile app
            // expects a different schema.
            $id = $m[1];
            $versionData = '{"Id":' . $id . ',"assetId":' . $id . ',"assetVersionNumber":1,"creatorTargetId":' . FAKE_USER_ID . ',"creatingUniverseId":' . FAKE_UNIVERSE_ID . ',"created":"2021-06-01T00:00:00","isEqualToCurrentPublishedVersion":true,"isPublished":true}';
            send_json('{"previousPageCursor":null,"nextPageCursor":null,"data":[' . $versionData . ']}');
        }

        send_json_error(0, 'Not found', 404);
        break;
}