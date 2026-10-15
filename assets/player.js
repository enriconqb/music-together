const state = {
  roomId: null,
  userName: '',
  userId: '',
  queue: [],
  currentIndex: -1,
  isPlaying: false,
  isShuffle: false,
  isRepeat: false,
  ytPlayer: null,
  ytReady: false,
  volume: 80,
  muted: false,
  remoteSync: false,
  wantPlaying: null,
  progressInterval: null,
  revision: 0,
  playNonce: 0,
  lastChat: 0,
  lastLog: 0,
  pollTimer: null,
  applyingRemote: false,
  loadedKey: '',
  playerMode: 'audio',
  pendingSeek: null,
  ignoreProgressUntil: 0,
  hostUserId: '',
  hostOnly: false,
  listedRooms: [],
  listPoll: null,
  countdownTimer: null,
};

function loadProfile() {
  let profile = {};
  try { profile = JSON.parse(localStorage.getItem('synctunes_profile') || '{}'); } catch (e) {}
  if (!profile.userId) profile.userId = 'user_' + Math.random().toString(36).slice(2, 11);
  state.userId = profile.userId;
  state.userName = (profile.name || '').trim().slice(0, 20);
  try { localStorage.setItem('synctunes_profile', JSON.stringify({ userId: state.userId, name: state.userName })); } catch (e) {}
  const mode = localStorage.getItem('synctunes_player_mode');
  state.playerMode = mode === 'video' ? 'video' : 'audio';
}

function hasProfile() {
  return state.userName.length > 0;
}

function applyProfileUI() {
  const ready = hasProfile();
  document.getElementById('profileGate').classList.toggle('hidden', ready);
  document.getElementById('createRoomName').classList.add('hidden');
  document.getElementById('joinUserName').classList.add('hidden');
  const btn = document.getElementById('profileBtn');
  btn.classList.toggle('hidden', !ready);
  btn.textContent = state.userName;
}

function saveProfileFromGate() {
  const name = document.getElementById('profileNameInput').value.trim().slice(0, 20);
  if (!name) {
    showToast('Nama wajib diisi', 'error');
    return;
  }
  state.userName = name;
  localStorage.setItem('synctunes_profile', JSON.stringify({ userId: state.userId, name }));
  applyProfileUI();
  resumeRoom();
}

function openProfileEditor() {
  document.getElementById('profileEditInput').value = state.userName;
  document.getElementById('profileEditor').classList.remove('hidden');
}

function closeProfileEditor() {
  document.getElementById('profileEditor').classList.add('hidden');
}

function saveProfileEdit() {
  const name = document.getElementById('profileEditInput').value.trim().slice(0, 20);
  if (!name) {
    showToast('Nama wajib diisi', 'error');
    return;
  }
  state.userName = name;
  localStorage.setItem('synctunes_profile', JSON.stringify({ userId: state.userId, name }));
  applyProfileUI();
  closeProfileEditor();
  showToast('Profil diperbarui', 'success');
}

function requireProfile() {
  if (hasProfile()) return true;
  document.getElementById('profileGate').classList.remove('hidden');
  showToast('Isi nama kamu dulu', 'error');
  return false;
}

function setPlayerMode(mode) {
  const song = state.queue[state.currentIndex];
  let resumeAt = null;
  let wasPlaying = false;
  if (song && song.source === 'youtube' && !song.live && state.ytPlayer && state.ytReady) {
    try {
      resumeAt = state.ytPlayer.getCurrentTime() || 0;
      wasPlaying = state.ytPlayer.getPlayerState() === 1;
    } catch (e) {}
  }
  state.playerMode = mode === 'video' ? 'video' : 'audio';
  localStorage.setItem('synctunes_player_mode', state.playerMode);
  document.body.classList.toggle('audio-only', state.playerMode === 'audio');
  document.getElementById('modeAudioBtn').classList.toggle('bg-violet-600', state.playerMode === 'audio');
  document.getElementById('modeAudioBtn').classList.toggle('text-white', state.playerMode === 'audio');
  document.getElementById('modeVideoBtn').classList.toggle('bg-violet-600', state.playerMode === 'video');
  document.getElementById('modeVideoBtn').classList.toggle('text-white', state.playerMode === 'video');
  if (song && song.source === 'spotify') {
    state.loadedKey = '';
    playSongAtIndex(state.currentIndex, false);
  } else if (resumeAt !== null) {
    const restore = () => {
      if (!state.ytPlayer || !state.ytReady) return;
      state.remoteSync = true;
      try {
        if (resumeAt > 1) state.ytPlayer.seekTo(resumeAt, true);
        if (wasPlaying) state.ytPlayer.playVideo();
        else state.ytPlayer.pauseVideo();
      } catch (e) {}
      state.ignoreProgressUntil = Date.now() + 2500;
      state.remoteSync = false;
    };
    restore();
    setTimeout(restore, 400);
  }
  updateAudioCover(song || null);
}

function updateAudioCover(song) {
  const img = document.getElementById('audioCoverImg');
  const icon = document.getElementById('audioCoverIcon');
  const title = document.getElementById('audioCoverTitle');
  const hint = document.getElementById('audioCoverHint');
  const art = document.getElementById('audioCoverArt');
  if (!song) {
    img.classList.add('hidden');
    img.removeAttribute('src');
    icon.classList.remove('hidden');
    art.className = 'w-36 h-36 rounded-2xl mb-4 flex items-center justify-center overflow-hidden bg-gradient-to-br from-violet-600/30 to-fuchsia-600/20 border border-white/10';
    title.textContent = 'Belum ada lagu';
    hint.classList.remove('hidden');
    return;
  }
  title.textContent = song.title || 'Tidak ada judul';
  hint.classList.add('hidden');
  if (song.source === 'youtube' && song.videoId) {
    img.src = 'https://i.ytimg.com/vi/' + song.videoId + '/hqdefault.jpg';
    img.classList.remove('hidden');
    icon.classList.add('hidden');
    art.className = 'w-36 h-36 rounded-2xl mb-4 flex items-center justify-center overflow-hidden shadow-lg shadow-black/40';
  } else {
    img.classList.add('hidden');
    img.removeAttribute('src');
    icon.classList.remove('hidden');
    art.className = 'w-36 h-36 rounded-2xl mb-4 flex items-center justify-center overflow-hidden bg-gradient-to-br from-green-600/40 to-emerald-800/30 border border-white/10';
  }
}

