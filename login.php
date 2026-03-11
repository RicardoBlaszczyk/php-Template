<?php

// includes
include "sicher/data.php";

$arr_presumption = array(
    'action',
    'user',
    'pass',
    'userId',
    'msg',
    'name',
    'password',
    'mandant',
    'filiale',
    'userName',
    'token',
    'jwt'
);
foreach ($arr_presumption as $key => $value) {
    $$value = $_REQUEST[$value] ?? '';
}

$error = '';

/**
 * JWT-Login:
 * - Token kann als Request-Parameter `token` kommen oder per Header `Authorization: Bearer <jwt>`
 * - Unterstützte Algorithmen: HS256 und RS256 (wird aus dem JWT-Header `alg` gelesen)
 * - Verifikation:
 *   - HS256: Signaturprüfung mit `JWT_AUTH_SECRET`
 *   - RS256: Signaturprüfung mit `JWT_AUTH_PUBLIC_KEY` (PEM) oder `JWT_AUTH_PUBLIC_KEY_FILE` (Pfad zur PEM)
 *   - Optional: zusätzliches Shared-Secret im Payload-Claim `secret`, wenn `JWT_LOGIN_SHARED_SECRET` gesetzt ist
 * - Erwartete Claims für die Zuordnung:
 *   - `user_id` oder alternativ Standard-Claim `sub`
 *   - optional `user_name`
 *   - optional `user_pass` (nur falls ihr wirklich Passwort-basiert einloggen wollt)
 *
 * Ergebnis:
 * - `$jwtAllowed === true`, wenn die JWT-Prüfung (Signatur + optionale Shared-Secret-Prüfung) erfolgreich war
 */
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
$jwt_token = '';
if (!empty($jwt) && is_string($jwt)) {
    $jwt_token = $jwt;
} elseif (is_string($authHeader) && preg_match('/^\s*Bearer\s+(.+)\s*$/i', $authHeader, $m)) {
    $jwt_token = $m[1];
}
$jwtAllowed = false;
if (!empty($jwt_token)) {
    try {
        // JWT Library laden (falls nicht bereits durch andere Includes geladen)
        if (!class_exists('\\Firebase\\JWT\\JWT') || !class_exists('\\Firebase\\JWT\\Key')) {
            $jwtAutoload = __DIR__ . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'php-jwt'
                . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
            if (is_file($jwtAutoload)) {
                require_once $jwtAutoload;
            }
        }
        if (!class_exists('\\Firebase\\JWT\\JWT') || !class_exists('\\Firebase\\JWT\\Key')) {
            throw new RuntimeException('JWT-Library nicht verfügbar.');
        }

        // JWT Header lesen (für alg)
        $parts = explode('.', (string)$jwt_token);
        if (count($parts) !== 3) {
            throw new RuntimeException('JWT Format ungültig.');
        }
        $headerJson = base64_decode(strtr($parts[0], '-_', '+/'), true);
        if ($headerJson === false) {
            throw new RuntimeException('JWT Header Base64 ungültig.');
        }
        $header = json_decode($headerJson, true);
        if (!is_array($header) || empty($header['alg'])) {
            throw new RuntimeException('JWT Header ungültig (alg fehlt).');
        }
        $alg = (string)$header['alg'];

        // Key je Algorithmus bestimmen
        if ($alg === 'HS256') {
            $useHs256 = defined('JWT_AUTH_SECRET_USE') && (string)JWT_AUTH_SECRET_USE === 'aktiv';
            if (!$useHs256) {
                throw new RuntimeException('HS256 ist deaktiviert (JWT_AUTH_SECRET_USE != aktiv).');
            }

            $jwtSecret = defined('JWT_AUTH_SECRET') ? (string)JWT_AUTH_SECRET : '';
            if ($jwtSecret === '') {
                throw new RuntimeException('JWT Secret fehlt (JWT_AUTH_SECRET nicht gesetzt).');
            }
            $key = new \Firebase\JWT\Key($jwtSecret, 'HS256');
        } elseif ($alg === 'RS256') {
            $useRs256 = defined('JWT_AUTH_PUBLIC_KEY_USE') && (string)JWT_AUTH_PUBLIC_KEY_USE === 'aktiv';
            if (!$useRs256) {
                throw new RuntimeException('RS256 ist deaktiviert (JWT_AUTH_PUBLIC_KEY_USE != aktiv).');
            }

            $publicKeyPem = defined('JWT_AUTH_PUBLIC_KEY') ? (string)JWT_AUTH_PUBLIC_KEY : '';
            if ($publicKeyPem === '' && defined('JWT_AUTH_PUBLIC_KEY_FILE')) {
                $pubFile = (string)JWT_AUTH_PUBLIC_KEY_FILE;
                if ($pubFile !== '' && is_file($pubFile)) {
                    $publicKeyPem = (string)file_get_contents($pubFile);
                }
            }

            if (trim($publicKeyPem) === '') {
                throw new RuntimeException('Public Key fehlt (JWT_AUTH_PUBLIC_KEY oder JWT_AUTH_PUBLIC_KEY_FILE).');
            }
            $key = new \Firebase\JWT\Key($publicKeyPem, 'RS256');
        } else {
            throw new RuntimeException('JWT Algorithmus nicht erlaubt: ' . $alg);
        }

        // kleine Zeitabweichungen erlauben (Sekunden)
        \Firebase\JWT\JWT::$leeway = 120;

        $payloadObj = \Firebase\JWT\JWT::decode($jwt_token, $key);
        $payload = json_decode(json_encode($payloadObj), true);

        if (!is_array($payload)) {
            throw new RuntimeException('JWT Payload ist ungültig.');
        }

        // Optional: Shared-Secret prüfen (falls konfiguriert)
        $requiredSharedSecret = defined('JWT_LOGIN_SHARED_SECRET') ? (string)JWT_LOGIN_SHARED_SECRET : '';
        if ($requiredSharedSecret !== '') {
            $tokenSecret = isset($payload['secret']) ? (string)$payload['secret'] : '';
            if ($tokenSecret === '' || !hash_equals($requiredSharedSecret, $tokenSecret)) {
                throw new RuntimeException('JWT Shared Secret ist falsch oder fehlt.');
            }
        }

        // ✅ Wenn wir bis hier sind: JWT ist gültig (Signatur + optionale Zusatzprüfung)
        $jwtAllowed = true;

        $tokenUserId   = isset($payload['user_id']) ? (string)$payload['user_id'] : '';
        $tokenUserName = isset($payload['user_name']) ? (string)$payload['user_name'] : '';
        $tokenUserPass = isset($payload['user_pass']) ? (string)$payload['user_pass'] : '';

        // Optional: standard claim "sub" als user_id akzeptieren
        if ($tokenUserId === '' && isset($payload['sub'])) {
            $tokenUserId = (string)$payload['sub'];
        }

        if ($tokenUserId !== '') {
            $userId = $tokenUserId;
        }
        if ($tokenUserName !== '') {
            $user = $tokenUserName;
        }
        if ($tokenUserPass !== '') {
            $pass = $tokenUserPass;
        }

        if ($userId === '' && $user === '') {
            throw new RuntimeException('JWT enthält weder user_id/sub noch user_name.');
        }
    } catch (\Throwable $e) {
        // Wichtig: echte Ursache ins Server-Log (nicht an den Client)
        error_log('JWT-Login fehlgeschlagen: ' . $e->getMessage());

        Notification::error('❌ Ungültiger oder abgelaufener Token.');
        header('Location: index.php?noJwtRedirect=1');
        exit;
    }
}
/**
 * Token-Login:
 * Erwartet Request-Parameter `token=...`
 * token = base64(json_encode(['user_id'=>..., 'user_name'=>..., 'user_pass'=>...]))
 *
 * @var bool|null $tokenAllowed
 */
