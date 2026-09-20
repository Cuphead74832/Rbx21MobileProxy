import base64
import datetime
import http.client
import ipaddress
import json
import os
import re
import secrets
import ssl
import sys
import threading
import time
import uuid
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from urllib.parse import parse_qs, unquote, urlparse

from cryptography import x509
from cryptography.hazmat.primitives import hashes, serialization
from cryptography.hazmat.primitives.asymmetric import padding, rsa
from cryptography.x509.oid import NameOID

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
JSON_DIR = os.path.join(BASE_DIR, "json")
ASSETS_DIR = os.path.join(BASE_DIR, "assets", "places")
CONFIG_PATH = os.path.join(BASE_DIR, "config.txt")
KEY_PATH = os.path.join(BASE_DIR, "privatekey.pem")
CERT_PATH = os.path.join(BASE_DIR, "cert.pem")
TLS_KEY_PATH = os.path.join(BASE_DIR, "cert.key")

CONFIG_KEYS = [
    ("host", "0.0.0.0"),
    ("port", "80"),
    ("https_port", "443"),
    ("base_url", "http://www.roblox.com"),
    ("api_base_url", "http://api.roblox.com"),
    ("machine_address", "127.0.0.1"),
    ("server_port", "2005"),
    ("membership", "None"),
    ("account_age_days", "1000"),
    ("country_code", "US"),
    ("asseturl", "https://localhost:2005"),
]

FAKE_UNIVERSE_ID = 1818
FAKE_PLACE_ID = 1818
FAKE_GAME_NAME = "Classic: Crossroads"
FAKE_GAME_DESCRIPTION = "The classic ROBLOX level is back."
FAKE_GAME_GENRE = 7

GAME_SORTS = {
    "sorts": [
        {"token": "popular", "name": "Popular", "displayName": "Popular", "gameSetTypeId": 1, "gameSetTargetId": 90, "timeOptionsAvailable": False, "genreOptionsAvailable": False, "numberOfRows": 1, "numberOfGames": 0, "isDefaultSort": True, "contextUniverseId": None, "contextCountryRegionId": None, "tokenExpiryInSeconds": 86400},
        {"token": "classics", "name": "Classics", "displayName": "Classics", "gameSetTypeId": 2, "gameSetTargetId": 91, "timeOptionsAvailable": False, "genreOptionsAvailable": False, "numberOfRows": 1, "numberOfGames": 0, "isDefaultSort": True, "contextUniverseId": None, "contextCountryRegionId": None, "tokenExpiryInSeconds": 86400},
        {"token": "mostFavorited", "name": "Most Favorited", "displayName": "Most Favorited", "gameSetTypeId": 4, "gameSetTargetId": 93, "timeOptionsAvailable": False, "genreOptionsAvailable": False, "numberOfRows": 1, "numberOfGames": 0, "isDefaultSort": True, "contextUniverseId": None, "contextCountryRegionId": None, "tokenExpiryInSeconds": 86400},
    ],
    "timeFilters": [
        {"token": "Now", "name": "Now", "tokenExpiryInSeconds": 3600},
        {"token": "PastDay", "name": "PastDay", "tokenExpiryInSeconds": 3600},
        {"token": "PastWeek", "name": "PastWeek", "tokenExpiryInSeconds": 3600},
        {"token": "PastMonth", "name": "PastMonth", "tokenExpiryInSeconds": 3600},
        {"token": "AllTime", "name": "AllTime", "tokenExpiryInSeconds": 3600},
    ],
    "genreFilters": [
        {"token": "T638364961735517991_1_89de", "name": "All", "tokenExpiryInSeconds": 3600},
        {"token": "T638364961735518009_19_3d2", "name": "Building", "tokenExpiryInSeconds": 3600},
        {"token": "T638364961735518045_11_3de6", "name": "Horror", "tokenExpiryInSeconds": 3600},
        {"token": "T638364961735518062_7_558c", "name": "Town and City", "tokenExpiryInSeconds": 3600},
        {"token": "T638364961735518076_17_c371", "name": "Military", "tokenExpiryInSeconds": 3600},
        {"token": "T638364961735518094_15_2056", "name": "Comedy", "tokenExpiryInSeconds": 3600},
        {"token": "T638364961735518107_8_6d4f", "name": "Medieval", "tokenExpiryInSeconds": 3600},
        {"token": "T638364961735518120_13_c168", "name": "Adventure", "tokenExpiryInSeconds": 3600},
        {"token": "T638364961735518134_9_e6aa", "name": "Sci-Fi", "tokenExpiryInSeconds": 3600},
        {"token": "T638364961735518156_12_13fb", "name": "Naval", "tokenExpiryInSeconds": 3600},
        {"token": "T638364961735518169_20_46a", "name": "FPS", "tokenExpiryInSeconds": 3600},
        {"token": "T638364961735518183_21_4bbf", "name": "RPG", "tokenExpiryInSeconds": 3600},
        {"token": "T638364961735518192_14_efc6", "name": "Sports", "tokenExpiryInSeconds": 3600},
        {"token": "T638364961735518205_10_fa83", "name": "Fighting", "tokenExpiryInSeconds": 3600},
        {"token": "T638364961735518223_16_5d38", "name": "Western", "tokenExpiryInSeconds": 3600},
    ],
    "gameFilters": [
        {"token": "T638364961735518263_Any_56d2", "name": "Any", "tokenExpiryInSeconds": 3600},
        {"token": "T638364961735518277_Classic_a1f4", "name": "Classic", "tokenExpiryInSeconds": 3600},
    ],
    "pageContext": {"pageId": "f5b1510e-3810-42ab-8135-8ffa5ef221ba", "isSeeAllPage": None},
    "gameSortStyle": None,
}