function toggleTheme() {
  document.body.classList.toggle('light');
  const isLight = document.body.classList.contains('light');
  document.getElementById('sunIcon').classList.toggle('hidden', !isLight);
  document.getElementById('moonIcon').classList.toggle('hidden', isLight);
  try { localStorage.setItem('synctunes_theme', isLight ? 'light' : 'dark'); } catch (e) {}
}
try {
  if (localStorage.getItem('synctunes_theme') === 'light') document.body.classList.add('light');
} catch (e) {}

function switchMobileTab(tab) {
  const ps = document.getElementById('playerScreen');
  if (!ps) return;
  ps.dataset.mobileTab = tab;
  document.querySelectorAll('[data-tab-btn]').forEach((btn) => {
    const active = btn.dataset.tabBtn === tab;
    btn.classList.toggle('bg-violet-600', active);
    btn.classList.toggle('text-white', active);
    btn.classList.toggle('dark-text-muted', !active);
  });
}

function onYouTubeIframeAPIReady() {
  if (state.ytPlayer) return;
  window.onYouTubeIframeAPIReady = onYouTubeIframeAPIReady;
  state.ytPlayer = new YT.Player('yt-player', {
    height: '100%',
    width: '100%',
    playerVars: { playsinline: 1, rel: 0, modestbranding: 1 },
    events: { onReady: onPlayerReady, onStateChange: onPlayerStateChange, onError: onPlayerError },
  });
}
function onPlayerReady(e) {
  state.ytReady = true;
  applyLocalVolume();
  const song = state.queue[state.currentIndex];
  if (song && song.source === 'youtube') playSongAtIndex(state.currentIndex, false);
}
function onPlayerStateChange(e) {
  if (e.data === YT.PlayerState.PLAYING) {
    if (state.wantPlaying === false) {
      state.wantPlaying = null;
      state.remoteSync = true;
      state.ytPlayer.pauseVideo();
      return;
    }
    state.wantPlaying = null;
    if (state.seekAfterReady) {
      const at = state.seekAfterReady;
      state.seekAfterReady = null;
      try { state.ytPlayer.seekTo(at, true); } catch (e) {}
    }
    applyLocalVolume();
    state.isPlaying = true;
    updatePlayPauseUI(true);
    startProgressTracker();
    state.remoteSync = false;
  } else if (e.data === YT.PlayerState.PAUSED) {
    state.isPlaying = false;
    updatePlayPauseUI(false);
    stopProgressTracker();
    state.remoteSync = false;
  } else if (e.data === YT.PlayerState.ENDED) {
    state.isPlaying = false;
    stopProgressTracker();
    onSongEnd();
  }
}
function onPlayerError() {
  if (!canControl()) return;
  showToast('Lagu tidak tersedia, melewati...', 'warning');
  setTimeout(() => skipSong(), 1200);
}

async function api(url, body) {
  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
  });
  const data = await readJson(res);
  if (!data.ok) throw new Error(data.error || 'Permintaan gagal.');
  return data;
}

async function readJson(res) {
  const text = await res.text();
  const trimmed = text.trim();
  if (trimmed.startsWith('{') || trimmed.startsWith('[')) {
    try { return JSON.parse(trimmed); } catch (e) {}
  }
  const embedded = trimmed.match(/\{[\s\S]*\}\s*$/);
  if (embedded) {
    try { return JSON.parse(embedded[0]); } catch (e) {}
  }
  const plain = trimmed.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
  return { ok: false, error: plain.slice(0, 180) || 'Respons server bukan JSON.' };
}

function deletionLabel(room) {
  if (!room.deleteAt || (room.online || 0) > 0) return '';
  const left = Math.ceil((Number(room.deleteAt) - Date.now()) / 1000);
  if (left <= 0) return '';
  return `<p class="text-[11px] text-amber-300" data-delete-at="${Number(room.deleteAt)}">hapus dalam ${left} dtk</p>`;
}

function renderRoomList(rooms) {
  const container = document.getElementById('roomList');
  const countEl = document.getElementById('roomListCount');
  countEl.textContent = rooms.length + ' room' + (rooms.length !== 1 ? 's' : '');
  if (!rooms.length) {
    container.innerHTML = '<p class="dark-text-muted text-gray-500 text-sm text-center py-6">Belum ada room. Buat yang pertama.</p>';
    return;
  }
  container.innerHTML = rooms.map((room) => `
    <div class="flex items-center justify-between p-3 dark-bg-primary bg-[#0f0f1a] rounded-xl border dark-border border-[#2a2a3e]">
      <div class="min-w-0 flex-1">
        <div class="flex items-center gap-2">
          <span class="font-mono font-bold text-violet-400 text-sm">${room.code}</span>
          ${room.isPrivate ? '<span class="text-yellow-500 text-xs">terkunci</span>' : ''}
          ${room.hostOnly ? '<span class="text-amber-300 text-[10px]">host</span>' : ''}
        </div>
        <p class="text-xs dark-text-muted text-gray-400 truncate">oleh ${escapeHtml(room.createdBy || 'Unknown')} · ${room.online || 0} online</p>
        ${deletionLabel(room)}
      </div>
      <button type="button" onclick="joinRoomFromList('${room.code}', ${room.isPrivate ? 'true' : 'false'})"
        class="px-3 py-1.5 bg-violet-600 hover:bg-violet-500 rounded-lg text-xs font-medium text-white ml-2">Join</button>
    </div>`).join('');
}

function tickRoomCountdowns() {
  document.querySelectorAll('[data-delete-at]').forEach((el) => {
    const left = Math.ceil((Number(el.dataset.deleteAt) - Date.now()) / 1000);
    if (left <= 0) {
      const card = el.closest('.flex.items-center.justify-between');
      if (card) card.remove();
      const container = document.getElementById('roomList');
      const countEl = document.getElementById('roomListCount');
      const leftCards = container ? container.querySelectorAll('.font-mono').length : 0;
      if (countEl) countEl.textContent = leftCards + ' room' + (leftCards !== 1 ? 's' : '');
      if (container && leftCards === 0) {
        container.innerHTML = '<p class="dark-text-muted text-gray-500 text-sm text-center py-6">Belum ada room. Buat yang pertama.</p>';
      }
      return;
    }
    el.textContent = 'hapus dalam ' + left + ' dtk';
  });
}

