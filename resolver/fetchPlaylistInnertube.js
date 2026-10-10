const https = require('https');

const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36';

function extractPlaylistId(urlOrId) {
  const raw = String(urlOrId).trim();
  if (/^PL[\w-]+$/i.test(raw)) return raw;

  try {
    const parsed = new URL(raw);
    return parsed.searchParams.get('list');
  } catch {
    return null;
  }
}

function readText(value) {
  if (!value) return null;
  if (typeof value === 'string') return value;
  return value.simpleText
    || value.runs?.map((run) => run.text).join('')
    || value.content
    || null;
}

function readContinuationToken(node) {
  if (!node || typeof node !== 'object') return null;

  return node.continuationItemRenderer?.continuationEndpoint?.continuationCommand?.token
    || node.continuationItemViewModel?.continuationCommand?.innertubeCommand?.continuationCommand?.token
    || node.continuationCommand?.token
    || node.continuationEndpoint?.continuationCommand?.token
    || null;
}

function isPlaylistItem(item) {
  return Boolean(
    item?.playlistVideoRenderer
    || item?.lockupViewModel
    || item?.richItemRenderer?.content?.lockupViewModel,
  );
}

function mapVideoEntry(id, title, duration = 0) {
  return {
    id,
    title: title || 'Unknown',
    url: `https://www.youtube.com/watch?v=${id}`,
    webpage_url: `https://www.youtube.com/watch?v=${id}`,
    duration: Number(duration) || 0,
    extractor: 'youtube',
    ie_key: 'Youtube',
  };
}

function extractVideos(data) {
  const videos = [];
  if (!data || typeof data !== 'object') return videos;

  if (data.playlistVideoRenderer?.videoId) {
    const video = data.playlistVideoRenderer;
    videos.push(mapVideoEntry(video.videoId, readText(video.title), video.lengthSeconds));
  }

  let node = data;
  if (node.richItemRenderer?.content?.lockupViewModel) {
    node = { lockupViewModel: node.richItemRenderer.content.lockupViewModel };
  }

  if (node.lockupViewModel?.contentId) {
    const video = node.lockupViewModel;
    const id = video.contentId;
    if (id && id.length === 11) {
      videos.push(mapVideoEntry(
        id,
        readText(video.metadata?.lockupMetadataViewModel?.title),
      ));
    }
  }

  for (const value of Object.values(data)) {
    if (Array.isArray(value)) {
      for (const item of value) videos.push(...extractVideos(item));
    } else if (value && typeof value === 'object') {
      videos.push(...extractVideos(value));
    }
  }

  return videos;
}

function findPlaylistContinuation(data) {
  if (!data || typeof data !== 'object') return null;

  if (Array.isArray(data.onResponseReceivedActions)) {
    for (const action of data.onResponseReceivedActions) {
      const items = action.appendContinuationItemsAction?.continuationItems;
      if (!Array.isArray(items)) continue;

      for (let i = items.length - 1; i >= 0; i -= 1) {
        const token = readContinuationToken(items[i]);
        if (token) return token;
      }
    }
  }

  function walk(node) {
    if (!node || typeof node !== 'object') return null;

    if (Array.isArray(node.contents) && node.contents.some(isPlaylistItem)) {
      for (let i = node.contents.length - 1; i >= 0; i -= 1) {
        const token = readContinuationToken(node.contents[i]);
        if (token) return token;
      }
    }

    for (const value of Object.values(node)) {
      if (Array.isArray(value)) {
        for (const item of value) {
          const found = walk(item);
          if (found) return found;
        }
      } else if (value && typeof value === 'object') {
        const found = walk(value);
        if (found) return found;
      }
    }

    return null;
  }

  return walk(data);
}

function fetchHtml(url) {
  return new Promise((resolve, reject) => {
    https.get(url, {
      headers: {
        'User-Agent': USER_AGENT,
        'Accept-Language': 'en-US,en;q=0.9',
      },
    }, (res) => {
      let data = '';
      res.on('data', (chunk) => { data += chunk; });
      res.on('end', () => resolve(data));
    }).on('error', reject);
  });
}

function postBrowse(apiKey, clientVersion, token) {
  const body = JSON.stringify({
    context: {
      client: {
        clientName: 'WEB',
        clientVersion,
        hl: 'en',
        gl: 'US',
      },
    },
    continuation: token,
  });

  return new Promise((resolve, reject) => {
    const req = https.request({
      hostname: 'www.youtube.com',
      path: `/youtubei/v1/browse?key=${apiKey}`,
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Content-Length': Buffer.byteLength(body),
        'User-Agent': USER_AGENT,
      },
    }, (res) => {
      let data = '';
      res.on('data', (chunk) => { data += chunk; });
      res.on('end', () => {
        try {
          resolve(JSON.parse(data));
        } catch (error) {
          reject(new Error(`Gagal parse respons playlist: ${error.message}`));
        }
      });
    });

    req.on('error', reject);
    req.write(body);
    req.end();
  });
}

function dedupeEntries(entries) {
  const seen = new Set();
  const result = [];

  for (const entry of entries) {
    const key = entry?.id != null ? String(entry.id) : entry?.url;
    if (!key || seen.has(key)) continue;
    seen.add(key);
    result.push(entry);
  }

  return result;
}

async function fetchPlaylistViaInnertube(urlOrId) {
  const playlistId = extractPlaylistId(urlOrId);
  if (!playlistId) {
    throw new Error('ID playlist tidak valid.');
  }

  const html = await fetchHtml(`https://www.youtube.com/playlist?list=${playlistId}`);
  const dataMatch = html.match(/var ytInitialData = ({.+?});<\/script>/);
  if (!dataMatch) {
    throw new Error('Data playlist tidak ditemukan di halaman YouTube.');
  }

  const initialData = JSON.parse(dataMatch[1]);
  const apiKey = html.match(/"INNERTUBE_API_KEY":"([^"]+)"/)?.[1];
  const clientVersion = html.match(/"INNERTUBE_CLIENT_VERSION":"([^"]+)"/)?.[1]
    || '2.20240101.00.00';

  if (!apiKey) {
    throw new Error('Kunci InnerTube tidak ditemukan.');
  }

  let allVideos = extractVideos(initialData);
  let token = findPlaylistContinuation(initialData);
  let pages = 1;

  while (token && pages < 30) {
    const response = await postBrowse(apiKey, clientVersion, token);
    const batch = extractVideos(response);
    allVideos = allVideos.concat(batch);
    token = findPlaylistContinuation(response);
    pages += 1;
    if (!token) break;
  }

  const entries = dedupeEntries(allVideos.map((video) => mapVideoEntry(video.id, video.title, video.duration)));

  return {
    playlistId,
    entries,
    pages,
  };
}

module.exports = {
  fetchPlaylistViaInnertube,
  extractPlaylistId,
};
