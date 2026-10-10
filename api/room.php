<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$raw = file_get_contents('php://input');
$input = json_decode($raw ?: '', true);
if (!is_array($input)) {
    $input = $_POST;
}

$action = (string)($input['action'] ?? '');
$dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'rooms';
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}

try {
    switch ($action) {
        case 'list':
            echo json_encode(['ok' => true, 'rooms' => listRooms($dir)]);
            break;
        case 'create':
            echo json_encode(createRoom($dir, $input));
            break;
        case 'join':
            echo json_encode(joinRoom($dir, $input));
            break;
        case 'state':
            echo json_encode(readState($dir, $input));
            break;
        case 'add':
            echo json_encode(mutate($dir, $input, 'add'));
            break;
        case 'remove':
            echo json_encode(mutate($dir, $input, 'remove'));
            break;
        case 'reorder':
            echo json_encode(mutate($dir, $input, 'reorder'));
            break;
        case 'play':
            echo json_encode(mutate($dir, $input, 'play'));
            break;
        case 'clear':
            echo json_encode(mutate($dir, $input, 'clear'));
            break;
        case 'chat':
            echo json_encode(mutate($dir, $input, 'chat'));
            break;
        case 'leave':
            echo json_encode(mutate($dir, $input, 'leave'));
            break;
        default:
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Aksi tidak dikenal.']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}

function roomPath(string $dir, string $code): string
{
    if (!preg_match('/^[A-Z0-9]{6}$/', $code)) {
        throw new InvalidArgumentException('Kode room tidak valid.');
    }
    return $dir . DIRECTORY_SEPARATOR . $code . '.json';
}

function withRoom(string $path, callable $fn, bool $create = false): array
{
    $fh = fopen($path, $create ? 'c+' : 'r+');
    if ($fh === false) {
        throw new RuntimeException('Room tidak ditemukan.');
    }
    if (!flock($fh, LOCK_EX)) {
        fclose($fh);
        throw new RuntimeException('Room sedang sibuk.');
    }
    $raw = stream_get_contents($fh);
    $data = json_decode($raw ?: '', true);
    if (!is_array($data)) {
        if (!$create) {
            flock($fh, LOCK_UN);
            fclose($fh);
            throw new RuntimeException('Room tidak ditemukan.');
        }
        $data = [];
    }
    try {
        $data = $fn($data);
    } catch (Throwable $e) {
        flock($fh, LOCK_UN);
        fclose($fh);
        throw $e;
    }
    rewind($fh);
    ftruncate($fh, 0);
    fwrite($fh, json_encode($data, JSON_UNESCAPED_UNICODE));
    fflush($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
    return $data;
}

function publicRoom(array $room, bool $includeChat = true): array
{
    $now = (int)(microtime(true) * 1000);
    $users = [];
    foreach ($room['users'] ?? [] as $user) {
        if (($now - (int)($user['lastSeen'] ?? 0)) < 20000) {
            $users[] = ['id' => $user['id'], 'name' => $user['name']];
        }
    }
    $out = [
        'code' => $room['code'],
        'createdBy' => $room['createdBy'],
        'createdAt' => $room['createdAt'],
        'isPrivate' => !empty($room['passwordHash']),
        'queue' => array_values($room['queue'] ?? []),
        'currentIndex' => (int)($room['currentIndex'] ?? -1),
        'isPlaying' => !empty($room['isPlaying']),
        'revision' => (int)($room['revision'] ?? 0),
        'playNonce' => (int)($room['playNonce'] ?? 0),
        'online' => $users,
    ];
    if ($includeChat) {
        $out['chat'] = array_values($room['chat'] ?? []);
    }
    return $out;
}

function touchUser(array &$room, array $input): void
{
    $id = substr((string)($input['userId'] ?? ''), 0, 40);
    $name = substr(trim((string)($input['userName'] ?? 'Guest')), 0, 20) ?: 'Guest';
    if ($id === '') {
        return;
    }
    $room['users'][$id] = [
        'id' => $id,
        'name' => $name,
        'lastSeen' => (int)(microtime(true) * 1000),
    ];
}

function listRooms(string $dir): array
{
    $rooms = [];
    foreach (glob($dir . DIRECTORY_SEPARATOR . '*.json') ?: [] as $file) {
        $data = json_decode((string)file_get_contents($file), true);
        if (!is_array($data) || empty($data['code'])) {
            continue;
        }
        $pub = publicRoom($data, false);
        $rooms[] = [
            'code' => $pub['code'],
            'createdBy' => $pub['createdBy'],
            'createdAt' => $pub['createdAt'],
            'isPrivate' => $pub['isPrivate'],
            'online' => count($pub['online']),
        ];
    }
    usort($rooms, fn($a, $b) => ($b['createdAt'] <=> $a['createdAt']));
    return $rooms;
}

function createRoom(string $dir, array $input): array
{
    $name = substr(trim((string)($input['userName'] ?? 'Host')), 0, 20) ?: 'Host';
    $password = trim((string)($input['password'] ?? ''));
    $code = '';
    for ($i = 0; $i < 8; $i++) {
        $code = randomCode();
        if (!file_exists(roomPath($dir, $code))) {
            break;
        }
    }
    $path = roomPath($dir, $code);
    $room = [
        'code' => $code,
        'createdBy' => $name,
        'createdAt' => (int)(microtime(true) * 1000),
        'passwordHash' => $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : null,
        'queue' => [],
        'currentIndex' => -1,
        'isPlaying' => false,
        'revision' => 1,
        'chat' => [],
        'users' => [],
    ];
    $input['userName'] = $name;
    touchUser($room, $input);
    file_put_contents($path, json_encode($room, JSON_UNESCAPED_UNICODE), LOCK_EX);
    return ['ok' => true, 'room' => publicRoom($room)];
}

function joinRoom(string $dir, array $input): array
{
    $code = strtoupper(trim((string)($input['code'] ?? '')));
    $path = roomPath($dir, $code);
    if (!file_exists($path)) {
        http_response_code(404);
        return ['ok' => false, 'error' => 'Room tidak ditemukan.'];
    }
    try {
        $room = withRoom($path, function (array $room) use ($input) {
            $hash = $room['passwordHash'] ?? null;
            if ($hash) {
                $password = (string)($input['password'] ?? '');
                if (!password_verify($password, $hash)) {
                    throw new RuntimeException('Password salah.');
                }
            }
            touchUser($room, $input);
            $room['revision'] = (int)($room['revision'] ?? 0) + 1;
            return $room;
        });
    } catch (RuntimeException $e) {
        http_response_code(403);
        return ['ok' => false, 'error' => $e->getMessage()];
    }
    return ['ok' => true, 'room' => publicRoom($room)];
}

function readState(string $dir, array $input): array
{
    $code = strtoupper(trim((string)($input['code'] ?? '')));
    $path = roomPath($dir, $code);
    if (!file_exists($path)) {
        http_response_code(404);
        return ['ok' => false, 'error' => 'Room tidak ditemukan.'];
    }
    $since = (int)($input['sinceChat'] ?? 0);
    $room = withRoom($path, function (array $room) use ($input) {
        touchUser($room, $input);
        return $room;
    });
    $pub = publicRoom($room);
    $pub['chat'] = array_values(array_filter($pub['chat'], fn($msg) => (int)($msg['time'] ?? 0) > $since));
    return ['ok' => true, 'room' => $pub];
}

function mutate(string $dir, array $input, string $op): array
{
    $code = strtoupper(trim((string)($input['code'] ?? '')));
    $path = roomPath($dir, $code);
    if (!file_exists($path)) {
        http_response_code(404);
        return ['ok' => false, 'error' => 'Room tidak ditemukan.'];
    }
    try {
        $room = withRoom($path, function (array $room) use ($input, $op) {
            touchUser($room, $input);
            $room['revision'] = (int)($room['revision'] ?? 0) + 1;
            $queue = array_values($room['queue'] ?? []);

            if ($op === 'add') {
                foreach ($input['songs'] ?? [] as $song) {
                    if (!is_array($song)) {
                        continue;
                    }
                    $queue[] = normalizeSong($song, (string)($input['userName'] ?? 'Guest'));
                }
                $room['queue'] = $queue;
                if ((int)$room['currentIndex'] < 0 && count($queue) > 0) {
                    $room['currentIndex'] = 0;
                    $room['isPlaying'] = true;
                    $room['playNonce'] = (int)($room['playNonce'] ?? 0) + 1;
                }
            } elseif ($op === 'remove') {
                $id = (string)($input['songId'] ?? '');
                $index = (int)$room['currentIndex'];
                $removedIndex = null;
                $next = [];
                foreach ($queue as $i => $song) {
                    if (($song['id'] ?? '') === $id) {
                        $removedIndex = $i;
                        continue;
                    }
                    $next[] = $song;
                }
                if ($removedIndex !== null && $removedIndex < $index) {
                    $index--;
                } elseif ($removedIndex === $index) {
                    $index = count($next) === 0 ? -1 : min($index, count($next) - 1);
                }
                $room['queue'] = $next;
                $room['currentIndex'] = $index;
            } elseif ($op === 'reorder') {
                $ids = array_values(array_filter(array_map('strval', $input['order'] ?? [])));
                $byId = [];
                foreach ($queue as $song) {
                    $byId[$song['id']] = $song;
                }
                $next = [];
                foreach ($ids as $id) {
                    if (isset($byId[$id])) {
                        $next[] = $byId[$id];
                    }
                }
                $room['queue'] = $next;
            } elseif ($op === 'play') {
                $index = (int)($input['index'] ?? -1);
                if ($index >= 0 && $index < count($queue)) {
                    $room['currentIndex'] = $index;
                    $room['isPlaying'] = true;
                    $room['playNonce'] = (int)($room['playNonce'] ?? 0) + 1;
                }
            } elseif ($op === 'clear') {
                $room['queue'] = [];
                $room['currentIndex'] = -1;
                $room['isPlaying'] = false;
            } elseif ($op === 'chat') {
                $text = substr(trim((string)($input['text'] ?? '')), 0, 200);
                if ($text !== '') {
                    $chat = $room['chat'] ?? [];
                    $chat[] = [
                        'id' => bin2hex(random_bytes(4)),
                        'user' => substr(trim((string)($input['userName'] ?? 'Guest')), 0, 20) ?: 'Guest',
                        'text' => $text,
                        'time' => (int)(microtime(true) * 1000),
                    ];
                    $room['chat'] = array_slice($chat, -50);
                }
            } elseif ($op === 'leave') {
                $id = (string)($input['userId'] ?? '');
                unset($room['users'][$id]);
            }
            return $room;
        });
    } catch (RuntimeException $e) {
        http_response_code(403);
        return ['ok' => false, 'error' => $e->getMessage()];
    }
    $pub = publicRoom($room, $op === 'chat' || $op === 'state');
    if ($op !== 'chat') {
        unset($pub['chat']);
    }
    return ['ok' => true, 'room' => $pub];
}

function normalizeSong(array $song, string $addedBy): array
{
    $source = ($song['source'] ?? '') === 'spotify' ? 'spotify' : 'youtube';
    $item = [
        'id' => bin2hex(random_bytes(6)),
        'source' => $source,
        'title' => substr((string)($song['title'] ?? 'Untitled'), 0, 180),
        'artist' => substr((string)($song['artist'] ?? ''), 0, 120),
        'duration' => (int)($song['duration'] ?? 0),
        'addedBy' => substr($addedBy, 0, 20) ?: 'Guest',
        'addedAt' => (int)(microtime(true) * 1000),
    ];
    if ($source === 'youtube') {
        $item['videoId'] = substr((string)($song['videoId'] ?? ''), 0, 16);
    } else {
        $item['spotifyType'] = substr((string)($song['spotifyType'] ?? 'track'), 0, 16);
        $item['spotifyId'] = substr((string)($song['spotifyId'] ?? ''), 0, 32);
    }
    return $item;
}

function randomCode(): string
{
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';
    for ($i = 0; $i < 6; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $code;
}