async function refreshRoomList() {
  const statusEl = document.getElementById('fbStatus');
  if (state.roomId || !statusEl) return;
  try {
    const data = await api('api/room.php', { action: 'list' });
    statusEl.innerHTML = '<span class="text-green-400">●</span> Server lokal aktif';
    state.listedRooms = (data.rooms || []).filter((room) => !room.deleteAt || Number(room.deleteAt) > Date.now());
    renderRoomList(state.listedRooms);
  } catch (err) {
    statusEl.innerHTML = '<span class="text-red-400">●</span> Server tidak terjangkau';
    document.getElementById('roomList').innerHTML = '<p class="text-sm text-center text-red-400 py-6">Gagal memuat room.</p>';
  }
}

function setupRoomList() {
  if (state.listPoll) clearInterval(state.listPoll);
  if (state.countdownTimer) clearInterval(state.countdownTimer);
  refreshRoomList();
  state.listPoll = setInterval(refreshRoomList, 2000);
  state.countdownTimer = setInterval(tickRoomCountdowns, 1000);
}

async function createRoom() {
  if (!requireProfile()) return;
  const name = state.userName;
  const isPrivate = document.getElementById('privateRoomCheck').checked;
  const password = isPrivate ? document.getElementById('roomPassword').value.trim() : '';
  if (isPrivate && !password) {
    showToast('Isi password untuk room privat', 'error');
    return;
  }
  state.userName = name;
  try {
    const hostOnly = document.getElementById('hostOnlyCheck').checked;
    const data = await api('api/room.php', { action: 'create', userName: name, userId: state.userId, password, hostOnly });
    enterRoom(data.room);
  } catch (err) {
    showToast(err.message, 'error');
  }
}

async function joinRoom() {
  if (!requireProfile()) return;
  const code = document.getElementById('joinRoomCode').value.trim().toUpperCase();
  const name = state.userName;
  if (!/^[A-Z0-9]{6}$/.test(code)) {
    showToast('Kode room harus 6 karakter', 'error');
    return;
  }
  let password = '';
  const listed = document.querySelector(`[onclick*="${code}"]`);
  if (listed && listed.getAttribute('onclick').includes('true')) {
    password = prompt('Room ini privat. Masukkan password:') || '';
  }
  state.userName = name;
  try {
    const data = await api('api/room.php', { action: 'join', code, userName: name, userId: state.userId, password });
    enterRoom(data.room);
  } catch (err) {
    if (err.message === 'Password salah.' || err.message.includes('Password')) {
      const retry = prompt('Password salah atau diperlukan. Masukkan password:') || '';
      try {
        const data = await api('api/room.php', { action: 'join', code, userName: name, userId: state.userId, password: retry });
        enterRoom(data.room);
        return;
      } catch (e2) {
        showToast(e2.message, 'error');
        return;
      }
    }
    showToast(err.message, 'error');
  }
}

function joinRoomFromList(code, isPrivate) {
  if (!requireProfile()) return;
  document.getElementById('joinRoomCode').value = code;
  if (isPrivate) {
    const password = prompt('Room ini privat. Masukkan password:') || '';
    state.userName = state.userName;
    api('api/room.php', {
      action: 'join',
      code,
      userName: state.userName,
      userId: state.userId,
      password,
    }).then((data) => enterRoom(data.room)).catch((err) => showToast(err.message, 'error'));
    return;
  }
  joinRoom();
}

function enterRoom(room) {
  state.roomId = room.code;
  state.revision = 0;
  state.playNonce = 0;
  state.lastChat = 0;
  state.lastLog = 0;
  state.loadedKey = '';
  document.getElementById('chatMessages').innerHTML = '';
  document.getElementById('logList').innerHTML = '';
  switchSideTab('log');
  document.getElementById('landingScreen').classList.add('hidden');
  document.getElementById('playerScreen').classList.remove('hidden');
  document.getElementById('roomInfo').classList.remove('hidden');
  document.getElementById('roomInfo').classList.add('sm:flex');
  document.getElementById('leaveBtn').classList.remove('hidden');
  document.getElementById('roomIdDisplay').textContent = room.code;
  document.getElementById('roomIdDisplayMobile').textContent = room.code;
  document.getElementById('onlineIndicator').classList.remove('hidden');
  document.getElementById('onlineIndicator').classList.add('flex');
  history.replaceState(null, '', '#' + room.code);
  try { sessionStorage.setItem('synctunes_active_room', room.code); } catch (e) {}
  applyRoom(room, true);
  if (state.pollTimer) clearInterval(state.pollTimer);
  state.pollTimer = setInterval(pollRoom, 1500);
  openRoleIfNeeded(room.code);
}

async function leaveRoom(silent) {
  const code = state.roomId;
  if (state.pollTimer) clearInterval(state.pollTimer);
  state.pollTimer = null;
  if (code && !silent) {
    api('api/room.php', { action: 'leave', code, userId: state.userId, userName: state.userName }).catch(() => {});
  }
  state.roomId = null;
  state.queue = [];
  state.currentIndex = -1;
  document.getElementById('playerScreen').classList.add('hidden');
  document.getElementById('landingScreen').classList.remove('hidden');
  document.getElementById('leaveBtn').classList.add('hidden');
  document.getElementById('roomIdDisplay').textContent = 'No room';
  history.replaceState(null, '', location.pathname);
  try { sessionStorage.removeItem('synctunes_active_room'); } catch (e) {}
  if (code) {
    try { sessionStorage.removeItem('synctunes_role_' + code); } catch (e) {}
  }
  if (state.ytPlayer && state.ytReady) {
    try { state.ytPlayer.stopVideo(); } catch (e) {}
  }
  setupRoomList();
}

