<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$query = trim((string)($_POST['q'] ?? $_GET['q'] ?? ''));
if ($query === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Tautan atau kata kunci kosong.']);
    exit;
}

if (isSpotify($query)) {
    echo json_encode(resolveSpotify($query));
    exit;
}

$node = getenv('NODE_PATH_BIN') ?: 'node';
$script = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'resolver' . DIRECTORY_SEPARATOR . 'resolve.js';
$cmd = escapeshellarg($node) . ' ' . escapeshellarg($script) . ' ' . escapeshellarg($query);

$descriptors = [
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w'],
];

$process = proc_open($cmd, $descriptors, $pipes, dirname(__DIR__));
if (!is_resource($process)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Gagal menjalankan resolver.']);
    exit;
}

$stdout = stream_get_contents($pipes[1]);
$stderr = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
$code = proc_close($process);

if ($code !== 0) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => trim($stderr) ?: 'Gagal membaca tautan YouTube.']);
    exit;
}

$decoded = json_decode($stdout, true);
if (!is_array($decoded) || empty($decoded['entries'])) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Resolver tidak mengembalikan lagu.']);
    exit;
}

$songs = [];
foreach ($decoded['entries'] as $entry) {
    if (empty($entry['videoId'])) {
        continue;
    }
    $songs[] = [
        'source' => 'youtube',
        'videoId' => $entry['videoId'],
        'title' => $entry['title'] ?? 'YouTube Video',
        'artist' => $entry['artist'] ?? 'YouTube',
        'duration' => (int)($entry['duration'] ?? 0),
    ];
}

echo json_encode([
    'ok' => true,
    'type' => $decoded['type'] ?? 'video',
    'title' => $decoded['title'] ?? '',
    'songs' => $songs,
]);

function isSpotify(string $query): bool
{
    return (bool)preg_match('#(?:open\.spotify\.com|spotify:)#i', $query);
}

function resolveSpotify(string $query): array
{
    if (!preg_match('#spotify\.com/(track|album|playlist|episode)/([a-zA-Z0-9]+)#', $query, $match)
        && !preg_match('#spotify:(track|album|playlist|episode):([a-zA-Z0-9]+)#', $query, $match)) {
        http_response_code(422);
        return ['ok' => false, 'error' => 'Tautan Spotify tidak dikenali.'];
    }

    $kind = $match[1];
    $id = $match[2];
    $url = "https://open.spotify.com/{$kind}/{$id}";
    $meta = spotifyOembed($url);

    $song = [
        'source' => 'spotify',
        'spotifyType' => $kind,
        'spotifyId' => $id,
        'title' => $meta['title'] ?? ucfirst($kind) . ' Spotify',
        'artist' => $meta['author_name'] ?? 'Spotify',
        'duration' => 0,
    ];

    return [
        'ok' => true,
        'type' => 'spotify',
        'title' => $song['title'],
        'songs' => [$song],
    ];
}

function spotifyOembed(string $url): array
{
    $endpoint = 'https://open.spotify.com/oembed?url=' . rawurlencode($url);
    if (!function_exists('curl_init')) {
        return [];
    }
    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_USERAGENT => 'SyncTunes',
    ]);
    $body = curl_exec($ch);
    curl_close($ch);
    if (!is_string($body) || $body === '') {
        return [];
    }
    $json = json_decode($body, true);
    return is_array($json) ? $json : [];
}