$tokenAllowed = false;
if (!empty($token) && empty($jwtAllowed)) {
    $decoded          = base64_decode((string)$token, true); // strict
    $THIRD_PARTY_PASS = defined('THIRD_PARTY_PASS') ? THIRD_PARTY_PASS : null;
    if ($decoded === false) {
        Notification::error('❌ Ungültiger Token (Base64 konnte nicht dekodiert werden).');
        header('Location: index.php');
        exit;
    }

    $payload = json_decode($decoded, true);
    if (!is_array($payload)) {
        Notification::error('❌ Ungültiger Token (JSON konnte nicht dekodiert werden).');
        header('Location: index.php');
        exit;
    }

    $tokenUserId   = isset($payload['user_id']) ? (string)$payload['user_id'] : '';
    $tokenUserName = isset($payload['user_name']) ? (string)$payload['user_name'] : '';
    $tokenUserPass = isset($payload['user_pass']) ? (string)$payload['user_pass'] : '';

    // Priorität: token überschreibt Request-Felder, aber nur wenn gesetzt
    if ($tokenUserId !== '') {
        $userId = $tokenUserId;
    }
    if ($tokenUserName !== '') {
        $user = $tokenUserName;
    }
    if ($tokenUserPass !== '') {
        $pass = $tokenUserPass;
    }

    // Mindestanforderung: Entweder userId oder user_name muss drin sein
    if ($userId === '' && $user === '') {
        Notification::error('❌ Token enthält weder user_id noch user_name.');
        header('Location: index.php');
        exit;
    } else if (!empty($pass) && !empty($THIRD_PARTY_PASS) && $pass == $THIRD_PARTY_PASS) {
        $tokenAllowed = true;
    }
}