async function pollRoom() {
  if (!state.roomId) return;
  try {
    const data = await api('api/room.php', {
      action: 'state',
      code: state.roomId,
      userId: state.userId,
      userName: state.userName,
      role: localRole(),
      sinceChat: state.lastChat,
      sinceLog: state.lastLog,
    });
    if (data.deleted) {
      showToast('Room ditutup karena kosong', 'info');
      leaveRoom(true);
      return;
    }
    applyRoom(data.room, false);
  } catch (err) {
    if (String(err.message).includes('tidak ditemukan')) {
      showToast('Room sudah ditutup', 'info');
      leaveRoom(true);
      return;
    }
    console.warn(err);
  }
}

function applyRoom(room, initial) {
  state.applyingRemote = true;
  const indexChanged = room.currentIndex !== state.currentIndex || room.playNonce !== state.playNonce;
  state.queue = room.queue || [];
  state.currentIndex = room.currentIndex;
  state.hostUserId = room.hostUserId || '';
  state.hostOnly = !!room.hostOnly;
  applyHostLock();
  state.revision = room.revision;
  state.playNonce = room.playNonce || 0;
  updateQueueUI();
  const count = (room.online || []).length;
  document.getElementById('onlineCount').textContent = count + ' online';
  renderPeople(room);
  (room.chat || []).forEach((msg) => {
    if (msg.time > state.lastChat) {
      appendChatMessage(msg);
      state.lastChat = msg.time;
    }
  });
  (room.log || []).forEach((entry) => {
    if (entry.time > state.lastLog) {
      appendLogEntry(entry);
      state.lastLog = entry.time;
    }
  });
  if (state.currentIndex < 0 || !state.queue[state.currentIndex]) {
    stopPlaybackEmpty();
  } else if (indexChanged || initial) {
    state.wantPlaying = !!room.isPlaying;
    if (initial) state.pendingSeek = resumeSeconds(room);
    playSongAtIndex(state.currentIndex, false);
  } else {
    syncPlayback(!!room.isPlaying);
  }
  state.applyingRemote = false;
}

function canControl() {
  return !state.hostOnly || state.userId === state.hostUserId;
}

function denyControl() {
  if (canControl()) return false;
  showToast('Hanya host yang mengatur pemutaran', 'info');
  return true;
}

function applyHostLock() {
  const allowed = canControl();
  document.querySelectorAll('[data-host-control]').forEach((el) => {
    el.disabled = !allowed;
    el.classList.toggle('opacity-40', !allowed);
    el.classList.toggle('pointer-events-none', !allowed);
  });
  const addPanel = document.getElementById('addSongPanel');
  if (addPanel) addPanel.classList.toggle('hidden', !allowed);
  ['clearQueueBtn', 'shuffleQueueBtn'].forEach((id) => {
    const btn = document.getElementById(id);
    if (!btn) return;
    btn.disabled = !allowed;
    btn.classList.toggle('hidden', !allowed);
  });
  const bar = document.getElementById('progressBarContainer');
  if (bar) bar.classList.toggle('pointer-events-none', !allowed);
  const label = document.getElementById('hostLockLabel');
  if (!label) return;
  if (!state.hostOnly) {
    label.textContent = '';
    return;
  }
  label.textContent = allowed ? 'Kamu host' : 'Hanya host yang mengatur pemutaran';
}

async function addSongFromInput() {
  if (denyControl()) return;
  const input = document.getElementById('songUrlInput');
  const q = input.value.trim();
  if (!q || !state.roomId) return;
  input.disabled = true;
  showToast('Membaca tautan...', 'info');
  try {
    const parsed = await fetch('api/parse.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ q }),
    }).then(readJson);
    if (!parsed.ok) throw new Error(parsed.error || 'Gagal membaca tautan.');
    input.value = '';
    const data = await api('api/room.php', {
      action: 'add',
      code: state.roomId,
      userId: state.userId,
      userName: state.userName,
      songs: parsed.songs,
    });
    showToast('Ditambahkan: ' + (parsed.songs.length > 1 ? parsed.songs.length + ' lagu' : parsed.songs[0].title), 'success');
    applyRoom({ ...data.room, chat: [] }, false);
  } catch (err) {
    showToast(err.message, 'error');
  } finally {
    input.disabled = false;
  }
}

function stopPlaybackEmpty() {
  state.isPlaying = false;
  state.wantPlaying = false;
  state.loadedKey = '';
  state.pendingSeek = null;
  state.seekAfterReady = null;
  if (state.ytPlayer && state.ytReady) {
    try { state.ytPlayer.stopVideo(); } catch (e) {}
  }
  const embed = document.getElementById('spotifyEmbedContainer');
  if (embed) embed.innerHTML = '';
  document.getElementById('noSongOverlay').classList.remove('hidden');
  document.getElementById('spotifyOverlay').classList.add('hidden');
  updatePlayPauseUI(false);
  stopProgressTracker();
  updateNowPlayingUI(null);
}

function songKey(song) {
  if (!song) return '';
  if (song.source === 'youtube') return 'yt:' + song.videoId;
  return 'sp:' + song.spotifyType + ':' + song.spotifyId;
}

