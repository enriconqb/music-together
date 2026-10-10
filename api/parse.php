<?php
declare(strict_types=1);

require __DIR__ . '/json_guard.php';

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

$fromNode = procOpenAllowed() ? resolveWithNode($query) : null;
if (is_array($fromNode) && !empty($fromNode['ok'])) {
    echo json_encode($fromNode);
    exit;
}

$fallback = resolveYouTubeFallback($query);
if (empty($fallback['ok']) && is_array($fromNode) && !empty($fromNode['error'])) {
    $fallback['error'] = $fromNode['error'];
}
if (empty($fallback['ok'])) {
    http_response_code(422);
}
echo json_encode($fallback);

function procOpenAllowed(): bool
{
    if (!function_exists('proc_open')) {
        return false;
    }
    $disabled = array_map('trim', explode(',', (string)ini_get('disable_functions')));
    return !in_array('proc_open', $disabled, true);
}

function resolveWithNode(string $query): ?array
{
    $node = getenv('NODE_PATH_BIN') ?: 'node';
    $script = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'resolver' . DIRECTORY_SEPARATOR . 'resolve.js';
    $cmd = escapeshellarg($node) . ' ' . escapeshellarg($script) . ' ' . escapeshellarg($query);
    $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $pipes = [];
    $process = @proc_open($cmd, $descriptors, $pipes, dirname(__DIR__));
    if (!is_resource($process)) {
        return null;
    }
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $code = proc_close($process);
    if ($code !== 0) {
        return ['ok' => false, 'error' => trim((string)$stderr) ?: 'Gagal membaca tautan YouTube.'];
    }
    $decoded = json_decode((string)$stdout, true);
    if (!is_array($decoded) || empty($decoded['entries'])) {
        return ['ok' => false, 'error' => 'Resolver tidak mengembalikan lagu.'];
    }
    return songsResponse($decoded['entries'], (string)($decoded['type'] ?? 'video'), (string)($decoded['title'] ?? ''));
}

function resolveYouTubeFallback(string $query): array
{
    $playlistId = youtubePlaylistId($query);
    if ($playlistId !== null && (strpos($query, '/playlist') !== false || !preg_match('#/(watch|shorts|embed)/#', $query))) {
        $songs = youtubePlaylistFeed($playlistId);
        if ($songs) {
            return songsResponse($songs, 'playlist', 'Playlist YouTube');
        }
        return ['ok' => false, 'error' => 'Playlist YouTube tidak bisa dibaca.'];
    }

    $videoId = youtubeVideoId($query);
    if ($videoId === null && !preg_match('#^https?://#i', $query)) {
        return ['ok' => false, 'error' => 'Pencarian YouTube butuh Node di server. Tempel tautan video atau playlist.'];
    }
    if ($videoId === null) {
        return ['ok' => false, 'error' => 'Tautan YouTube tidak dikenali.'];
    }
    $meta = httpJson('https://www.youtube.com/oembed?format=json&url=' . rawurlencode('https://www.youtube.com/watch?v=' . $videoId));
    return songsResponse([[
        'videoId' => $videoId,
        'title' => $meta['title'] ?? 'YouTube Video',
        'artist' => $meta['author_name'] ?? 'YouTube',
        'duration' => 0,
        'live' => (bool)preg_match('#/live/#', $query),
    ]], 'video', (string)($meta['title'] ?? ''));
}

function youtubePlaylistFeed(string $playlistId): array
{
    $xml = httpText('https://www.youtube.com/feeds/videos.xml?playlist_id=' . rawurlencode($playlistId));
    if ($xml === '') {
        return [];
    }
    $feed = @simplexml_load_string($xml);
    if (!$feed) {
        return [];
    }
    $songs = [];
    foreach ($feed->entry as $entry) {
        $videoId = '';
        $yt = $entry->children('http://www.youtube.com/xml/schemas/2015');
        if (isset($yt->videoId)) {
            $videoId = (string)$yt->videoId;
        }
        if ($videoId === '') {
            continue;
        }
        $media = $entry->children('http://search.yahoo.com/mrss/');
        $artist = isset($media->group->credit) ? (string)$media->group->credit : 'YouTube';
        $songs[] = [
            'videoId' => $videoId,
            'title' => (string)$entry->title,
            'artist' => $artist,
            'duration' => 0,
        ];
        if (count($songs) >= 100) {
            break;
        }
    }
    return $songs;
}

function songsResponse(array $entries, string $type, string $title): array
{
    $songs = [];
    foreach ($entries as $entry) {
        if (empty($entry['videoId'])) {
            continue;
        }
        $songs[] = [
            'source' => 'youtube',
            'videoId' => $entry['videoId'],
            'title' => $entry['title'] ?? 'YouTube Video',
            'artist' => $entry['artist'] ?? 'YouTube',
            'duration' => (int)($entry['duration'] ?? 0),
            'live' => !empty($entry['live']),
        ];
    }
    if (!$songs) {
        return ['ok' => false, 'error' => 'Tidak ada lagu yang bisa ditambahkan.'];
    }
    return ['ok' => true, 'type' => $type, 'title' => $title, 'songs' => $songs];
}

function youtubeVideoId(string $query): ?string
{
    if (preg_match('#(?:v=|youtu\.be/|shorts/|embed/|live/)([\w-]{11})#', $query, $match)) {
        return $match[1];
    }
    if (preg_match('#^([\w-]{11})$#', $query, $match)) {
        return $match[1];
    }
    return null;
}

function youtubePlaylistId(string $query): ?string
{
    if (preg_match('#[?&]list=([\w-]+)#', $query, $match)) {
        return $match[1];
    }
    return null;
}

function httpJson(string $url): array
{
    $body = httpText($url);
    $json = json_decode($body, true);
    return is_array($json) ? $json : [];
}

function httpText(string $url): string
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_USERAGENT => 'SyncTunes',
        ]);
        $body = curl_exec($ch);
        curl_close($ch);
        return is_string($body) ? $body : '';
    }
    $body = @file_get_contents($url);
    return is_string($body) ? $body : '';
}

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
