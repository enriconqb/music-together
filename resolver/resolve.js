const { fetchYouTubePlaylistEntries } = require('./fetchYouTubePlaylist');
const { ytDlpJson } = require('./ytDlpJson');

const YTDLP_FLAGS = {
  dumpSingleJson: true,
  noWarnings: true,
  skipDownload: true,
};

function canonicalYouTubeUrl(url) {
  try {
    const parsed = new URL(url.trim());
    const host = parsed.hostname.replace(/^www\./, '').toLowerCase();
    if (host !== 'music.youtube.com' && host !== 'm.youtube.com') return url.trim();
    const listId = parsed.searchParams.get('list');
    const videoId = parsed.searchParams.get('v');
    if (listId && !listId.startsWith('RD') && !listId.startsWith('LL') && (parsed.pathname === '/playlist' || listId.startsWith('PL'))) {
      return `https://www.youtube.com/playlist?list=${encodeURIComponent(listId)}`;
    }
    if (videoId) return `https://www.youtube.com/watch?v=${encodeURIComponent(videoId)}`;
    return url.trim();
  } catch {
    return url.trim();
  }
}

function isDirectUrl(query) {
  return /^https?:\/\//i.test(query.trim());
}

function isYouTubeUrl(url) {
  try {
    const parsed = new URL(url.trim());
    const host = parsed.hostname.replace(/^www\./, '').toLowerCase();
    return host === 'youtube.com'
      || host === 'm.youtube.com'
      || host === 'music.youtube.com'
      || host === 'youtu.be';
  } catch {
    return false;
  }
}

function isYouTubePlaylistUrl(url) {
  if (!isYouTubeUrl(url)) return false;
  try {
    const parsed = new URL(url.trim());
    const listId = parsed.searchParams.get('list');
    if (!listId || listId.startsWith('RD') || listId.startsWith('LL')) return false;
    if (parsed.pathname === '/playlist') return true;
    if (parsed.pathname === '/watch' && listId.startsWith('PL')) return true;
    return false;
  } catch {
    return false;
  }
}

function formatDuration(seconds) {
  const total = Number(seconds);
  if (!Number.isFinite(total) || total < 0) return 0;
  return Math.floor(total);
}

function videoIdFromEntry(entry) {
  if (entry?.id && /^[\w-]{11}$/.test(String(entry.id))) return String(entry.id);
  const raw = entry?.url || entry?.webpage_url || '';
  const match = String(raw).match(/(?:v=|youtu\.be\/|shorts\/|live\/)([\w-]{11})/);
  return match ? match[1] : null;
}

function mapEntry(entry) {
  const videoId = videoIdFromEntry(entry);
  if (!videoId) return null;
  return {
    videoId,
    title: entry.title || 'YouTube Video',
    duration: formatDuration(entry.duration),
    artist: entry.uploader || entry.channel || 'YouTube',
    live: entry.is_live === true || entry.live_status === 'is_live',
  };
}

async function resolve(query) {
  const trimmed = canonicalYouTubeUrl(String(query || '').trim());
  if (!trimmed) throw new Error('Query kosong.');

  if (isDirectUrl(trimmed) && isYouTubePlaylistUrl(trimmed)) {
    const { info, entries } = await fetchYouTubePlaylistEntries(trimmed);
    const mapped = entries.map(mapEntry).filter(Boolean);
    if (!mapped.length) throw new Error('Playlist kosong atau tidak dapat diakses.');
    return {
      type: 'playlist',
      title: info.title || 'Playlist YouTube',
      entries: mapped,
    };
  }

  if (isDirectUrl(trimmed) && isYouTubeUrl(trimmed)) {
    const info = await ytDlpJson(trimmed, { ...YTDLP_FLAGS, noPlaylist: true, simulate: true });
    const mapped = mapEntry(info);
    if (!mapped) throw new Error('Video YouTube tidak valid.');
    if (/\/live\//i.test(trimmed)) mapped.live = true;
    return { type: 'video', title: mapped.title, entries: [mapped] };
  }

  if (isDirectUrl(trimmed)) {
    throw new Error('Bukan tautan YouTube.');
  }

  const info = await ytDlpJson(`ytsearch1:${trimmed}`, { ...YTDLP_FLAGS, flatPlaylist: true, simulate: true });
  const first = Array.isArray(info.entries) ? info.entries[0] : info;
  const mapped = mapEntry(first);
  if (!mapped) throw new Error(`Tidak ada hasil YouTube untuk: "${trimmed}"`);
  return { type: 'search', title: mapped.title, entries: [mapped] };
}

const query = process.argv.slice(2).join(' ').trim();

resolve(query)
  .then((result) => {
    console.error(`[Playlist] selesai type=${result.type} jumlah=${(result.entries || []).length}`);
    process.stdout.write(JSON.stringify(result));
  })
  .catch((error) => {
    process.stderr.write(error.message || String(error));
    process.exit(1);
  });