function playSongAtIndex(index, broadcast) {
  if (index < 0 || index >= state.queue.length) return;
  state.currentIndex = index;
  const song = state.queue[index];
  updateNowPlayingUI(song);
  const key = songKey(song) + ':' + state.playNonce;
  if (song.source === 'youtube') {
    document.getElementById('spotifyOverlay').classList.add('hidden');
    document.getElementById('noSongOverlay').classList.add('hidden');
    if (state.ytPlayer && state.ytReady) {
      if (state.loadedKey !== key) {
        const live = !!song.live;
        const start = !live && typeof state.pendingSeek === 'number' ? state.pendingSeek : 0;
        state.pendingSeek = null;
        state.seekAfterReady = !live && start > 1 ? start : null;
        if (state.seekAfterReady) state.ignoreProgressUntil = Date.now() + 2500;
        if (live) state.ytPlayer.loadVideoById(song.videoId);
        else state.ytPlayer.loadVideoById({ videoId: song.videoId, startSeconds: start });
        applyLocalVolume();
        state.loadedKey = key;
      }
    }
  } else {
    document.getElementById('noSongOverlay').classList.add('hidden');
    document.getElementById('spotifyOverlay').classList.remove('hidden');
    if (state.ytPlayer && state.ytReady) {
      try { state.ytPlayer.pauseVideo(); } catch (e) {}
    }
    if (state.loadedKey !== key) {
      const c = document.getElementById('spotifyEmbedContainer');
      const compact = state.playerMode === 'audio' || song.spotifyType === 'track' || song.spotifyType === 'episode';
      const height = compact ? '152' : '380';
      c.innerHTML = '';
      const iframe = document.createElement('iframe');
      iframe.src = `https://open.spotify.com/embed/${song.spotifyType}/${song.spotifyId}?utm_source=generator`;
      iframe.width = '100%';
      iframe.height = height;
      iframe.frameBorder = '0';
      iframe.allow = 'autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture';
      iframe.style.borderRadius = '12px';
      c.appendChild(iframe);
      state.loadedKey = key;
    }
  }
  if (broadcast) {
    api('api/room.php', {
      action: 'play',
      code: state.roomId,
      userId: state.userId,
      userName: state.userName,
      index,
    }).then((data) => {
      state.playNonce = data.room.playNonce;
    }).catch((err) => showToast(err.message, 'error'));
  }
}

function togglePlayPause() {
  if (denyControl()) return;
  const song = state.queue[state.currentIndex];
  if (!song) return;
  if (song.source !== 'youtube') {
    showToast('Gunakan kontrol di pemutar Spotify', 'info');
    return;
  }
  const next = !state.isPlaying;
  if (!state.ytPlayer || !state.ytReady) {
    showToast('Pemutar belum siap, perintah tetap dikirim ke room', 'info');
    state.isPlaying = next;
    updatePlayPauseUI(next);
    api('api/room.php', {
      action: 'transport',
      code: state.roomId,
      userId: state.userId,
      userName: state.userName,
      isPlaying: next,
      position: 0,
    }).catch((err) => showToast(err.message, 'error'));
    return;
  }
  state.wantPlaying = null;
  state.remoteSync = true;
  if (next) state.ytPlayer.playVideo();
  else state.ytPlayer.pauseVideo();
  let position = 0;
  try { position = state.ytPlayer.getCurrentTime() || 0; } catch (e) {}
  api('api/room.php', {
    action: 'transport',
    code: state.roomId,
    userId: state.userId,
    userName: state.userName,
    isPlaying: next,
    position,
  }).catch((err) => showToast(err.message, 'error'));
}

function syncPlayback(isPlaying) {
  const song = state.queue[state.currentIndex];
  if (!song || song.source !== 'youtube' || !state.ytPlayer || !state.ytReady) return;
  let playerState = -1;
  try { playerState = state.ytPlayer.getPlayerState(); } catch (e) { return; }
  if (isPlaying && playerState !== YT.PlayerState.PLAYING && playerState !== YT.PlayerState.BUFFERING) {
    state.remoteSync = true;
    state.ytPlayer.playVideo();
  } else if (!isPlaying && (playerState === YT.PlayerState.PLAYING || playerState === YT.PlayerState.BUFFERING)) {
    state.remoteSync = true;
    state.ytPlayer.pauseVideo();
  }
}

function applyLocalVolume() {
  if (state.ytPlayer && state.ytReady) {
    if (state.muted) {
      try { state.ytPlayer.mute(); } catch (e) {}
      state.ytPlayer.setVolume(0);
    } else {
      try { state.ytPlayer.unMute(); } catch (e) {}
      state.ytPlayer.setVolume(state.volume);
    }
  }
  const btn = document.getElementById('muteBtn');
  if (!btn) return;
  document.getElementById('muteLabel').textContent = state.muted ? 'Muted' : 'Mute';
  document.getElementById('muteIconOff').classList.toggle('hidden', state.muted);
  document.getElementById('muteIconOn').classList.toggle('hidden', !state.muted);
  btn.classList.toggle('bg-[#14532d]', state.muted);
  btn.classList.toggle('border-green-800', state.muted);
  btn.classList.toggle('text-white', state.muted);
  btn.classList.toggle('bg-[#1a1a2e]', !state.muted);
  btn.classList.toggle('border-[#3a3a4e]', !state.muted);
  btn.classList.toggle('text-gray-300', !state.muted);
}

function localRole() {
  if (!state.roomId) return '';
  let saved = '';
  try { saved = sessionStorage.getItem('synctunes_role_' + state.roomId) || ''; } catch (e) {}
  if (saved === 'speaker' || saved === 'requester') return saved;
  return state.muted ? 'requester' : '';
}

function renderPeople(room) {
  const list = document.getElementById('peopleList');
  if (!list) return;
  const hostId = room.hostUserId || state.hostUserId || '';
  let people = (room.online || []).slice();
  if (!people.length && (hostId || state.userId)) {
    const selfIsHost = !hostId || hostId === state.userId;
    people = [{
      id: selfIsHost ? (hostId || state.userId) : hostId,
      name: room.createdBy || state.userName || 'Host',
      role: localRole(),
    }];
    if (!selfIsHost && state.userId) {
      people.push({ id: state.userId, name: state.userName || 'Guest', role: localRole() });
    }
  }
  people.sort((a, b) => {
    const ah = a.id === hostId ? 0 : 1;
    const bh = b.id === hostId ? 0 : 1;
    if (ah !== bh) return ah - bh;
    return String(a.name || '').localeCompare(String(b.name || ''));
  });
  if (!people.length) {
    list.innerHTML = '<p class="text-xs text-gray-500">Belum ada yang join.</p>';
    return;
  }
  list.innerHTML = people.map((person) => {
    const mine = person.id === state.userId;
    const badges = [];
    if (person.id === hostId) {
      badges.push('<span class="px-1.5 py-0.5 rounded-md bg-violet-600/30 text-violet-200">Host</span>');
    }
    if (person.role === 'speaker') {
      badges.push('<span class="px-1.5 py-0.5 rounded-md bg-[#2a2a3e] text-gray-200">Speaker</span>');
    } else if (person.role === 'requester') {
      badges.push('<span class="px-1.5 py-0.5 rounded-md bg-[#14532d] text-green-200">Requester</span>');
    } else {
      badges.push('<span class="px-1.5 py-0.5 rounded-md bg-[#0f0f1a] text-gray-500">Belum pilih</span>');
    }
    return `<div class="px-2 py-1.5 rounded-lg bg-[#0f0f1a]">
      <p class="text-xs break-words">${escapeHtml(person.name || 'Guest')}${mine ? ' <span class="text-gray-500">(kamu)</span>' : ''}</p>
      <div class="flex flex-wrap gap-1 mt-1 text-[10px] font-medium">${badges.join('')}</div>
    </div>`;
  }).join('');
}

