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
$fallback['debug'] = [
    'source' => 'php',
    'count' => count($fallback['songs'] ?? []),
    'log' => substr(trim((string)($fromNode['error'] ?? 'Node tidak dipakai atau gagal sebelum ada pesan.')), -2000),
];
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
    @set_time_limit(0);
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
    $result = songsResponse($decoded['entries'], (string)($decoded['type'] ?? 'video'), (string)($decoded['title'] ?? ''));
    $result['debug'] = [
        'source' => 'node',
        'count' => count($result['songs'] ?? []),
        'log' => substr(trim((string)$stderr), -2000),
    ];
    return $result;
}

function resolveYouTubeFallback(string $query): array
{
    $playlistId = youtubePlaylistId($query);
    $isPlaylist = $playlistId !== null
        && !preg_match('/^(RD|LL)/', $playlistId)
        && (str_contains($query, '/playlist') || str_contains($query, 'music.youtube.com') || str_starts_with($playlistId, 'PL'));
    if ($isPlaylist) {
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
    $songs = youtubePlaylistHtml($playlistId);
    if ($songs) {
        return $songs;
    }
    return youtubePlaylistRss($playlistId);
}

function youtubePlaylistRss(string $playlistId): array
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
    }
    return $songs;
}

function youtubePlaylistHtml(string $playlistId): array
{
    $html = httpText(
        'https://www.youtube.com/playlist?list=' . rawurlencode($playlistId),
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36'
    );
    if ($html === '') {
        return [];
    }
    $songs = youtubePlaylistRenderers($html);
    if (!$songs) {
        $songs = [];
        $seen = [];
        $offset = 0;
        $marker = '"lockupMetadataViewModel":{"title":{"content":"';
        while (($pos = strpos($html, $marker, $offset)) !== false) {
        $valueStart = $pos + strlen($marker);
        $titleRaw = '';
        $length = strlen($html);
        for ($i = $valueStart; $i < $length; $i++) {
            $char = $html[$i];
            if ($char === '\\') {
                $titleRaw .= $char . ($html[$i + 1] ?? '');
                $i++;
                continue;
            }
            if ($char === '"') {
                break;
            }
            $titleRaw .= $char;
        }
        $start = max(0, $pos - 12000);
        $window = substr($html, $start, $pos - $start);
        $offset = $valueStart;
        if (!preg_match_all('#/vi/([\w-]{11})/#', $window, $ids) || !$ids[1]) {
            continue;
        }
        $videoId = $ids[1][count($ids[1]) - 1];
        if (isset($seen[$videoId])) {
            continue;
        }
        $seen[$videoId] = true;
        $decoded = json_decode('"' . $titleRaw . '"');
        $songs[] = [
            'videoId' => $videoId,
            'title' => is_string($decoded) && $decoded !== '' ? $decoded : 'YouTube Video',
            'artist' => 'YouTube',
            'duration' => 0,
        ];
        }
    }
    return youtubeContinuePlaylist($html, $songs);
}

function youtubeContinuePlaylist(string $html, array $songs): array
{
    if (!preg_match('/"INNERTUBE_API_KEY":"([^"]+)"/', $html, $keyMatch)) {
        return $songs;
    }
    $version = '2.20240101.00.00';
    if (preg_match('/"INNERTUBE_CLIENT_VERSION":"([^"]+)"/', $html, $versionMatch)) {
        $version = $versionMatch[1];
    }
    $token = youtubeContinuationToken($html);
    $pages = 0;
    while ($token && $pages < 30) {
        $body = json_encode([
            'context' => ['client' => ['clientName' => 'WEB', 'clientVersion' => $version, 'hl' => 'en', 'gl' => 'US']],
            'continuation' => $token,
        ]);
        $raw = httpPostJson('https://www.youtube.com/youtubei/v1/browse?key=' . rawurlencode($keyMatch[1]), $body);
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            break;
        }
        $songs = youtubeMergePlaylistNodes($data, $songs);
        $token = youtubeContinuationToken($raw);
        $pages++;
    }
    return $songs;
}

function youtubeContinuationToken(string $raw): ?string
{
    if (!preg_match_all('/"token":"([A-Za-z0-9_\-]{80,})"/', $raw, $matches) || !$matches[1]) {
        return null;
    }
    return $matches[1][count($matches[1]) - 1];
}

function youtubeMergePlaylistNodes(array $node, array $songs): array
{
    $seen = [];
    foreach ($songs as $song) {
        $seen[$song['videoId']] = true;
    }
    $walker = function ($value) use (&$walker, &$songs, &$seen) {
        if (!is_array($value)) {
            return;
        }
        $videoId = '';
        $title = 'YouTube Video';
        if (!empty($value['playlistVideoRenderer']['videoId'])) {
            $videoId = (string)$value['playlistVideoRenderer']['videoId'];
            $title = (string)($value['playlistVideoRenderer']['title']['runs'][0]['text'] ?? $title);
        } elseif (!empty($value['lockupViewModel']['contentId']) && strlen((string)$value['lockupViewModel']['contentId']) === 11) {
            $videoId = (string)$value['lockupViewModel']['contentId'];
            $title = (string)($value['lockupViewModel']['metadata']['lockupMetadataViewModel']['title']['content'] ?? $title);
        }
        if ($videoId !== '' && !isset($seen[$videoId])) {
            $seen[$videoId] = true;
            $songs[] = ['videoId' => $videoId, 'title' => $title, 'artist' => 'YouTube', 'duration' => 0];
        }
        foreach ($value as $child) {
            if (is_array($child)) {
                $walker($child);
            }
        }
    };
    $walker($node);
    return $songs;
}

function httpPostJson(string $url, string $body): string
{
    if (!function_exists('curl_init')) {
        return '';
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
    ]);
    $raw = curl_exec($ch);
    curl_close($ch);
    return is_string($raw) ? $raw : '';
}

function youtubePlaylistRenderers(string $html): array
{
    if (!preg_match_all('/"playlistVideoRenderer":\{"videoId":"([\w-]{11})"/', $html, $matches, PREG_OFFSET_CAPTURE)) {
        return [];
    }
    $songs = [];
    $seen = [];
    foreach ($matches[1] as $hit) {
        $videoId = $hit[0];
        if (isset($seen[$videoId])) {
            continue;
        }
        $seen[$videoId] = true;
        $slice = substr($html, (int)$hit[1], 900);
        $title = 'YouTube Video';
        if (preg_match('/"title":\{"runs":\[\{"text":"((?:\\\\.|[^"\\\\])*)"/', $slice, $titleMatch)) {
            $decoded = json_decode('"' . $titleMatch[1] . '"');
            if (is_string($decoded) && $decoded !== '') {
                $title = $decoded;
            }
        }
        $songs[] = [
            'videoId' => $videoId,
            'title' => $title,
            'artist' => 'YouTube',
            'duration' => 0,
        ];
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

function httpText(string $url, string $userAgent = 'SyncTunes'): string
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => $userAgent,
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
