<?php
header('Content-Type: application/json; charset=utf-8');

$url  = $_POST['url'] ?? '';
$user = $_POST['auth_user'] ?? '';
$pass = $_POST['auth_pass'] ?? '';

if (empty($url)) {
    echo json_encode(['error' => 'Keine URL angegeben.']);
    exit;
}

// ✅ Protokoll-Check: Wenn kein http/https vorhanden ist, http:// voranstellen
if (!preg_match('~^https?://~i', $url)) {
    $url = 'http://' . $url;
}

// ✅ Port-Fix für cURL (besonders unter Windows/IIS nötig)
$parsedUrl = parse_url($url);
$port = $parsedUrl['port'] ?? null;

// Wenn ein Port gefunden wurde, entfernen wir ihn aus der Haupt-URL für curl_init
// aber wir behalten den Rest bei.
if ($port) {
    // Rekonstruiere URL ohne Port im Host-Teil
    $cleanUrl = ($parsedUrl['scheme'] ?? 'http') . '://' . $parsedUrl['host'];
    if (!empty($parsedUrl['path'])) $cleanUrl .= $parsedUrl['path'];
    if (!empty($parsedUrl['query'])) $cleanUrl .= '?' . $parsedUrl['query'];

    $ch = curl_init($cleanUrl);
    curl_setopt($ch, CURLOPT_PORT, intval($port));
} else {
    $ch = curl_init($url);
}

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_NOBODY         => false,
    CURLOPT_HEADER         => true,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => 0,
    CURLOPT_USERAGENT      => "EndpointChecker/1.0",
]);

if ($user && $pass) {
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, "$user:$pass");
}

$response = curl_exec($ch);
$info     = curl_getinfo($ch);
$error    = curl_error($ch);
$errno    = curl_errno($ch);

curl_close($ch);

if ($errno) {
    echo json_encode([
                         'success' => false,
                         'error'   => "cURL Fehler #$errno: $error"
                     ]);
    exit;
}

echo json_encode([
                     'success'     => true,
                     'status'      => $info['http_code'] ?? 0,
                     'time'        => round($info['total_time'] ?? 0, 3),
                     'url'         => $info['url'] ?? $url,
                     'ssl_version' => $info['ssl_version'] ?? '',
                     'ssl_cipher'  => $info['ssl_cipher'] ?? '',
                 ]);