function toggleMute() {
  state.muted = !state.muted;
  if (state.roomId) {
    try { sessionStorage.setItem('synctunes_role_' + state.roomId, state.muted ? 'requester' : 'speaker'); } catch (e) {}
  }
  applyLocalVolume();
}

function openRoleIfNeeded(code) {
  const modal = document.getElementById('roleModal');
  if (!modal) return;
  let saved = '';
  try { saved = sessionStorage.getItem('synctunes_role_' + code) || ''; } catch (e) {}
  if (saved === 'speaker' || saved === 'requester') {
    state.muted = saved === 'requester';
    applyLocalVolume();
    modal.style.display = 'none';
    return;
  }
  modal.classList.remove('hidden');
  modal.style.display = 'flex';
}

function chooseRole(role) {
  state.muted = role === 'requester';
  if (state.roomId) {
    try { sessionStorage.setItem('synctunes_role_' + state.roomId, role); } catch (e) {}
  }
  const modal = document.getElementById('roleModal');
  if (modal) {
    modal.classList.add('hidden');
    modal.style.display = 'none';
  }
  applyLocalVolume();
}

function skipSong() {
  if (denyControl()) return;
  if (!state.queue.length) return;
  let next;
  if (state.isShuffle) {
    next = Math.floor(Math.random() * state.queue.length);
  } else {
    next = state.currentIndex + 1;
    if (next >= state.queue.length) next = 0;
  }
  playSongAtIndex(next, true);
}

function playPrevious() {
  if (denyControl()) return;
  if (!state.queue.length) return;
  if (state.ytPlayer && state.ytReady && state.ytPlayer.getCurrentTime() > 3) {
    state.ytPlayer.seekTo(0);
    reportProgress(false);
    return;
  }
  let prev = state.currentIndex - 1;
  if (prev < 0) prev = state.isRepeat ? state.queue.length - 1 : 0;
  playSongAtIndex(prev, true);
}

function onSongEnd() {
  if (!canControl()) return;
  if (state.isRepeat) playSongAtIndex(state.currentIndex, true);
  else skipSong();
}

function banFromQueue(songId) {
  if (denyControl()) return;
  api('api/room.php', { action: 'ban', code: state.roomId, userId: state.userId, userName: state.userName, songId })
    .then((data) => applyRoom({ ...data.room, chat: [] }, false))
    .catch((err) => showToast(err.message, 'error'));
}
function removeFromQueue(songId) {
  if (denyControl()) return;
  api('api/room.php', { action: 'remove', code: state.roomId, userId: state.userId, userName: state.userName, songId })
    .then((data) => applyRoom({ ...data.room, chat: [] }, false))
    .catch((err) => showToast(err.message, 'error'));
}
function playNow(songId) {
  if (denyControl()) return;
  const idx = state.queue.findIndex((s) => s.id === songId);
  if (idx >= 0) playSongAtIndex(idx, true);
}
function clearQueue() {
  if (denyControl()) return;
  if (!confirm('Kosongkan seluruh antrian?')) return;
  api('api/room.php', { action: 'clear', code: state.roomId, userId: state.userId, userName: state.userName })
    .then((data) => applyRoom({ ...data.room, chat: [] }, false))
    .catch((err) => showToast(err.message, 'error'));
}
function shuffleQueue() {
  if (denyControl()) return;
  if (state.queue.length < 2) return;
  const ids = state.queue.map((s) => s.id);
  for (let i = ids.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [ids[i], ids[j]] = [ids[j], ids[i]];
  }
  api('api/room.php', { action: 'reorder', code: state.roomId, userId: state.userId, userName: state.userName, order: ids })
    .then((data) => applyRoom({ ...data.room, chat: [] }, false))
    .catch((err) => showToast(err.message, 'error'));
}

function updateQueueUI() {
  const container = document.getElementById('queueList');
  const countEl = document.getElementById('queueCount');
  if (!container) return;
  if (!state.queue.length) {
    container.innerHTML = '<div class="text-center py-8 dark-text-muted text-gray-600 text-sm">Antrian kosong</div>';
    countEl.textContent = '0 lagu';
    return;
  }
  countEl.textContent = state.queue.length + ' lagu';
  const query = (document.getElementById('queueSearch')?.value || '').trim().toLowerCase();
  const visible = state.queue
    .map((song, i) => ({ song, i }))
    .filter(({ song }) => !query || String(song.title || '').toLowerCase().includes(query));
  if (!visible.length) {
    container.innerHTML = '<div class="text-center py-8 dark-text-muted text-gray-600 text-sm">Tidak ada judul yang cocok</div>';
    return;
  }
  container.innerHTML = visible.map(({ song, i }) => {
    const isActive = i === state.currentIndex;
    const icon = song.source === 'youtube' ? '▶' : '♫';
    return `<div class="flex items-center gap-2 p-2 rounded-lg ${isActive ? 'bg-violet-600/20 border border-violet-500/30' : 'dark-hover hover:bg-[#252540]'} group" draggable="${canControl() ? 'true' : 'false'}" data-song-id="${song.id}">
      <div class="text-xs w-4 text-center">${icon}</div>
      <div class="flex-1 min-w-0">
        <p class="text-xs sm:text-sm font-medium truncate ${isActive ? 'text-violet-400' : ''}">${escapeHtml(song.title)}</p>
        <p class="text-[10px] text-gray-500 truncate">${escapeHtml(song.artist)} · ${escapeHtml(song.addedBy || '')}</p>
      </div>
      <div class="flex gap-0.5 ${canControl() ? 'opacity-0 group-hover:opacity-100' : 'hidden'}">
        ${canControl() ? `<button type="button" onclick="playNow('${song.id}')" class="p-1 text-gray-400 hover:text-violet-400" title="Putar">▶</button>` : ''}
        ${canControl() && state.userId && state.userId === state.hostUserId ? `<button type="button" onclick="banFromQueue('${song.id}')" class="px-1.5 py-0.5 text-[10px] font-semibold text-red-300 hover:text-white" title="Ban">Ban</button>` : ''}
        ${canControl() ? `<button type="button" onclick="removeFromQueue('${song.id}')" class="p-1 text-gray-400 hover:text-red-400" title="Hapus">✕</button>` : ''}
      </div>
    </div>`;
  }).join('');
  setupDragAndDrop();
}