APPLICATION_NAMES = [
    "RCCService2019",
    "PCDesktopClient2019",
    "RCCService2020",
    "PCStudioApp",
    "PCStudio221",
    "PCStudio223",
    "RCCService2021",
    "RCCServiceGDASTGWG72713",
    "PCDesktopClient",
    "PCDesktopClient2021",
    "PCDesktopCli223",
    "AndroidApp",
    "iOSApp",
]

LAUNCHER_HTML = ('<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Roblox Launcher</title>'
                 '<style>body{font-family:sans-serif;background:#1b1b2b;color:#fff;display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;margin:0}button{font-size:22px;padding:16px 48px;border:0;border-radius:8px;background:#00a2ff;color:#fff;cursor:pointer}button:hover{background:#007acc}p{color:#aaa;margin-top:20px}</style>'
                 '</head><body>'
                 '<button id="go">Go!</button>'
                 '<p>Opens Roblox place 1818</p>'
                 '<script>document.getElementById("go").addEventListener("click",function(){window.location.href="roblox://placeid=1818";});</script>'
                 '</body></html>')


def load_config():
    dirty = not os.path.exists(CONFIG_PATH)
    cfg = {}
    if os.path.exists(CONFIG_PATH):
        with open(CONFIG_PATH) as f:
            for line in f:
                line = line.strip()
                if "=" in line:
                    key, value = line.split("=", 1)
                    cfg[key.strip()] = value.strip()
    defaults = dict(CONFIG_KEYS)
    defaults["fake_user_id"] = str(secrets.randbelow(900000000000) + 10000000000)
    username = "Mobile" + f"{secrets.randbelow(10000):04d}"
    defaults["fake_username"] = username
    defaults["fake_display_name"] = username
    for key, value in defaults.items():
        if key not in cfg or cfg[key] == "":
            cfg[key] = value
            dirty = True
    if dirty:
        with open(CONFIG_PATH, "w") as f:
            for key, value in cfg.items():
                f.write(f"{key}={value}\n")
    cfg["fake_user_id"] = str(int(cfg["fake_user_id"]))
    cfg["server_port"] = int(cfg["server_port"])
    cfg["account_age_days"] = int(cfg["account_age_days"])
    return cfg


def load_key():
    with open(KEY_PATH, "rb") as f:
        return serialization.load_pem_private_key(f.read(), password=None)


def ensure_tls_files():
    if os.path.isfile(CERT_PATH) and os.path.isfile(TLS_KEY_PATH):
        return
    key = rsa.generate_private_key(public_exponent=65537, key_size=2048)
    now = datetime.datetime.utcnow()
    name = x509.Name([x509.NameAttribute(NameOID.COMMON_NAME, "www.roblox.com")])
    san = x509.SubjectAlternativeName([
        x509.DNSName("www.roblox.com"),
        x509.DNSName("roblox.com"),
        x509.DNSName("*.roblox.com"),
        x509.DNSName("api.roblox.com"),
        x509.DNSName("uploads.roblox.com"),
        x509.DNSName("assetdelivery.roblox.com"),
        x509.DNSName("games.roblox.com"),
        x509.DNSName("localhost"),
        x509.IPAddress(ipaddress.ip_address("127.0.0.1")),
    ])
    cert = (
        x509.CertificateBuilder()
        .subject_name(name)
        .issuer_name(name)
        .public_key(key.public_key())
        .serial_number(x509.random_serial_number())
        .not_valid_before(now - datetime.timedelta(days=1))
        .not_valid_after(now + datetime.timedelta(days=3650))
        .add_extension(san, critical=False)
        .add_extension(x509.BasicConstraints(ca=True, path_length=None), critical=True)
        .add_extension(x509.KeyUsage(
            digital_signature=True,
            content_commitment=False,
            key_encipherment=True,
            data_encipherment=False,
            key_agreement=False,
            key_cert_sign=True,
            crl_sign=True,
            encipher_only=None,
            decipher_only=None,
        ), critical=True)
        .add_extension(x509.ExtendedKeyUsage([
            x509.ExtendedKeyUsageOID.SERVER_AUTH,
            x509.ExtendedKeyUsageOID.CLIENT_AUTH,
        ]), critical=False)
        .sign(key, hashes.SHA256())
    )
    with open(TLS_KEY_PATH, "wb") as f:
        f.write(key.private_bytes(
            serialization.Encoding.PEM,
            serialization.PrivateFormat.TraditionalOpenSSL,
            serialization.NoEncryption(),
        ))
    with open(CERT_PATH, "wb") as f:
        f.write(cert.public_bytes(serialization.Encoding.PEM))


