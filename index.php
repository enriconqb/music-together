<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>SyncTunes — Collaborative Music Player</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://www.youtube.com/iframe_api"></script>
<style>
  .queue-scroll::-webkit-scrollbar { width: 6px; }
  .queue-scroll::-webkit-scrollbar-track { background: transparent; }
  .queue-scroll::-webkit-scrollbar-thumb { background: #4a4a6a; border-radius: 3px; }

  .video-container { position: relative; height: clamp(140px, 32vh, 280px); overflow: hidden; border-radius: 12px; background: #000; flex-shrink: 0; }
  body.audio-only .video-container iframe,
  body.audio-only .video-container #yt-player { position: fixed; left: -9999px; top: 0; width: 320px; height: 180px; }
  body.audio-only #audioCover { display: flex; }
  #profileGate:not(.hidden),
  #roleModal:not(.hidden),
  #profileEditor:not(.hidden) { display: flex; }

  @keyframes pulse-dot { 0%,100%{opacity:1} 50%{opacity:.4} }
  .pulse-dot { animation: pulse-dot 1.5s ease-in-out infinite; }

  @keyframes fadeIn { from{opacity:0; transform:translateY(8px)} to{opacity:1; transform:translateY(0)} }
  .fade-in { animation: fadeIn .3s ease-out; }

  .no-scrollbar::-webkit-scrollbar { display: none; }
  .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

  /* ===== MOBILE TAB LAYOUT (below lg) ===== */
  @media (max-width: 1023px) {
    #playerScreen[data-mobile-tab="player"] #tabContentPlayer { display: flex; }
    #playerScreen[data-mobile-tab="player"] #tabContentSidebar { display: none; }

    #playerScreen[data-mobile-tab="queue"] #tabContentPlayer { display: none; }
    #playerScreen[data-mobile-tab="queue"] #tabContentSidebar { display: flex; }
    #playerScreen[data-mobile-tab="queue"] #tabContentQueue { display: flex; }
    #playerScreen[data-mobile-tab="queue"] #tabContentChat { display: none; }

    #playerScreen[data-mobile-tab="chat"] #tabContentPlayer { display: none; }
    #playerScreen[data-mobile-tab="chat"] #tabContentSidebar { display: flex; }
    #playerScreen[data-mobile-tab="chat"] #tabContentQueue { display: none; }
    #playerScreen[data-mobile-tab="chat"] #tabContentChat { display: flex; }
  }

  html, body { height: 100dvh; overflow: hidden; }
  body { display: flex; flex-direction: column; }
  main.app-main { flex: 1; min-height: 0; display: flex; flex-direction: column; overflow: hidden; }
  #landingScreen:not(.hidden),
  #playerScreen:not(.hidden) { flex: 1; min-height: 0; display: flex; flex-direction: column; overflow: hidden; }
  #landingGrid { flex: 1; min-height: 0; display: flex; flex-direction: column; }
  #landingForms { flex-shrink: 0; }
  @media (max-width: 1023px) {
    #landingScreen .landing-hero-mark { display: none; }
    #landingScreen .landing-blurb { display: none; }
    #landingScreen h1 { font-size: 1.25rem; }
    #landingTabs { display: flex; }
    #landingScreen[data-landing-tab="create"] #landingJoin,
    #landingScreen[data-landing-tab="create"] #landingRooms { display: none; }
    #landingScreen[data-landing-tab="join"] #landingCreate,
    #landingScreen[data-landing-tab="join"] #landingRooms { display: none; }
    #landingScreen[data-landing-tab="rooms"] #landingForms { display: none; }
    #landingScreen[data-landing-tab="rooms"] #landingRooms,
    #landingScreen[data-landing-tab="rooms"] #roomListCard { display: flex; flex: 1; min-height: 0; }
    #landingCreate, #landingJoin { min-height: 0; }
    #landingScreen .landing-action { padding-top: 0.85rem; padding-bottom: 0.85rem; }
  }
  @media (min-width: 1024px) {
    #landingTabs { display: none; }
    #landingGrid { display: grid; grid-template-columns: 1fr 400px; gap: 1.5rem; }
    #landingForms { max-height: none; overflow: visible; }
  }
  #roomListCard { min-height: 0; display: flex; flex-direction: column; }
  #roomList { flex: 1; min-height: 0; overflow-y: auto; }
  #playerColumns { flex: 1; min-height: 0; }
  #tabContentPlayer { min-height: 0; }
  #tabContentSidebar { min-height: 0; }
  #tabContentQueue, #tabContentChat { min-height: 0; }
  @media (min-width: 1024px) {
    #playerColumns { display: flex; gap: 1.5rem; }
    #tabContentPlayer { flex: 1; min-width: 0; display: flex; flex-direction: column; overflow-y: auto; }
    #tabContentSidebar { width: 24rem; flex-shrink: 0; display: flex; flex-direction: column; margin-top: 0; }
    #tabContentQueue { flex: 1.15; display: flex; max-height: none; }
    #tabContentChat { flex: 1; display: flex; max-height: none; }
  }
  @media (max-width: 1023px) {
    #playerColumns { display: flex; flex-direction: column; min-height: 0; }
    #tabContentPlayer { flex: 1; flex-direction: column; overflow-y: auto; }
    #tabContentSidebar { flex: 1; flex-direction: column; margin-top: 0; }
    #tabContentQueue, #tabContentChat { flex: 1; max-height: none; }
  }

  /* ===== LIGHT MODE ===== */
  body.light { background-color: #f3f4f6 !important; color: #1f2937; }
  body.light .dark-bg-primary { background-color: #ffffff !important; }
  body.light .dark-bg-secondary { background-color: #f9fafb !important; }
  body.light .dark-bg-tertiary { background-color: #e5e7eb !important; }
  body.light .dark-border { border-color: #e5e7eb !important; }
  body.light .dark-text { color: #1f2937 !important; }
  body.light .dark-text-muted { color: #6b7280 !important; }
  body.light .dark-input { background-color: #ffffff !important; border-color: #d1d5db !important; color: #111827 !important; }
  body.light .dark-header { background-color: rgba(255,255,255,0.95) !important; }
  body.light .dark-hover:hover { background-color: #f3f4f6 !important; }
</style>
</head>
<body class="bg-[#0f0f1a] text-gray-100 h-dvh overflow-hidden font-sans antialiased transition-colors duration-300">

<!-- ================= HEADER ================= -->
<header class="dark-header shrink-0 z-50 bg-[#0f0f1a]/95 backdrop-blur-md border-b dark-border border-[#2a2a3e] transition-colors duration-300">
  <div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-6">
    <div class="flex items-center justify-between h-14 sm:h-16">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 sm:w-9 sm:h-9 bg-gradient-to-br from-violet-500 to-fuchsia-500 rounded-lg flex items-center justify-center">
          <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/></svg>
        </div>
        <span class="text-lg sm:text-xl font-bold bg-gradient-to-r from-violet-400 to-fuchsia-400 bg-clip-text text-transparent">SyncTunes</span>
      </div>

      <div id="roomInfo" class="hidden sm:flex items-center gap-3 text-sm">
        <span id="roomIdDisplay" class="dark-bg-secondary px-3 py-1 bg-[#1a1a2e] rounded-full dark-text-muted text-gray-400 border dark-border border-[#2a2a3e] transition-colors duration-300">No room</span>
        <div id="onlineIndicator" class="hidden items-center gap-1.5 text-green-400">
          <span class="w-2 h-2 bg-green-400 rounded-full pulse-dot"></span>
          <span id="onlineCount" class="text-xs">0 online</span>
        </div>
        <button id="leaveBtn" onclick="leaveRoom()" class="hidden text-xs px-3 py-1 rounded-full bg-red-500/20 text-red-400 border border-red-500/30 hover:bg-red-500/30 transition-colors">Leave</button>
      </div>

      <div class="flex items-center gap-1">
        <span id="roomIdDisplayMobile" class="sm:hidden dark-bg-secondary px-2 py-0.5 bg-[#1a1a2e] rounded-full text-xs dark-text-muted text-gray-400 border dark-border border-[#2a2a3e] transition-colors duration-300">—</span>
        <button type="button" id="profileBtn" onclick="openProfileEditor()" class="hidden text-xs px-3 py-1 rounded-full dark-bg-secondary bg-[#1a1a2e] border dark-border border-[#2a2a3e] dark-text-muted text-gray-300 hover:text-white"></button>
        <button onclick="toggleTheme()" id="themeToggle" class="p-2 dark-text-muted text-gray-400 hover:text-white transition-colors">
          <svg id="sunIcon" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
          <svg id="moonIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
        </button>
      </div>
    </div>
  </div>
</header>

<main class="app-main max-w-7xl mx-auto w-full px-3 sm:px-4 lg:px-6 py-3">

  <div id="profileGate" class="hidden fixed inset-0 z-[90] bg-black/70 items-center justify-center p-4">
    <div class="dark-bg-secondary bg-[#1a1a2e] rounded-2xl p-5 w-full max-w-md border dark-border border-[#2a2a3e]">
      <h2 class="font-semibold mb-2">Nama kamu</h2>
      <p class="text-sm dark-text-muted text-gray-400 mb-3">Perangkat ini belum punya profil. Nama dipakai di room dan chat.</p>
      <input id="profileNameInput" type="text" maxlength="20" placeholder="Nama" class="dark-input w-full px-4 py-2.5 bg-[#0f0f1a] border dark-border border-[#2a2a3e] rounded-xl text-sm placeholder-gray-500 mb-3">
      <button type="button" onclick="saveProfileFromGate()" class="w-full py-2.5 bg-gradient-to-r from-violet-600 to-fuchsia-600 rounded-xl text-sm font-medium text-white">Simpan nama</button>
    </div>
  </div>
  <div id="roleModal" class="hidden fixed inset-0 z-[95] bg-black/75 items-center justify-center p-4">
    <div class="w-full max-w-md">
      <h2 class="text-center text-lg font-semibold mb-1">Masuk sebagai</h2>
      <p class="text-center text-sm text-gray-400 mb-5">Pilihan ini hanya mengatur suara di perangkat ini.</p>
      <div class="grid grid-cols-2 gap-3">
        <button type="button" onclick="chooseRole('speaker')" class="rounded-2xl bg-[#1c1c1c] border border-white/10 px-4 py-6 text-white hover:bg-[#2a2a2a]">
          <svg class="w-8 h-8 mx-auto mb-2" fill="currentColor" viewBox="0 0 24 24"><path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02z"/></svg>
          <span class="block font-semibold">Speaker</span>
          <span class="block text-[11px] text-gray-400 mt-1">Suara nyala</span>
        </button>
        <button type="button" onclick="chooseRole('requester')" class="rounded-2xl bg-[#14532d] border border-green-900 px-4 py-6 text-white hover:bg-[#166534]">
          <svg class="w-8 h-8 mx-auto mb-2" fill="currentColor" viewBox="0 0 24 24"><path d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L19.73 21 21 19.73l-9-9L4.27 3zM12 4L9.91 6.09 12 8.18V4z"/></svg>
          <span class="block font-semibold">Requester</span>
          <span class="block text-[11px] text-green-200/80 mt-1">Suara mati</span>
        </button>
      </div>
    </div>
  </div>
  <div id="profileEditor" class="hidden fixed inset-0 z-[80] bg-black/60 items-center justify-center p-4">
    <div class="dark-bg-secondary bg-[#1a1a2e] rounded-2xl p-5 w-full max-w-sm border dark-border border-[#2a2a3e]">
      <h2 class="font-semibold mb-3">Ubah profil</h2>
      <input id="profileEditInput" type="text" maxlength="20" class="dark-input w-full px-4 py-2.5 bg-[#0f0f1a] border dark-border border-[#2a2a3e] rounded-xl text-sm mb-3">
      <div class="flex gap-2">
        <button type="button" onclick="saveProfileEdit()" class="flex-1 py-2 bg-violet-600 rounded-xl text-sm text-white">Simpan</button>
        <button type="button" onclick="closeProfileEditor()" class="flex-1 py-2 bg-[#2a2a3e] rounded-xl text-sm">Batal</button>
      </div>
    </div>
  </div>

  <!-- ================= LANDING SCREEN ================= -->
  <div id="landingScreen" data-landing-tab="create">
    <div class="text-center mb-3 max-w-5xl mx-auto shrink-0">
      <div class="landing-hero-mark w-16 h-16 sm:w-20 sm:h-20 mx-auto mb-3 sm:mb-4 bg-gradient-to-br from-violet-500 to-fuchsia-500 rounded-2xl flex items-center justify-center shadow-lg shadow-violet-500/20">
        <svg class="w-8 h-8 sm:w-10 sm:h-10 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/></svg>
      </div>
      <h1 class="text-2xl sm:text-3xl font-bold mb-1">Listen Together</h1>
      <p class="landing-blurb dark-text-muted text-gray-400 text-sm">Create a room or join with a code to share music with friends.</p>
    </div>
    <div id="landingTabs" class="shrink-0 mb-3 gap-1 rounded-xl bg-[#1a1a2e] border border-[#2a2a3e] p-1">
      <button type="button" id="landingTabcreate" onclick="switchLandingTab('create')" class="flex-1 py-2.5 rounded-lg text-sm font-medium bg-violet-600 text-white">Buat</button>
      <button type="button" id="landingTabjoin" onclick="switchLandingTab('join')" class="flex-1 py-2.5 rounded-lg text-sm font-medium text-gray-400">Gabung</button>
      <button type="button" id="landingTabrooms" onclick="switchLandingTab('rooms')" class="flex-1 py-2.5 rounded-lg text-sm font-medium text-gray-400">Room</button>
    </div>

    <!-- DESKTOP: 2-col layout | MOBILE: stacked -->
    <div id="landingGrid" class="max-w-5xl mx-auto w-full lg:grid lg:grid-cols-[1fr_400px] lg:gap-6 min-h-0">
      <!-- Left: forms -->
      <div id="landingForms" class="space-y-4">
        <!-- Create Room -->
        <div id="landingCreate" class="dark-bg-secondary bg-[#1a1a2e] rounded-2xl p-5 sm:p-6 border dark-border border-[#2a2a3e] transition-colors duration-300">
          <h2 class="font-semibold mb-3 flex items-center gap-2">
            <svg class="w-5 h-5 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Create Room
          </h2>
          <input id="createRoomName" type="text" placeholder="Your name (optional)" maxlength="20"
            class="dark-input input-focus w-full px-4 py-2.5 bg-[#0f0f1a] border dark-border border-[#2a2a3e] rounded-xl text-sm placeholder-gray-500 mb-3 transition-all">

          <div class="flex items-center gap-2 mb-3">
            <input type="checkbox" id="hostOnlyCheck" checked
              class="w-4 h-4 rounded border-gray-600 bg-[#0f0f1a] text-violet-600 focus:ring-violet-500 cursor-pointer">
            <label for="hostOnlyCheck" class="dark-text text-sm text-gray-300 cursor-pointer">Hanya host yang mengatur pemutaran</label>
          </div>

          <div class="flex items-center gap-2 mb-3">
            <input type="checkbox" id="privateRoomCheck"
              onchange="document.getElementById('passwordField').classList.toggle('hidden', !this.checked)"
              class="w-4 h-4 rounded border-gray-600 bg-[#0f0f1a] text-violet-600 focus:ring-violet-500 cursor-pointer">
            <label for="privateRoomCheck" class="dark-text text-sm text-gray-300 cursor-pointer">Private room (require password)</label>
          </div>

          <div id="passwordField" class="hidden mb-3">
            <input id="roomPassword" type="password" placeholder="Room password" maxlength="20"
              class="dark-input input-focus w-full px-4 py-2.5 bg-[#0f0f1a] border dark-border border-[#2a2a3e] rounded-xl text-sm placeholder-gray-500 transition-all">
          </div>

          <button type="button" onclick="createRoom()"
            class="landing-action w-full py-2.5 bg-gradient-to-r from-violet-600 to-fuchsia-600 hover:from-violet-500 hover:to-fuchsia-500 rounded-xl font-medium text-sm transition-all active:scale-[0.98] text-white">
            Create New Room
          </button>
        </div>

        <!-- Join Room -->
        <div id="landingJoin" class="dark-bg-secondary bg-[#1a1a2e] rounded-2xl p-5 sm:p-6 border dark-border border-[#2a2a3e] transition-colors duration-300">
          <h2 class="font-semibold mb-3 flex items-center gap-2">
            <svg class="w-5 h-5 text-fuchsia-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
            Join by Code
          </h2>
          <div class="flex gap-2 mb-3">
            <input id="joinRoomCode" type="text" placeholder="CODE" maxlength="6"
              class="dark-input input-focus flex-1 px-4 py-2.5 bg-[#0f0f1a] border dark-border border-[#2a2a3e] rounded-xl text-sm placeholder-gray-500 uppercase tracking-widest text-center font-mono transition-all">
            <input id="joinUserName" type="text" placeholder="Your name" maxlength="20"
              class="dark-input input-focus flex-1 px-4 py-2.5 bg-[#0f0f1a] border dark-border border-[#2a2a3e] rounded-xl text-sm placeholder-gray-500 transition-all">
          </div>
          <button type="button" onclick="joinRoom()"
            class="landing-action w-full py-2.5 dark-bg-tertiary bg-[#2a2a3e] hover:bg-[#3a3a4e] rounded-xl font-medium text-sm transition-all active:scale-[0.98]">
            Join Room
          </button>
        </div>
      </div>

      <!-- Right: room list -->
      <div id="landingRooms" class="mt-3 lg:mt-0 min-h-0 flex flex-col">
        <div id="roomListCard" class="dark-bg-secondary bg-[#1a1a2e] rounded-2xl p-5 sm:p-6 border dark-border border-[#2a2a3e] transition-colors duration-300 flex-1 min-h-0">
          <div class="flex items-center justify-between mb-3">
            <h2 class="font-semibold flex items-center gap-2">
              <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
              Available Rooms
            </h2>
            <span id="roomListCount" class="text-xs dark-text-muted text-gray-500">0</span>
          </div>
          <div id="roomList" class="space-y-2 overflow-y-auto queue-scroll pr-1 min-h-0">
            <p class="dark-text-muted text-gray-500 text-sm text-center py-6">Loading rooms...</p>
          </div>
          <div id="fbStatus" class="mt-3 pt-3 border-t dark-border border-[#2a2a3e] text-[10px] dark-text-muted text-gray-500"></div>
        </div>
      </div>
    </div>

    <p class="text-center text-xs dark-text-muted text-gray-600 mt-3 shrink-0">Powered by YouTube &amp; Spotify • Antrian tersinkron di server lokal</p>
  </div>

  <!-- ================= PLAYER SCREEN ================= -->
  <div id="playerScreen" data-mobile-tab="player" class="hidden">

    <!-- Mobile tab bar -->
    <div class="lg:hidden shrink-0 dark-bg-secondary bg-[#1a1a2e] rounded-xl p-1 mb-3 flex gap-1 border dark-border border-[#2a2a3e] transition-colors">
      <button type="button" onclick="switchMobileTab('player')" data-tab-btn="player"
        class="flex-1 py-2 rounded-lg text-xs font-medium transition-colors">▶ Player</button>
      <button type="button" onclick="switchMobileTab('queue')" data-tab-btn="queue"
        class="flex-1 py-2 rounded-lg text-xs font-medium transition-colors">☰ Queue</button>
      <button type="button" onclick="switchMobileTab('chat')" data-tab-btn="chat"
        class="flex-1 py-2 rounded-lg text-xs font-medium transition-colors">💬 Chat</button>
    </div>

    <div id="playerColumns" class="min-h-0">
      <!-- LEFT: Player -->
      <div id="tabContentPlayer" class="min-w-0 min-h-0">
        <!-- Video player -->
        <div class="video-container bg-black mb-3 sm:mb-4 relative">
          <div id="yt-player"></div>
          <div id="audioCover" class="absolute inset-0 z-30 hidden flex-col items-center justify-center bg-[#0f0f1a] px-6 text-center">
            <div id="audioCoverArt" class="w-36 h-36 rounded-2xl mb-4 flex items-center justify-center overflow-hidden bg-gradient-to-br from-violet-600/30 to-fuchsia-600/20 border border-white/10">
              <img id="audioCoverImg" alt="" class="hidden w-full h-full object-cover">
              <svg id="audioCoverIcon" class="w-14 h-14 text-violet-300/80" fill="currentColor" viewBox="0 0 24 24"><path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/></svg>
            </div>
            <p id="audioCoverTitle" class="text-sm font-medium text-gray-200 truncate max-w-full">Belum ada lagu</p>
            <p id="audioCoverHint" class="text-xs text-gray-500 mt-1">Tempel tautan YouTube atau Spotify untuk mulai</p>
          </div>
          <div id="noSongOverlay" class="dark-bg-primary absolute inset-0 flex flex-col items-center justify-center bg-[#0f0f1a] z-10 transition-colors duration-300">
            <svg class="w-16 h-16 dark-text-muted text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>
            <p class="dark-text-muted text-gray-500 text-sm">No song playing</p>
            <p class="text-gray-600 text-xs mt-1">Add a song to the queue to start</p>
          </div>
          <div id="spotifyOverlay" class="dark-bg-primary absolute inset-0 hidden z-20 bg-[#0f0f1a] flex flex-col items-center justify-center transition-colors duration-300">
            <div class="w-full max-w-md mx-auto px-4"><div id="spotifyEmbedContainer"></div></div>
          </div>
        </div>

        <!-- Now playing -->
        <div class="dark-bg-secondary bg-[#1a1a2e] rounded-2xl p-3 sm:p-4 border dark-border border-[#2a2a3e] mb-3 sm:mb-4 transition-colors">
          <div class="flex items-start gap-3">
            <div id="nowPlayingArt" class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl bg-gradient-to-br from-violet-600 to-fuchsia-600 flex items-center justify-center flex-shrink-0">
              <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/></svg>
            </div>
            <div class="flex-1 min-w-0">
              <h3 id="nowPlayingTitle" class="font-semibold text-sm sm:text-base truncate">Not playing</h3>
              <p id="nowPlayingArtist" class="dark-text-muted text-gray-400 text-xs sm:text-sm truncate">—</p>
            <div class="flex items-center gap-2 mt-1">
              <div class="flex rounded-full border dark-border border-[#2a2a3e] overflow-hidden text-[10px]">
                <button type="button" id="modeAudioBtn" onclick="setPlayerMode('audio')" class="px-2.5 py-1">Audio</button>
                <button type="button" id="modeVideoBtn" onclick="setPlayerMode('video')" class="px-2.5 py-1">Video</button>
              </div>
              <span id="nowPlayingSource" class="dark-bg-tertiary text-[10px] px-1.5 py-0.5 rounded bg-[#2a2a3e] dark-text-muted text-gray-400">—</span>
              <span id="nowPlayingAddedBy" class="text-[10px] dark-text-muted text-gray-500"></span>
              <span id="hostLockLabel" class="text-[10px] text-amber-300/90"></span>
            </div>
            </div>
          </div>
          <div class="mt-3">
            <div class="flex items-center gap-2 text-[10px] sm:text-xs dark-text-muted text-gray-500 mb-1">
              <span id="currentTime">0:00</span>
              <div class="flex-1 h-1 dark-bg-tertiary bg-[#2a2a3e] rounded-full overflow-hidden cursor-pointer" id="progressBarContainer">
                <div id="progressBar" class="h-full bg-gradient-to-r from-violet-500 to-fuchsia-500 rounded-full transition-all duration-200" style="width: 0%"></div>
              </div>
              <span id="totalTime">0:00</span>
            </div>
          </div>
        </div>

        <!-- Controls -->
        <div class="dark-bg-secondary bg-[#1a1a2e] rounded-2xl p-3 sm:p-4 border dark-border border-[#2a2a3e] mb-3 sm:mb-4 transition-colors">
          <div class="flex items-center justify-center gap-3 sm:gap-5 flex-wrap">
            <button type="button" onclick="shuffleQueue()" id="shuffleBtn" data-host-control class="p-2 dark-text-muted text-gray-400 hover:text-white transition-colors active:scale-90" title="Acak antrian">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h4l4 8 4-8h4M4 20h4l4-8m0 0l4 8h4"/></svg>
            </button>
            <button type="button" onclick="playPrevious()" data-host-control class="p-2 dark-text text-gray-300 hover:text-white transition-colors active:scale-90" title="Previous">
              <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M6 6h2v12H6zm3.5 6l8.5 6V6z"/></svg>
            </button>
            <button type="button" onclick="togglePlayPause()" id="playPauseBtn" data-host-control class="w-12 h-12 sm:w-14 sm:h-14 bg-gradient-to-br from-violet-500 to-fuchsia-500 rounded-full flex items-center justify-center shadow-lg shadow-violet-500/30 hover:shadow-violet-500/50 transition-all active:scale-95">
              <svg id="playIcon" class="w-6 h-6 sm:w-7 sm:h-7 text-white ml-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
              <svg id="pauseIcon" class="w-6 h-6 sm:w-7 sm:h-7 text-white hidden" fill="currentColor" viewBox="0 0 24 24"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
            </button>
            <button type="button" onclick="skipSong()" data-host-control class="p-2 dark-text text-gray-300 hover:text-white transition-colors active:scale-90" title="Next">
              <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M6 18l8.5-6L6 6v12zM16 6v12h2V6h-2z"/></svg>
            </button>
            <button type="button" onclick="toggleRepeat()" id="repeatBtn" class="p-2 dark-text-muted text-gray-400 hover:text-white transition-colors active:scale-90" title="Repeat">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            </button>
            <button type="button" onclick="toggleMute()" id="muteBtn" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium bg-[#1a1a2e] border border-[#3a3a4e] text-gray-300 hover:text-white" title="Mute perangkat ini">
              <svg id="muteIconOff" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02z"/></svg>
              <svg id="muteIconOn" class="w-4 h-4 hidden" fill="currentColor" viewBox="0 0 24 24"><path d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L19.73 21 21 19.73l-9-9L4.27 3zM12 4L9.91 6.09 12 8.18V4z"/></svg>
              <span id="muteLabel">Mute</span>
            </button>
            <div class="hidden sm:flex items-center gap-2 ml-2">
              <svg class="w-4 h-4 dark-text-muted text-gray-400" fill="currentColor" viewBox="0 0 24 24"><path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/></svg>
              <input type="range" min="0" max="100" value="80" oninput="setVolume(this.value)"
                class="w-16 sm:w-20 h-1 dark-bg-tertiary bg-[#2a2a3e] rounded-full appearance-none cursor-pointer accent-violet-500">
            </div>
          </div>
        </div>

        <div id="peopleCard" class="dark-bg-secondary bg-[#1a1a2e] rounded-2xl p-3 sm:p-4 border dark-border border-[#2a2a3e] mb-3 sm:mb-4 shrink-0">
          <h3 class="font-semibold text-sm mb-2">Di room ini</h3>
          <div id="peopleList" class="space-y-1.5"></div>
        </div>

        <!-- Add song -->
        <div id="addSongPanel" class="dark-bg-secondary bg-[#1a1a2e] rounded-2xl p-3 sm:p-4 border dark-border border-[#2a2a3e] transition-colors">
          <h3 class="font-semibold text-sm mb-3 flex items-center gap-2">
            <svg class="w-4 h-4 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add to Queue
          </h3>
          <div class="flex gap-2">
            <input id="songUrlInput" type="text" placeholder="Paste YouTube or Spotify link..."
              onkeydown="if(event.key==='Enter') addSongFromInput()"
              class="dark-input input-focus flex-1 px-3 sm:px-4 py-2.5 bg-[#0f0f1a] border dark-border border-[#2a2a3e] rounded-xl text-sm placeholder-gray-500 min-w-0 transition-all">
            <button type="button" onclick="addSongFromInput()"
              class="px-4 sm:px-5 py-2.5 bg-gradient-to-r from-violet-600 to-fuchsia-600 hover:from-violet-500 hover:to-fuchsia-500 rounded-xl font-medium text-sm transition-all active:scale-95 flex-shrink-0 text-white">
              Add
            </button>
          </div>
          <p class="text-[10px] dark-text-muted text-gray-600 mt-2">Supports YouTube links and Spotify tracks/playlists</p>
        </div>
      </div>

      <!-- RIGHT: sidebar (queue + chat) -->
      <div id="tabContentSidebar" class="min-h-0">
        <!-- Queue -->
        <div id="tabContentQueue" class="dark-bg-secondary bg-[#1a1a2e] rounded-2xl border dark-border border-[#2a2a3e] flex flex-col min-h-0 transition-colors">
          <div class="flex items-center justify-between p-3 sm:p-4 border-b dark-border border-[#2a2a3e]">
            <h3 class="font-semibold text-sm flex items-center gap-2">
              <svg class="w-4 h-4 text-fuchsia-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
              Queue
            </h3>
            <span id="queueCount" class="text-xs dark-text-muted text-gray-500">0 songs</span>
          </div>
          <div class="px-3 pt-2">
            <input id="queueSearch" type="text" placeholder="Cari judul..." maxlength="80" oninput="updateQueueUI()"
              class="dark-input input-focus w-full px-3 py-1.5 bg-[#0f0f1a] border dark-border border-[#2a2a3e] rounded-lg text-xs placeholder-gray-500">
          </div>
          <div id="queueList" class="queue-scroll flex-1 overflow-y-auto p-2 space-y-1 min-h-0"></div>
          <div class="p-2 border-t dark-border border-[#2a2a3e] flex gap-2">
            <button type="button" id="clearQueueBtn" onclick="clearQueue()" class="flex-1 py-2 text-xs dark-text-muted text-gray-400 hover:text-red-400 dark-bg-primary bg-[#0f0f1a] rounded-lg transition-colors">Clear All</button>
            <button type="button" id="shuffleQueueBtn" onclick="shuffleQueue()" class="flex-1 py-2 text-xs dark-text-muted text-gray-400 hover:text-white dark-bg-primary bg-[#0f0f1a] rounded-lg transition-colors">Shuffle</button>
          </div>
        </div>

        <!-- Chat -->
        <div id="tabContentChat" class="dark-bg-secondary bg-[#1a1a2e] rounded-2xl border dark-border border-[#2a2a3e] flex flex-col mt-3 min-h-0 transition-colors">
          <div class="flex items-center gap-1 p-2 border-b dark-border border-[#2a2a3e]">
            <button type="button" id="tabLogBtn" onclick="switchSideTab('log')" class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-violet-600 text-white">Log</button>
            <button type="button" id="tabChatBtn" onclick="switchSideTab('chat')" class="px-3 py-1.5 text-xs font-semibold rounded-lg text-gray-400">Chat</button>
          </div>
          <div id="logList" class="flex-1 overflow-y-auto p-3 space-y-1 min-h-0 no-scrollbar">
            <div class="text-center text-xs dark-text-muted text-gray-600 py-4">Belum ada aktivitas</div>
          </div>
          <div id="chatPane" class="flex flex-1 flex-col min-h-0" style="display:none">
          <div id="chatMessages" class="flex-1 overflow-y-auto p-3 space-y-2 min-h-0 no-scrollbar">
            <div class="text-center text-xs dark-text-muted text-gray-600 py-4">Welcome! Say hi 👋</div>
          </div>
          <div class="p-2 border-t dark-border border-[#2a2a3e]">
            <div class="flex gap-2">
              <input id="chatInput" type="text" placeholder="Type a message..." maxlength="200"
                onkeydown="if(event.key==='Enter') sendChatMessage()"
                class="dark-input input-focus flex-1 px-3 py-2 bg-[#0f0f1a] border dark-border border-[#2a2a3e] rounded-xl text-sm placeholder-gray-500 min-w-0 transition-all">
              <button type="button" onclick="sendChatMessage()" class="px-3 py-2 bg-violet-600 hover:bg-violet-500 rounded-xl transition-colors active:scale-95 flex-shrink-0">
                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
              </button>
            </div>
          </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<script src="assets/player.js?v=14"></script>
</body>
</html>