function updateNowPlayingUI(song) {
  if (!song) {
    document.getElementById('nowPlayingTitle').textContent = 'Tidak ada lagu';
    document.getElementById('nowPlayingArtist').textContent = '—';
    document.getElementById('nowPlayingSource').textContent = '—';
    document.getElementById('nowPlayingAddedBy').textContent = '';
    document.getElementById('currentTime').textContent = '0:00';
    document.getElementById('totalTime').textContent = '0:00';
    document.getElementById('progressBar').style.width = '0%';
    updateAudioCover(null);
    return;
  }
  document.getElementById('nowPlayingTitle').textContent = song.title || 'Unknown';
  document.getElementById('nowPlayingArtist').textContent = song.artist || 'Unknown';
  document.getElementById('nowPlayingSource').textContent = song.source === 'youtube' ? 'YouTube' : 'Spotify';
  document.getElementById('nowPlayingAddedBy').textContent = song.addedBy ? 'oleh ' + song.addedBy : '';
  updateAudioCover(song);
}

function updatePlayPauseUI(isPlaying) {
  document.getElementById('playIcon').classList.toggle('hidden', isPlaying);
  document.getElementById('pauseIcon').classList.toggle('hidden', !isPlaying);
}
function startProgressTracker() {
  stopProgressTracker();
  state.progressInterval = setInterval(() => {
    if (!state.ytPlayer || !state.ytReady || !state.isPlaying) return;
    const cur = state.ytPlayer.getCurrentTime() || 0;
    const tot = state.ytPlayer.getDuration() || 0;
    document.getElementById('currentTime').textContent = formatTime(cur);
    document.getElementById('totalTime').textContent = formatTime(tot);
    if (tot > 0) document.getElementById('progressBar').style.width = ((cur / tot) * 100) + '%';
    if (!state.lastProgressSent || Date.now() - state.lastProgressSent >= 5000) reportProgress(false);
  }, 500);
}
function resumeSeconds(room) {
  let pos = Number(room.position) || 0;
  if (room.isPlaying && room.positionAt) {
    pos += (Date.now() - Number(room.positionAt)) / 1000;
  }
  if (!Number.isFinite(pos) || pos < 0) return 0;
  return pos;
}

function reportProgress(useBeacon) {
  if (!canControl()) return;
  if (!state.roomId || Date.now() < state.ignoreProgressUntil) return;
  const song = state.queue[state.currentIndex];
  if (!song || song.source !== 'youtube' || !state.ytPlayer || !state.ytReady) return;
  let position = 0;
  try { position = state.ytPlayer.getCurrentTime() || 0; } catch (e) { return; }
  state.lastProgressSent = Date.now();
  const body = {
    action: 'progress',
    code: state.roomId,
    userId: state.userId,
    userName: state.userName,
    position,
  };
  if (useBeacon && navigator.sendBeacon) {
    navigator.sendBeacon('api/room.php', new Blob([JSON.stringify(body)], { type: 'application/json' }));
    return;
  }
  api('api/room.php', body).catch(() => {});
}

function stopProgressTracker() {
  if (state.progressInterval) clearInterval(state.progressInterval);
  state.progressInterval = null;
}

function setupDragAndDrop() {
  if (!canControl()) return;
  let draggedId = null;
  document.querySelectorAll('[data-song-id]').forEach((el) => {
    el.addEventListener('dragstart', () => { draggedId = el.dataset.songId; });
    el.addEventListener('dragover', (e) => e.preventDefault());
    el.addEventListener('drop', (e) => {
      e.preventDefault();
      if (draggedId && draggedId !== el.dataset.songId) reorderQueue(draggedId, el.dataset.songId);
    });
  });
}
function reorderQueue(fromId, toId) {
  if (denyControl()) return;
  const ids = state.queue.map((s) => s.id);
  const fi = ids.indexOf(fromId);
  const ti = ids.indexOf(toId);
  if (fi < 0 || ti < 0) return;
  ids.splice(ti, 0, ids.splice(fi, 1)[0]);
  api('api/room.php', { action: 'reorder', code: state.roomId, userId: state.userId, userName: state.userName, order: ids })
    .then((data) => applyRoom({ ...data.room, chat: [] }, false))
    .catch((err) => showToast(err.message, 'error'));
}