def build_tls_context():
    for _ in range(2):
        ensure_tls_files()
        try:
            context = ssl.SSLContext(ssl.PROTOCOL_TLS_SERVER)
            context.load_cert_chain(CERT_PATH, TLS_KEY_PATH)
            return context
        except Exception:
            for path in (CERT_PATH, TLS_KEY_PATH):
                if os.path.exists(path):
                    os.remove(path)
    raise ConnectionError("could not create TLS certificate")


def now_string():
    t = time.gmtime()
    return f"{t.tm_mon}/{t.tm_mday}/{t.tm_year} {time.strftime('%I:%M:%S %p', t)}"


def b64url(data):
    return base64.urlsafe_b64encode(data).rstrip(b"=").decode()


def fake_session_token():
    header = json.dumps({"typ": "JWT", "alg": "HS512"})
    payload = json.dumps({"sessionId": uuid.uuid4().hex, "createdAt": int(time.time())})
    return b64url(header.encode()) + "." + b64url(payload.encode()) + "." + b64url(secrets.token_bytes(64))


def sign_sha1(data, key):
    return key.sign(data, padding.PKCS1v15(), hashes.SHA1())


def signed_script(key, json_text):
    script = "\r\n" + json_text
    return "--rbxsig2%" + base64.b64encode(sign_sha1(script.encode(), key)).decode() + "%" + script


def build_join_script(cfg, key, place_id, job_id=None):
    user_id = str(cfg["fake_user_id"])
    username = cfg["fake_username"]
    display_name = cfg["fake_display_name"]
    if not job_id:
        job_id = str(uuid.uuid4())
    membership = cfg["membership"]
    account_age_days = cfg["account_age_days"]
    machine_address = cfg["machine_address"]
    server_port = cfg["server_port"]
    base_url = cfg["base_url"]
    formatted = now_string()
    country_code = cfg["country_code"]
    cookie = ""
    character_appearance_url = cfg["api_base_url"] + "/v1.1/avatar-fetch?userId=" + user_id + "&placeId=" + str(place_id)

    ticket2 = user_id + "\n" + username + "\n" + character_appearance_url + "\n" + job_id + "\n" + formatted
    ticket = "\n".join([
        formatted, job_id, user_id, user_id, "0", str(account_age_days), "f",
        str(len(username)), username, str(len(membership)), membership,
        str(len(country_code)), country_code, "0", "", str(len(username)), username,
    ])
    client_ticket = formatted + ";" + base64.b64encode(sign_sha1(ticket2.encode(), key)).decode() + ";" + base64.b64encode(sign_sha1(ticket.encode(), key)).decode() + ";4"

    session_id = "|".join([str(uuid.uuid4()), job_id, "0", machine_address, "8", formatted, "0", "null", cookie, "null", "null", "null"])

    join_script_json = {
        "ClientPort": 0,
        "MachineAddress": machine_address,
        "ServerPort": server_port,
        "PingUrl": "",
        "PingInterval": 0,
        "UserName": username,
        "SeleniumTestMode": False,
        "UserId": int(user_id),
        "SuperSafeChat": False,
        "CharacterAppearance": character_appearance_url,
        "ClientTicket": client_ticket,
        "NewClientTicket": client_ticket,
        "GameChatType": "AllUsers",
        "GameId": job_id,
        "PlaceId": int(place_id),
        "MeasurementUrl": "",
        "WaitingForCharacterGuid": str(uuid.uuid4()),
        "BaseUrl": base_url,
        "ChatStyle": "ClassicAndBubble",
        "VendorId": 0,
        "ScreenShotInfo": "",
        "VideoInfo": "",
        "CreatorId": int(user_id),
        "CreatorTypeEnum": "User",
        "MembershipType": membership,
        "AccountAge": account_age_days,
        "CookieStoreFirstTimePlayKey": "rbx_evt_ftp",
        "CookieStoreFiveMinutePlayKey": "rbx_evt_fmp",
        "CookieStoreEnabled": True,
        "IsRobloxPlace": False,
        "GenerateTeleportJoin": True,
        "IsUnknownOrUnder13": False,
        "SessionId": session_id,
        "DataCenterId": 0,
        "UniverseId": FAKE_UNIVERSE_ID,
        "BrowserTrackerId": 0,
        "UsePortraitMode": False,
        "FollowUserId": 0,
        "characterAppearanceId": int(user_id),
        "DisplayName": display_name,
        "RobloxLocale": "RobloxLocale",
        "GameLocale": "en_us",
        "CountryCode": "US",
    }
    json_text = json.dumps(join_script_json)
    return {
        "jobId": job_id,
        "signed": signed_script(key, json_text),
        "json": json_text,
    }


