const { fetchPlaylistViaInnertube } = require('./fetchPlaylistInnertube');
const { ytDlpJson } = require('./ytDlpJson');
const { getYtDlpAuthFlags } = require('./ytDlpAuth');

const CHUNK_SIZE = 100;

function isValidPlaylistEntry(entry) {
  return Boolean(entry && (entry.id || entry.url || entry.webpage_url));
}

function entryKey(entry) {
  if (entry?.id != null) return String(entry.id);
  return entry?.url || entry?.webpage_url || null;
}

function dedupeEntries(entries) {
  const seen = new Set();
  const result = [];

  for (const entry of entries) {
    const key = entryKey(entry);
    if (!key || seen.has(key)) continue;
    seen.add(key);
    result.push(entry);
  }

  return result;
}

function filterValidEntries(entries) {
  if (!Array.isArray(entries)) return [];
  return entries.filter(isValidPlaylistEntry);
}

function resolvePlaylistTotal(info, entries) {
  const count = info?.playlist_count ?? info?.n_entries;
  if (Number.isFinite(count) && count > 0) return count;
  return entries.length;
}

function buildPlaylistFlags(extra = {}) {
  return {
    dumpSingleJson: true,
    noWarnings: true,
    skipDownload: true,
    flatPlaylist: true,
    simulate: true,
    lazyPlaylist: false,
    ignoreErrors: true,
    ...getYtDlpAuthFlags(),
    ...extra,
  };
}

async function fetchPlaylistChunk(url, playlistItems) {
  const page = await ytDlpJson(url, buildPlaylistFlags({ playlistItems }));
  return filterValidEntries(page.entries);
}

async function fetchYouTubePlaylistEntries(url) {
  const first = await ytDlpJson(url, buildPlaylistFlags());
  let entries = filterValidEntries(first.entries);
  const total = resolvePlaylistTotal(first, entries);
  const ytdlpCount = entries.length;

  if (entries.length < total) {
    for (let start = entries.length + 1; start <= total; start += CHUNK_SIZE) {
      const end = Math.min(start + CHUNK_SIZE - 1, total);
      const pageEntries = await fetchPlaylistChunk(url, `${start}-${end}`);

      if (!pageEntries.length) continue;

      entries = dedupeEntries(entries.concat(pageEntries));
      if (entries.length >= total) break;
    }
  }

  if (entries.length < total) {
    try {
      const innertube = await fetchPlaylistViaInnertube(url);
      entries = dedupeEntries(entries.concat(innertube.entries));
      console.log(
        `[Playlist] yt-dlp: ${ytdlpCount}, innertube: ${innertube.entries.length}, `
        + `gabungan: ${entries.length}, metadata: ${total}`,
      );
    } catch (error) {
      console.warn(`[Playlist] Fallback InnerTube gagal: ${error.message}`);
    }
  }

  return {
    info: first,
    entries: dedupeEntries(entries),
    playlistTotal: total,
  };
}

module.exports = {
  fetchYouTubePlaylistEntries,
  filterValidEntries,
};