function sendChatMessage() {
  const input = document.getElementById('chatInput');
  const text = input.value.trim();
  if (!text || !state.roomId) return;
  input.value = '';
  api('api/room.php', { action: 'chat', code: state.roomId, userId: state.userId, userName: state.userName, text })
    .then((data) => (data.room.chat || []).forEach((msg) => {
      if (msg.time > state.lastChat) {
        appendChatMessage(msg);
        state.lastChat = msg.time;
      }
    }))
    .catch((err) => showToast(err.message, 'error'));
}
function switchLandingTab(tab) {
  const screen = document.getElementById('landingScreen');
  if (!screen) return;
  screen.dataset.landingTab = tab;
  ['create', 'join', 'rooms'].forEach((name) => {
    const btn = document.getElementById('landingTab' + name);
    if (!btn) return;
    const on = name === tab;
    btn.className = 'flex-1 py-2.5 rounded-lg text-sm font-medium transition-colors ' + (on ? 'bg-violet-600 text-white' : 'text-gray-400');
  });
}
function switchSideTab(tab) {
  const log = tab !== 'chat';
  document.getElementById('logList').style.display = log ? '' : 'none';
  document.getElementById('chatPane').style.display = log ? 'none' : 'flex';
  document.getElementById('tabLogBtn').className = 'px-3 py-1.5 text-xs font-semibold rounded-lg ' + (log ? 'bg-violet-600 text-white' : 'text-gray-400');
  document.getElementById('tabChatBtn').className = 'px-3 py-1.5 text-xs font-semibold rounded-lg ' + (!log ? 'bg-violet-600 text-white' : 'text-gray-400');
}
function appendLogEntry(entry) {
  const list = document.getElementById('logList');
  const empty = list.querySelector('.text-center');
  if (empty) empty.remove();
  const time = new Date(entry.time).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
  const row = document.createElement('div');
  row.className = 'flex flex-wrap items-center gap-1.5 text-xs text-gray-300 leading-relaxed';
  row.innerHTML = '<span class="shrink-0 px-1.5 py-0.5 rounded-md bg-[#0f0f1a] text-gray-400">' + escapeHtml(time) + '</span>'
    + '<span class="shrink-0 px-1.5 py-0.5 rounded-md bg-violet-600/25 text-violet-300">' + escapeHtml(entry.user) + '</span>'
    + '<span class="min-w-0 break-words">' + escapeHtml(entry.text) + '</span>';
  list.appendChild(row);
  list.scrollTop = list.scrollHeight;
}
function appendChatMessage(msg) {
  const c = document.getElementById('chatMessages');
  const isOwn = msg.user === state.userName;
  const div = document.createElement('div');
  div.className = 'fade-in';
  div.innerHTML = `<div class="flex ${isOwn ? 'justify-end' : 'justify-start'}">
    <div class="max-w-[80%] ${isOwn ? 'bg-violet-600/30 border-violet-500/20' : 'bg-[#252540] border-[#3a3a4e]'} rounded-xl px-3 py-2 border">
      <p class="text-[10px] ${isOwn ? 'text-violet-300' : 'text-gray-400'} font-medium mb-0.5">${escapeHtml(msg.user)}</p>
      <p class="text-xs break-words">${escapeHtml(msg.text)}</p>
    </div></div>`;
  c.appendChild(div);
  c.scrollTop = c.scrollHeight;
}

function formatTime(s) {
  if (!s || isNaN(s)) return '0:00';
  const m = Math.floor(s / 60);
  const sec = Math.floor(s % 60);
  return m + ':' + (sec < 10 ? '0' : '') + sec;
}
function escapeHtml(t) {
  if (!t) return '';
  const d = document.createElement('div');
  d.textContent = t;
  return d.innerHTML;
}
function setVolume(v) {
  state.volume = parseInt(v, 10);
  if (!state.muted) applyLocalVolume();
}
function toggleShuffle() {
  state.isShuffle = !state.isShuffle;
  document.getElementById('shuffleBtn').classList.toggle('text-violet-400', state.isShuffle);
}
function toggleRepeat() {
  state.isRepeat = !state.isRepeat;
  document.getElementById('repeatBtn').classList.toggle('text-violet-400', state.isRepeat);
}
function showToast(msg, type = 'info') {
  const colors = {
    success: 'bg-green-600/90 text-white',
    error: 'bg-red-600/90 text-white',
    warning: 'bg-yellow-500/90 text-black',
    info: 'bg-violet-600/90 text-white',
  };
  const t = document.createElement('div');
  t.className = `fixed bottom-4 left-1/2 -translate-x-1/2 z-[100] px-4 py-2.5 rounded-xl text-sm font-medium shadow-lg ${colors[type] || colors.info}`;
  t.textContent = msg;
  document.body.appendChild(t);
  setTimeout(() => t.remove(), 2400);
}

document.getElementById('progressBarContainer')?.addEventListener('click', function (e) {
  if (!canControl()) return;
  if (!state.ytPlayer || !state.ytReady) return;
  const song = state.queue[state.currentIndex];
  if (!song || song.source !== 'youtube') return;
  const r = this.getBoundingClientRect();
  const pct = (e.clientX - r.left) / r.width;
  const d = state.ytPlayer.getDuration() || 0;
    if (d > 0) state.ytPlayer.seekTo(pct * d);
    reportProgress(false);
});

function roomCodeFromSession() {
  const hash = window.location.hash.replace('#', '').toUpperCase();
  if (/^[A-Z0-9]{6}$/.test(hash)) return hash;
  try {
    const stored = (sessionStorage.getItem('synctunes_active_room') || '').toUpperCase();
    if (/^[A-Z0-9]{6}$/.test(stored)) return stored;
  } catch (e) {}
  return '';
}

async function resumeRoom() {
  const code = roomCodeFromSession();
  if (!code || !hasProfile()) return;
  try {
    const data = await api('api/room.php', {
      action: 'state',
      code,
      userId: state.userId,
      userName: state.userName,
      sinceChat: 0,
    });
    if (data.deleted || !data.room) return;
    enterRoom(data.room);
  } catch (err) {
    try { sessionStorage.removeItem('synctunes_active_room'); } catch (e) {}
  }
}

window.addEventListener('load', () => {
  loadProfile();
  applyProfileUI();
  setPlayerMode(state.playerMode);
  setupRoomList();
  switchMobileTab('player');
  resumeRoom();
  if (window.YT && YT.Player) onYouTubeIframeAPIReady();
});

window.addEventListener('pagehide', () => {
  reportProgress(true);
});

Object.assign(window, {
  createRoom, joinRoom, joinRoomFromList, leaveRoom,
  addSongFromInput, togglePlayPause, skipSong, playPrevious,
  removeFromQueue, banFromQueue, playNow, clearQueue, shuffleQueue,
  toggleShuffle, toggleRepeat, sendChatMessage, setVolume,
  toggleTheme, switchMobileTab, onYouTubeIframeAPIReady,
  saveProfileFromGate, openProfileEditor, closeProfileEditor, saveProfileEdit, setPlayerMode,
  toggleMute, chooseRole, switchSideTab, switchLandingTab,
});

window.onYouTubeIframeAPIReady = onYouTubeIframeAPIReady;
if (window.YT && YT.Player) onYouTubeIframeAPIReady();