def game_list_entry(universe_id=FAKE_UNIVERSE_ID, place_id=FAKE_PLACE_ID, cfg=None):
    user_id = int(cfg["fake_user_id"])
    username = cfg["fake_username"]
    return {
        "universeId": int(universe_id),
        "name": FAKE_GAME_NAME,
        "placeId": int(place_id),
        "rootPlaceId": int(place_id),
        "gameDescription": FAKE_GAME_DESCRIPTION,
        "playerCount": 1,
        "visitCount": 69420,
        "creatorId": user_id,
        "creatorType": 1,
        "creatorName": username,
        "genre": FAKE_GAME_GENRE,
        "totalUpVotes": 420,
        "totalDownVotes": 0,
        "analyticsIdentifier": None,
        "price": 0,
        "isShowSponsoredLabel": False,
        "nativeAdData": "",
        "isSponsored": False,
        "year": 2023,
        "imageToken": "T_" + str(place_id) + "_icon",
    }

def universe_info(universe_id=FAKE_UNIVERSE_ID, place_id=FAKE_PLACE_ID, cfg=None):
    user_id = int(cfg["fake_user_id"])
    username = cfg["fake_username"]
    return {
        "id": int(universe_id),
        "rootPlaceId": int(place_id),
        "isPublic": True,
        "name": FAKE_GAME_NAME,
        "description": FAKE_GAME_DESCRIPTION,
        "sourceName": FAKE_GAME_NAME,
        "sourceDescription": FAKE_GAME_DESCRIPTION,
        "genre": FAKE_GAME_GENRE,
        "creator": {"id": user_id, "name": username, "type": 1, "isRNVAccount": False, "hasVerifiedBadge": True},
        "favoritedCount": 1000,
        "isFavoritedByUser": False,
        "isAllGenre": False,
        "universeAvatarType": 1,
        "privacyType": 1,
        "studioAccessToApisAllowed": False,
        "price": 0,
        "isGenreEnforced": False,
        "playing": 1,
        "created": "2021-06-01T00:00:00",
        "updated": "2021-06-01T00:00:00",
        "maxPlayers": 50,
        "visits": 69420,
        "createVipServersAllowed": True,
        "robloxPlaceId": int(place_id),
    }

def place_entry(place_id=FAKE_PLACE_ID, universe_id=FAKE_UNIVERSE_ID, cfg=None):
    user_id = int(cfg["fake_user_id"])
    username = cfg["fake_username"]
    return {
        "placeId": int(place_id),
        "name": FAKE_GAME_NAME,
        "description": FAKE_GAME_DESCRIPTION,
        "year": 2023,
        "robloxPlaceId": int(place_id),
        "builderId": user_id,
        "builderType": 1,
        "builder": username,
        "universeId": int(universe_id),
        "universeRootPlaceId": int(place_id),
        "price": 0,
        "playerCount": 1,
        "isPlayable": True,
        "imageToken": "T_" + str(place_id) + "_icon",
        "reasonProhibited": "None",
        "maxPlayerCount": 50,
        "genre": FAKE_GAME_GENRE,
        "moderationStatus": 1,
        "created": "2021-06-01T00:00:00",
        "updated": "2021-06-01T00:00:00",
    }


def read_json_file(name):
    path = os.path.join(JSON_DIR, name)
    if not os.path.isfile(path):
        return None
    with open(path, "rb") as f:
        return f.read()