/**
 * IP-Login:
 * Erwartet Request-Parameter `userId=...`
 *
 * @var user|null $userById
 */
if (!empty($userId)) {
    $userById = user::getUserbyFid($userId);
    $user     = $userById->name;
}

$ipAllowed = false;
if (defined('USER_ROOMS_USE') && !empty(USER_ROOMS_USE)) {
    $clientIp = getClientIp();
    // Erlaubte Netzwerke
    if (defined('USER_ROOMS') && !empty(USER_ROOMS)) {
        $lines = preg_split('/\r\n|\r|\n|,/', USER_ROOMS);
        foreach ($lines as $line) {
            if (strpos($line, '/') === false) continue;
            list($net, $mask) = explode('/', trim($line));
            if (ip_in_range($_SERVER['REMOTE_ADDR'], $net, (int)$mask)) {
                $ipAllowed = true;
                break;
            }
        }
    }
}

switch ($action) {
    case 'login':

        if ($ipAllowed || $tokenAllowed || $jwtAllowed) { // --- Falls idAllowed oder tokenAllowed ---

            if (!empty($user)) {
                $user = user::logInByIP($user);
                if ($user) {
                    Notification::info('Willkommen!');
                } else {
                    Notification::error("❌ Benutzername mit IP nicht erlaubt. Bitte kontaktieren Sie den Systemadministrator. " . $clientIp);
                }
            } else {
                Notification::error("❌ Benutzername fehlt.");
            }

        } else {

            $user = user::logIn($user, $pass);
            if ($user !== false) {
                Notification::info('Willkommen');
            } else {
                Notification::error('❌ Passwort oder Benutzername falsch! ' . $clientIp);
            }
        }
        header('Location: index.php?batch_id=' . (!empty($_REQUEST['batchId']) ? $_REQUEST['batchId'] : ''));
        exit;
        break;

    case'create':
        $user = user::createUser($name, $password, $mandant, $filiale);
        if ($user !== false) {
            Notification::info('Benutzer erfolgreich erstellt');
            header('Location: index.php');
            exit;
        } else {
            Notification::error('Fehler beim Erstellen des Benutzers');
            header('Location: index.php');
            exit;
        }
        break;

    default:
        user::logOut();
        Notification::info('Logout erfolgreich');
        header('Location: index.php');
        break;
}