def unverified_tls_context():
    for proto in (getattr(ssl, "PROTOCOL_TLS_CLIENT", None), getattr(ssl, "PROTOCOL_TLS", None), getattr(ssl, "PROTOCOL_SSLv23", None)):
        if proto is None:
            continue
        try:
            context = ssl.SSLContext(proto)
            try:
                context.check_hostname = False
            except Exception:
                pass
            try:
                context.verify_mode = ssl.CERT_NONE
            except Exception:
                pass
            return context
        except Exception:
            continue
    for name in ("_create_unverified_context", "create_unverified_context"):
        factory = getattr(ssl, name, None)
        if factory is not None:
            try:
                return factory()
            except Exception:
                continue
    raise ConnectionError("ssl module unusable")


class Server(ThreadingHTTPServer):
    daemon_threads = True

    def __init__(self, addr, cfg, key):
        self.cfg = cfg
        self.key = key
        super().__init__(addr, Handler)


class Handler(BaseHTTPRequestHandler):
    server_version = "Rbx21MobileProxy"
    protocol_version = "HTTP/1.1"

    def log_message(self, fmt, *args):
        sys.stderr.write("[%s] %s\n" % (self.log_date_time_string(), fmt % args))

    def body(self):
        length = int(self.headers.get("Content-Length", 0) or 0)
        if length <= 0:
            return b""
        return self.rfile.read(length)

    def auth_cookie(self):
        return self.cookie_value(".PUPPYSECURITY") or self.cookie_value("PUPPYSECURITY")

    def fetch_upstream(self, base, method, target, data):
        parsed = urlparse(base)
        scheme = parsed.scheme or "https"
        host = parsed.hostname or "localhost"
        port = parsed.port or (443 if scheme == "https" else 80)
        headers = {}
        for name, value in self.headers.items():
            low = name.lower()
            if low in ("host", "content-length", "connection", "accept-encoding", "transfer-encoding"):
                continue
            headers[name] = value
        errors = []
        for use_tls in (scheme == "https", False):
            try:
                if use_tls:
                    context = unverified_tls_context()
                    try:
                        context.minimum_version = ssl.TLSVersion.TLSv1
                    except Exception:
                        pass
                    conn = http.client.HTTPSConnection(host, port, context=context, timeout=15)
                else:
                    conn = http.client.HTTPConnection(host, port, timeout=15)
                try:
                    conn.request(method, target, body=data or None, headers=headers)
                    resp = conn.getresponse()
                    body = resp.read()
                    return body, resp.getheader("Content-Type"), resp.status
                finally:
                    conn.close()
            except Exception as exc:
                errors.append(str(exc))
        raise ConnectionError(" / ".join(errors))

    def params(self):
        result = {}
        parsed = urlparse(self.path)
        for key, value in parse_qs(parsed.query).items():
            result[key] = value[0]
        if self.command in ("POST", "PUT"):
            raw = self.body()
            ctype = self.headers.get("Content-Type", "")
            if raw:
                if "application/json" in ctype:
                    try:
                        for key, value in json.loads(raw.decode("utf-8", "replace")).items():
                            if isinstance(value, (str, int, float, bool)):
                                result[key] = str(value)
                    except Exception:
                        pass
                else:
                    for key, value in parse_qs(raw.decode("utf-8", "replace")).items():
                        result[key] = value[0]
        return result

    def send_bytes(self, status, ctype, data, extra_headers=None):
        self.send_response(status)
        self.send_header("Content-Type", ctype)
        self.send_header("Content-Length", str(len(data)))
        for key, value in (extra_headers or []):
            self.send_header(key, value)
        self.end_headers()
        if data and self.command != "HEAD":
            self.wfile.write(data)

    def drain_body(self):
        length = int(self.headers.get("Content-Length", 0) or 0)
        if length > 0:
            try:
                self.rfile.read(length)
            except Exception:
                pass

    def send_json(self, obj, status=200, extra_headers=None):
        if isinstance(obj, str):
            data = obj.encode()
        elif isinstance(obj, bytes):
            data = obj
        else:
            data = json.dumps(obj).encode()
        headers = [("X-Content-Type-Options", "nosniff")] + (extra_headers or [])
        self.send_bytes(status, "application/json", data, headers)

    def send_json_error(self, code, message, status=400):
        self.send_json({"errors": [{"code": code, "message": message}]}, status)

    def empty_ok(self):
        self.send_bytes(200, "text/plain", b"")

    def cookie_value(self, name):
        cookie_line = self.headers.get("Cookie", "")
        for part in cookie_line.split(";"):
            part = part.strip()
            if part.startswith(name + "="):
                return part[len(name) + 1:]
        return ""

    def do_GET(self):
        self.handle_request()

    def do_POST(self):
        self.handle_request()

    def do_HEAD(self):
        self.handle_request()

    def do_PUT(self):
        self.handle_request()

    def do_DELETE(self):
        self.handle_request()

    def do_PATCH(self):
        self.handle_request()

    def do_OPTIONS(self):
        self.handle_request()

    BODY_CONSUMING = ("/v1/join-game", "/asset", "/v1/asset", "/v1/settings/application", "/v2/get-rollout-settings")

    def handle_request(self):
        cfg = self.server.cfg
        key = self.server.key
        try:
            self.dispatch(cfg, key)
        except BrokenPipeError:
            pass
        except Exception:
            self.send_json_error(0, "Internal server error", 500)

    def dispatch(self, cfg, key):
        parsed = urlparse(self.path)
        path = parsed.path
        path = path.rstrip("/") if path != "/" else path
        query = parsed.query
        if self.command in ("POST", "PUT", "PATCH") and path not in self.BODY_CONSUMING:
            self.drain_body()
        user_id = cfg["fake_user_id"]
        username = cfg["fake_username"]
        display_name = cfg["fake_display_name"]
        base_url = cfg["base_url"]

        if path == "/v1/settings/application":
            params = self.params()
            app = params.get("applicationName")
            if app is None:
                app = ""
            if app not in APPLICATION_NAMES:
                self.send_json_error(1, "Invalid application name: " + app)
                return
            if app == "PCStudio221":
                app = "PCDesktopClient2021"
            if app == "RCCServiceGDASTGWG72713":
                app = "RCCService2021"
            body = read_json_file(app + ".json")
            if body is None:
                self.send_json_error(0, "Feature flags not found for " + app)
                return
            self.send_json(body)
            return

        if path == "/v1/locales/user-localization-locus-supported-locales":
            body = read_json_file("Supportedlocales.json")
            if body is None:
                self.send_json_error(0, "Supported locales not found", 500)
                return
            self.send_json(body)
            return

        if path == "/notifications/signalr/negotiate":
            self.send_json({
                "Url": base_url + "/notifications/signalr",
                "ConnectionToken": b64url(secrets.token_bytes(48)),
                "ConnectionId": str(uuid.uuid4()) + "-UserNotificationHub",
                "KeepAliveTimeout": 20.0,
                "DisconnectTimeout": 30.0,
                "ConnectionTimeout": 110.0,
                "TryWebSockets": True,
                "ProtocolVersion": "1.4",
                "TransportConnectTimeout": 5.0,
                "LongPollDelay": 0.0,
            })
            return

        if path in ("/v1.1/Counters/Increment", "/mobile/pbe", "/client/pbe",
                    "/v1.0/SequenceStatistics/BatchAddToSequencesV2",
                    "/v1.1/Counters/BatchIncrement",
                    "/v2/push-notifications/register-android-native",
                    "/v1/performance/measurements"):
            self.empty_ok()
            return

        if path == "/mobileapi/check-app-version":
            self.send_json({"data": {"UpgradeAction": "None"}})
            return

        if path == "/v1/enrollments":
            self.send_json({"data": [{"SubjectType": "BrowserTracker", "SubjectTargetId": int(user_id), "ExperimentName": "AllUsers.DevelopSplashScreen.GreenStartCreatingButton", "Status": "Inactive", "Variation": None}]})
            return

        if path == "/v1/get-enrollments":
            self.send_json([])
            return

        if path == "/universal-app-configuration/v1/behaviors/app-patch/content":
            self.send_json({"SchemeVersion": "1", "CanaryUserIds": [], "CanaryPercentage": 0})
            return

        if path == "/universal-app-configuration/v1/behaviors/app-policy/content":
            body = read_json_file("AppPolicy.json")
            if body is None:
                self.send_json_error(0, "App policy not found", 500)
                return
            self.send_json(body)
            return

        if path == "/users/account-info":
            self.send_json({
                "UserId": int(user_id),
                "Username": username,
                "DisplayName": display_name,
                "HasPasswordSet": True,
                "Email": username + "@mobile.com",
                "MembershipType": 3,
                "RobuxBalance": 0,
                "AgeBracket": 0,
                "Roles": [],
                "EmailNotificationEnabled": False,
                "PasswordNotifcationEnabled": False,
            })
            return

        if path == "/device/initialize":
            token = fake_session_token()
            cookie = ".ROBLOSECURITY=" + token + "; Path=/; HttpOnly; SameSite=Lax; Max-Age=1209600"
            cookie2 = ".PUPPYSECURITY=" + token + "; Path=/; HttpOnly; SameSite=Lax; Max-Age=1209600"
            self.send_json(
                {"browserTrackerId": 1234567890, "appDeviceIdentifier": None},
                extra_headers=[("Set-Cookie", cookie), ("Set-Cookie", cookie2)],
            )
            return

        if path == "/v1/login":
            token = fake_session_token()
            cookie = ".ROBLOSECURITY=" + token + "; Path=/; HttpOnly; SameSite=Lax; Max-Age=1209600"
            cookie2 = ".PUPPYSECURITY=" + token + "; Path=/; HttpOnly; SameSite=Lax; Max-Age=1209600"
            self.send_json(
                {"user": {"id": int(user_id), "name": username, "displayName": display_name}, "isBanned": False},
                extra_headers=[("Set-Cookie", cookie), ("Set-Cookie", cookie2)],
            )
            return

        if path == "/pe":
            self.send_json([])
            return

        if path in ("/v2/chat-settings", "/v1/chat-settings"):
            self.send_json({"chatEnabled": True, "isActiveChatUser": True})
            return

        if path == "/v2/get-rollout-settings":
            params = self.params()
            feature_names = params.get("featureNames", "")
            self.send_json({"rolloutFeatures": [{"featureName": feature_names, "isRolloutEnabled": True}]})
            return

        if path == "/alerts/alert-info":
            self.send_json({"IsVisible": False, "Text": "", "LinkText": "", "LinkUrl": ""})
            return

        if path == "/incoming-items/counts":
            self.send_json({"success": True})
            return

        if path == "/v1/join-game":
            raw_body = self.body()
            place_id = FAKE_PLACE_ID
            match = re.search(rb'"placeId"\s*:\s*(\d+)', raw_body)
            if match:
                place_id = int(match.group(1))
            js = build_join_script(cfg, key, place_id)
            self.send_json({
                "jobId": js["jobId"],
                "status": 2,
                "joinScriptUrl": base_url + "/Game/Join.ashx?jobId=" + js["jobId"],
                "authenticationUrl": base_url + "/Login/Negotiate.ashx",
                "authenticationTicket": self.auth_cookie(),
                "message": "Server found (%s)" % js["jobId"],
                "joinScript": js["signed"],
            })
            return

        if path in ("/Game/Join.ashx", "/game/join.ashx", "/games/join.ashx"):
            params = self.params()
            job_id = params.get("jobId", "")
            js = build_join_script(cfg, key, FAKE_PLACE_ID, job_id)
            self.send_bytes(200, "text/plain; charset=utf-8", js["signed"].encode())
            return

        if path == "/Game/JoinRate.ashx":
            self.empty_ok()
            return

        if path in ("/asset", "/v1/asset"):
            client_body = self.body()
            asset_base = cfg.get("asseturl", "https://localhost:2005") or "https://localhost:2005"
            asset_target = "/asset/" + (("?" + parsed.query) if parsed.query else "")
            try:
                data, ctype, status = self.fetch_upstream(asset_base, self.command, asset_target, client_body)
            except Exception as exc:
                sys.stderr.write("asset proxy error: %r\n" % (exc,))
                self.send_json_error(0, "Asset upstream unreachable: %s" % (exc,), 502)
                return
            self.send_bytes(status, ctype or "application/octet-stream", data)
            return

        if path == "/marketplace/productinfo":
            params = self.params()
            asset_id = params.get("assetId", "1818")
            self.send_json({
                "TargetId": int(asset_id),
                "AssetId": int(asset_id),
                "ProductId": int(asset_id),
                "Name": FAKE_GAME_NAME,
                "Description": FAKE_GAME_DESCRIPTION,
                "AssetTypeId": 9,
                "Creator": {"Id": int(user_id), "Name": username, "CreatorType": 1, "CreatorTargetId": int(user_id)},
                "IconImageAssetId": 0,
                "Created": "2008-05-01T00:00:00",
                "Updated": "2021-06-01T00:00:00",
                "PriceInRobux": 0,
                "PriceInTickets": None,
                "Sales": 0,
                "IsNew": False,
                "IsForSale": True,
                "IsPublicDomain": True,
                "IsLimited": False,
                "IsLimitedUnique": False,
                "Remaining": 0,
                "MinimumMembershipLevel": 0,
            })
            return

        if path == "/v1/games":
            params = self.params()
            universes = re.findall(r"[0-9]+", params.get("universeIds", ""))
            entries = []
            for universe_id in universes:
                entries.append(universe_info(universe_id, FAKE_PLACE_ID, cfg))
            if not entries:
                entries.append(universe_info(cfg=cfg))
            self.send_json({"data": entries})
            return

        if path == "/v1/games/list":
            self.send_json({"games": [game_list_entry(cfg=cfg)]})
            return

        if path == "/v1/games/sorts":
            self.send_json(GAME_SORTS)
            return

        if path == "/v1/games/multiget-playability-status":
            ids = unquote(query)
            entries = []
            seen = set()
            for universe_id in re.findall(r"[0-9]+", ids):
                if universe_id in seen:
                    continue
                seen.add(universe_id)
                entries.append({"playabilityStatus": "Playable", "isPlayable": True, "universeId": int(universe_id)})
            self.send_json(entries)
            return

        if path == "/v1/games/multiget-place-details":
            params = self.params()
            places = []
            for place_id in re.findall(r"[0-9]+", params.get("placeIds", "")):
                places.append(place_entry(place_id, FAKE_UNIVERSE_ID, cfg))
            if not places:
                places.append(place_entry(cfg=cfg))
            self.send_json(places)
            return

        if path == "/games/getgameinstancesjson":
            job_id = str(uuid.uuid4())
            self.send_json({
                "PlaceId": FAKE_PLACE_ID,
                "ShowShutdownAllButton": False,
                "Collection": [{
                    "placeId": FAKE_PLACE_ID,
                    "Capacity": 50,
                    "Ping": 60,
                    "Fps": 60,
                    "ShowSlowGameMessage": False,
                    "UserCanJoin": True,
                    "ShowShutdownButton": False,
                    "jobId": job_id,
                    "FriendsMouseover": "",
                    "FriendsDescription": "",
                    "PlayersCapacity": "1 of 50",
                    "RobloxAppJoinScript": "",
                    "CurrentPlayers": [{"Id": int(user_id), "Username": username, "Thumbnail": {"IsFinal": True, "Url": "/Thumbs/Avatar-Headshot.ashx?userid=" + user_id}}],
                }],
                "TotalCollectionSize": 1,
            })
            return

        if path == "/":
            self.send_bytes(200, "text/html; charset=utf-8", LAUNCHER_HTML.encode())
            return

        if path.startswith("/games-autocomplete/v1/get-suggestion"):
            partial = path[len("/games-autocomplete/v1/get-suggestion"):].strip("/")
            partial = unquote(partial)
            suggestions = []
            if partial != "":
                suggestions.append({"value": FAKE_GAME_NAME, "fullValue": FAKE_GAME_NAME, "pinnedIndex": 0})
            self.send_json({"body": partial, "suggestions": suggestions})
            return

        match = re.match(r"^/v1/games/recommendations/game/(\d+)$", path)
        if match:
            self.send_json({"games": [game_list_entry(cfg=cfg)]})
            return

        if path.startswith("/v1/themes/User/"):
            self.send_json({"themeType": 1})
            return

        match = re.match(r"^/assets/(\d+)/versions$", path)
        if match:
            asset_id = match.group(1)
            version_data = {
                "Id": int(asset_id),
                "assetId": int(asset_id),
                "assetVersionNumber": 1,
                "creatorTargetId": int(user_id),
                "creatingUniverseId": FAKE_UNIVERSE_ID,
                "created": "2021-06-01T00:00:00",
                "isEqualToCurrentPublishedVersion": True,
                "isPublished": True,
            }
            self.send_json({"previousPageCursor": None, "nextPageCursor": None, "data": [version_data]})
            return

        if path.startswith("/assets/"):
            segment = path[len("/assets/"):].split("/")[0]
            candidate = os.path.join(ASSETS_DIR, os.path.basename(segment))
            if os.path.isfile(candidate):
                with open(candidate, "rb") as f:
                    data = f.read()
                self.send_bytes(200, "application/octet-stream", data)
                return

        candidate = os.path.normpath(os.path.join(JSON_DIR, path.lstrip("/")))
        if candidate.startswith(JSON_DIR) and os.path.isfile(candidate):
            with open(candidate, "rb") as f:
                data = f.read()
            self.send_json(data)
            return

        self.send_json_error(0, "Not found: " + path, 404)


def main():
    cfg = load_config()
    key = load_key()
    host = cfg.get("host", "0.0.0.0")
    http_port = int(cfg.get("port", "80"))
    https_port = int(cfg.get("https_port", "443"))
    servers = []
    http_server = Server((host, http_port), cfg, key)
    http_server.daemon_threads = True
    servers.append(http_server)
    threading.Thread(target=http_server.serve_forever, daemon=True).start()
    print("Serving on %s:%d (%s @%s), key=%s" % (host, http_port, cfg["fake_username"], cfg["fake_user_id"], KEY_PATH))
    if https_port > 0:
        tls_context = build_tls_context()
        https_server = Server((host, https_port), cfg, key)
        https_server.daemon_threads = True
        https_server.socket = tls_context.wrap_socket(https_server.socket, server_side=True)
        servers.append(https_server)
        threading.Thread(target=https_server.serve_forever, daemon=True).start()
        print("HTTPS serving on %s:%d with %s" % (host, https_port, CERT_PATH))
    try:
        while True:
            time.sleep(3600)
    except KeyboardInterrupt:
        pass
    finally:
        for server in servers:
            server.server_close()


if __name__ == "__main__":
    main()