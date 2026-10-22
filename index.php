<!DOCTYPE html>
<html class="dark" lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SyncTunes</title>
<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@500;600&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script id="tailwind-config">
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          "colors": {
            "on-tertiary-fixed-variant": "#004c69",
            "on-primary-container": "#24008c",
            "inverse-on-surface": "#2b3041",
            "on-surface": "#dee1f8",
            "on-secondary": "#2f1e7c",
            "primary-fixed-dim": "#c7bfff",
            "surface-bright": "#34394a",
            "on-tertiary-container": "#002d40",
            "on-primary": "#2a039d",
            "secondary-container": "#483a96",
            "on-secondary-fixed": "#190064",
            "on-surface-variant": "#c8c4d6",
            "error": "#ffb4ab",
            "tertiary-container": "#009bd1",
            "secondary-fixed-dim": "#c8bfff",
            "on-primary-fixed": "#170065",
            "on-error": "#690005",
            "on-primary-fixed-variant": "#422db2",
            "primary-fixed": "#e4dfff",
            "surface-container": "#1a1f30",
            "surface-tint": "#c7bfff",
            "primary-container": "#8e7fff",
            "on-background": "#dee1f8",
            "inverse-primary": "#5a49cb",
            "surface-variant": "#2f3446",
            "on-secondary-container": "#b9afff",
            "inverse-surface": "#dee1f8",
            "surface-container-highest": "#2f3446",
            "on-secondary-fixed-variant": "#463893",
            "surface-container-low": "#161b2b",
            "tertiary": "#7bd0ff",
            "tertiary-fixed": "#c4e7ff",
            "secondary": "#c8bfff",
            "surface-dim": "#0e1323",
            "on-tertiary-fixed": "#001e2c",
            "on-tertiary": "#00354a",
            "error-container": "#93000a",
            "on-error-container": "#ffdad6",
            "primary": "#c7bfff",
            "outline": "#928f9f",
            "background": "#0e1323",
            "tertiary-fixed-dim": "#7bd0ff",
            "surface": "#0e1323",
            "outline-variant": "#474554",
            "surface-container-high": "#24293a",
            "secondary": "#c8bfff",
            "surface-container-lowest": "#080d1d"
          },
          "borderRadius": {
            "DEFAULT": "0.25rem",
            "lg": "0.5rem",
            "xl": "0.75rem",
            "2xl": "1rem",
            "full": "9999px"
          },
          "spacing": {
            "space-sm": "0.75rem",
            "margin": "1rem",
            "margin-desktop": "1.5rem",
            "space-lg": "1.5rem",
            "space-xl": "2rem",
            "space-md": "1rem",
            "space-2xl": "3rem",
            "gutter": "1rem",
            "space-xs": "0.5rem",
            "space-xxs": "0.25rem",
            "gutter-desktop": "1.25rem"
          },
          "fontFamily": {
            "headline-md": ["Plus Jakarta Sans"],
            "headline-lg": ["Plus Jakarta Sans"],
            "body-md": ["Plus Jakarta Sans"],
            "headline-xl": ["Plus Jakarta Sans"],
            "label-code": ["JetBrains Mono"],
            "title-sm": ["Plus Jakarta Sans"],
            "label-timer": ["JetBrains Mono"],
            "body-sm": ["Plus Jakarta Sans"],
            "label-badge": ["Plus Jakarta Sans"]
          },
          "fontSize": {
            "headline-md": ["1.25rem", { "lineHeight": "1.75rem", "letterSpacing": "-0.01em", "fontWeight": "600" }],
            "headline-lg": ["1.5rem", { "lineHeight": "2rem", "letterSpacing": "-0.02em", "fontWeight": "600" }],
            "body-md": ["0.875rem", { "lineHeight": "1.375rem", "letterSpacing": "0em", "fontWeight": "400" }],
            "headline-xl": ["2rem", { "lineHeight": "2.5rem", "letterSpacing": "-0.03em", "fontWeight": "700" }],
            "label-code": ["1.125rem", { "lineHeight": "1.5rem", "letterSpacing": "0.15em", "fontWeight": "600" }],
            "title-sm": ["1rem", { "lineHeight": "1.5rem", "letterSpacing": "-0.01em", "fontWeight": "600" }],
            "label-timer": ["0.8125rem", { "lineHeight": "1rem", "letterSpacing": "0.02em", "fontWeight": "500" }],
            "body-sm": ["0.75rem", { "lineHeight": "1.125rem", "letterSpacing": "0.01em", "fontWeight": "400" }],
            "label-badge": ["0.6875rem", { "lineHeight": "0.875rem", "letterSpacing": "0.06em", "fontWeight": "700" }]
          }
        }
      }
    }
  </script>
<style>
    @keyframes subtlePulse {
      0%, 100% { opacity: 1; transform: scale(1); }
      50% { opacity: 0.75; transform: scale(1.04); }
    }
    @keyframes eqBar1 { 0%, 100% { height: 6px; } 50% { height: 22px; } }
    @keyframes eqBar2 { 0%, 100% { height: 18px; } 50% { height: 9px; } }
    @keyframes eqBar3 { 0%, 100% { height: 12px; } 50% { height: 26px; } }
    @keyframes eqBar4 { 0%, 100% { height: 24px; } 50% { height: 8px; } }
    @keyframes eqBar5 { 0%, 100% { height: 10px; } 50% { height: 20px; } }
    .eq-1 { animation: eqBar1 1.1s ease-in-out infinite; }
    .eq-2 { animation: eqBar2 0.85s ease-in-out infinite; }
    .eq-3 { animation: eqBar3 1.25s ease-in-out infinite; }
    .eq-4 { animation: eqBar4 0.95s ease-in-out infinite; }
    .eq-5 { animation: eqBar5 1.15s ease-in-out infinite; }
    
    /* Custom scrollbar for lobby list */
    .custom-scrollbar::-webkit-scrollbar {
      width: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
      background: transparent;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
      background: #2f3446;
      border-radius: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
      background: #474554;
    }
  </style>
<style>
  html,body{height:100dvh;overflow:hidden}
  .material-symbols-outlined{font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24;vertical-align:middle}
  body.audio-only .video-container iframe, body.audio-only .video-container #yt-player{position:fixed;left:-9999px;top:0;width:320px;height:180px}
  body.audio-only #audioCover{display:flex}
  #profileGate:not(.hidden),#roleModal:not(.hidden),#profileEditor:not(.hidden){display:flex}
  @media (max-width:1023px){
    #playerScreen[data-mobile-tab="player"] #tabContentSidebar{display:none}
    #playerScreen[data-mobile-tab="queue"] #tabContentPlayer,
    #playerScreen[data-mobile-tab="queue"] #sideBottom{display:none}
    #playerScreen[data-mobile-tab="chat"] #tabContentPlayer,
    #playerScreen[data-mobile-tab="chat"] #tabContentQueue{display:none}
    #playerScreen[data-mobile-tab="queue"] #tabContentSidebar,
    #playerScreen[data-mobile-tab="chat"] #tabContentSidebar{display:flex;width:100%}
  }
</style>
<script src="https://www.youtube.com/iframe_api"></script>
</head>
<body class="bg-surface text-on-surface antialiased h-[100dvh] overflow-hidden flex flex-col font-sans">

<header class="w-full h-14 px-6 flex justify-between items-center z-50 shrink-0 bg-surface-container-lowest border-b border-outline-variant/30">
<div class="flex items-center gap-6">
<!-- Brand Logo -->
<a class="text-headline-md font-headline-md font-bold tracking-tight text-on-surface flex items-center gap-2" href="#">
<span class="w-8 h-8 rounded-xl bg-primary-container/20 border border-primary-container/40 flex items-center justify-center text-primary shadow-[0_0_12px_rgba(142,127,255,0.25)]">
<span class="material-symbols-outlined text-[18px]">graphic_eq</span>
</span>
<span>SyncTunes</span>
</a>
<!-- Status Badges Cluster -->
<div class="hidden md:flex items-center gap-2 pl-2 border-l border-outline-variant/40">
<span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-surface-container-high border border-outline-variant/40 text-on-surface-variant font-label-badge text-label-badge">
<span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
          v2.4 Live Sync
        </span>
<span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-surface-container-low border border-outline-variant/30 text-on-surface-variant font-label-badge text-label-badge">
<span class="material-symbols-outlined text-[13px] text-tertiary">group</span>
          <span id="listenerBadge">0 pendengar aktif</span>
        </span>
</div>
<!-- Navigation Links from Shared Components -->
<nav class="hidden lg:flex items-center gap-5 ml-4">
<button type="button" id="navExplore" onclick="showAppPage('explore')" class="text-primary font-semibold border-b-2 border-primary pb-1 font-body-sm text-body-sm">Eksplor</button>
<button type="button" id="navFitur" onclick="focusCreate()" class="text-on-surface-variant hover:text-on-surface transition-colors pb-1 font-body-sm text-body-sm">Fitur</button>
<button type="button" id="navDocs" onclick="showAppPage('docs')" class="text-on-surface-variant hover:text-on-surface transition-colors pb-1 font-body-sm text-body-sm">Dokumentasi</button>
</nav>
</div>
<!-- Trailing Actions Cluster -->
<div class="flex items-center gap-3"><div id="roomInfo" class="items-center gap-2 mr-1" style="display:none"><span id="roomIdDisplay" class="font-label-code text-[11px] text-tertiary px-2 py-1 rounded-full bg-surface-container-high border border-outline-variant/40">—</span><span id="roomIdDisplayMobile" class="hidden"></span><span id="onlineIndicator" class="items-center gap-1 text-emerald-400 text-[11px]" style="display:none"><span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span><span id="onlineCount">0 online</span></span><button id="leaveBtn" type="button" onclick="leaveRoom()" class="hidden px-3 py-1 rounded-lg bg-error-container text-on-error-container text-xs font-semibold">Keluar</button></div><button type="button" id="profileBtn" onclick="openProfileEditor()" class="hidden"></button><button type="button" id="themeToggle" onclick="toggleTheme()" class="hidden"><span id="sunIcon" class="hidden"></span><span id="moonIcon"></span></button>
<div class="flex items-center gap-1 text-on-surface-variant mr-1">
<button class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-surface-container-high/60 transition-colors" title="Audio Buffer Status">
<span class="material-symbols-outlined text-[19px]">graphic_eq</span>
</button>
<button class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-surface-container-high/60 transition-colors" title="Bantuan">
<span class="material-symbols-outlined text-[19px]">help_outline</span>
</button>
<button class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-surface-container-high/60 transition-colors relative" title="Notifikasi">
<span class="material-symbols-outlined text-[19px]">notifications</span>
<span class="absolute top-1.5 right-1.5 w-1.5 h-1.5 bg-primary rounded-full"></span>
</button>
</div>
<button type="button" onclick="openProfileEditor()" class="px-3 py-1.5 rounded-xl text-on-surface font-body-sm text-body-sm font-semibold hover:bg-surface-container-high/60 active:scale-[0.98] transition-all">
        Masuk
      </button>
<button type="button" onclick="focusCreate()" class="px-3.5 py-1.5 rounded-xl bg-primary-container text-on-primary-container font-body-sm text-body-sm font-semibold hover:bg-primary-container/90 active:scale-[0.98] shadow-[0_0_16px_rgba(142,127,255,0.35)] transition-all flex items-center gap-1.5">
<span class="material-symbols-outlined text-[16px]">add</span>
        Buat Room
      </button>
<!-- Profile Avatar -->
<div class="pl-2 border-l border-outline-variant/30 flex items-center">
<div class="w-8 h-8 rounded-full overflow-hidden border border-outline-variant/60 ring-1 ring-primary/20">
<img alt="Avatar profil pengguna" class="w-full h-full object-cover" data-alt="A cinematic, moody portrait of a young Indonesian audio engineer with neon purple rim lighting against a pitch black late night studio interior." src="https://lh3.googleusercontent.com/aida-public/AB6AXuATDJQ5yQqMq024eatkG3NzVgFb_609LAlAK5Bf1HJ2Xt7NIg-Ve0410l3uA9eN9P87mQEyoGnGrrPUI91lyG7kz4B-5HeXOa2FpcRgvm65yfkLRzZ43DNGdfKhAXx4yis57xD3iNoKBt1jf0Tlr6JYME41MiUNcLcy9htc25CAwmYZG4N45VaqVT2HOD4BxZ4IFZDGEH6vB2zMdSeP3vW34WS2UjUXjuaL7r4tmmD5dMrLK6jrHuL5HA2txqy8AHadWwaMCfJIEA"/>
</div>
</div>
</div>
</header>

<div id="profileGate" class="hidden fixed inset-0 z-[90] bg-black/70 items-center justify-center p-4">
  <div class="bg-[#161b2b] rounded-2xl p-6 w-full max-w-md border border-white/10">
    <h2 class="font-semibold mb-1">Nama kamu</h2>
    <p class="text-sm text-on-surface-variant mb-3">Nama ini akan tampil di daftar pendengar dan antrian lagu room.</p>
    <input id="profileNameInput" type="text" maxlength="20" placeholder="Nama" class="w-full h-11 px-3 rounded-xl bg-surface-container-lowest border border-outline-variant/40 mb-3 text-sm">
    <button type="button" onclick="saveProfileFromGate()" class="w-full h-11 rounded-xl bg-[#8e7fff] text-white font-semibold">Simpan nama</button>
  </div>
</div>
<div id="roleModal" class="hidden fixed inset-0 z-[95] bg-black/75 items-center justify-center p-4">
  <div class="w-full max-w-lg bg-[#161b2b] rounded-2xl p-6 border border-white/10">
    <h2 class="text-center text-lg font-semibold mb-1">Masuk sebagai</h2>
    <p class="text-center text-sm text-on-surface-variant mb-5">Pilihan ini hanya mengatur suara di perangkat ini.</p>
    <div class="grid grid-cols-2 gap-3">
      <button type="button" onclick="chooseRole('speaker')" class="rounded-2xl bg-surface-container border border-[#8e7fff] px-4 py-6">
        <span class="material-symbols-outlined text-3xl text-primary">volume_up</span>
        <span class="block font-semibold mt-2">Speaker</span>
        <span class="block text-[11px] text-on-surface-variant">suara nyala</span>
      </button>
      <button type="button" onclick="chooseRole('requester')" class="rounded-2xl bg-surface-container border border-outline-variant/40 px-4 py-6">
        <span class="material-symbols-outlined text-3xl">volume_off</span>
        <span class="block font-semibold mt-2">Requester</span>
        <span class="block text-[11px] text-on-surface-variant">suara mati</span>
      </button>
    </div>
  </div>
</div>
<div id="profileEditor" class="hidden fixed inset-0 z-[80] bg-black/60 items-center justify-center p-4">
  <div class="bg-[#161b2b] rounded-2xl p-5 w-full max-w-sm border border-white/10">
    <h2 class="font-semibold mb-3">Ubah profil</h2>
    <input id="profileEditInput" type="text" maxlength="20" class="w-full h-11 px-3 rounded-xl bg-surface-container-lowest border border-outline-variant/40 mb-3 text-sm">
    <div class="flex gap-2">
      <button type="button" onclick="saveProfileEdit()" class="flex-1 py-2 bg-[#8e7fff] text-[#170065] rounded-xl text-sm font-semibold">Simpan</button>
      <button type="button" onclick="closeProfileEditor()" class="flex-1 py-2 bg-surface-container-high rounded-xl text-sm">Batal</button>
    </div>
  </div>
</div>

<main class="flex-1 min-h-0 overflow-hidden">
<div id="exploreScreen" class="h-full min-h-0 max-w-[1600px] w-full mx-auto px-6 py-4 grid grid-cols-12 gap-5 overflow-hidden">
<!-- ============================================== -->
<!-- LEFT COLUMN: Hero & Product Reality (5 cols)   -->
<!-- ============================================== -->
<section class="col-span-12 lg:col-span-5 flex flex-col justify-center gap-8 h-full overflow-y-auto py-1">
<!-- Top Group: Value Proposition -->
<div class="space-y-4">
<!-- Big Prominent Wordmark with glowing violet dot -->
<div class="flex items-center gap-2">
<span class="px-2.5 py-1 rounded-md bg-surface-container-high/80 border border-outline-variant/40 font-label-badge text-label-badge text-primary-fixed uppercase tracking-wider">
            SYNCHRONIZED AUDIO V2
          </span>
<span class="flex items-center gap-1 text-label-timer font-label-timer text-on-surface-variant">
<span class="w-2 h-2 rounded-full bg-emerald-400"></span>
            Zero Audio Drift
          </span>
</div>
<div class="space-y-2">
<h1 class="text-headline-xl font-headline-xl text-on-surface tracking-tight leading-tight">
            Dengar lagu yang sama, di detik yang sama<span class="text-primary inline-block animate-pulse">.</span>
</h1>
<p class="text-body-md font-body-md text-on-surface-variant max-w-lg leading-relaxed">
            Sinkronisasi playback sub-milidetik untuk mendengarkan lagu dan menonton video bareng teman tanpa jeda. Tanpa latensi, tanpa lag transmisi.
          </p>
</div>
<!-- Three Feature Chips / Badges -->
<div class="flex flex-wrap gap-2 pt-1">
<div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-surface-container-low border border-outline-variant/40 hover:border-primary/40 transition-colors">
<span class="w-5 h-5 rounded-md bg-red-500/20 text-red-400 flex items-center justify-center">
<span class="material-symbols-outlined text-[14px]">smart_display</span>
</span>
<span class="text-on-surface font-body-sm text-body-sm font-medium">YouTube Integration</span>
</div>
<div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-surface-container-low border border-outline-variant/40 hover:border-primary/40 transition-colors">
<span class="w-5 h-5 rounded-md bg-primary-container/20 text-primary flex items-center justify-center">
<span class="material-symbols-outlined text-[14px]">queue_music</span>
</span>
<span class="text-on-surface font-body-sm text-body-sm font-medium">Antrian Bersama</span>
</div>
<div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-surface-container-low border border-outline-variant/40 hover:border-primary/40 transition-colors">
<span class="w-5 h-5 rounded-md bg-tertiary-container/30 text-tertiary flex items-center justify-center">
<span class="material-symbols-outlined text-[14px]">devices</span>
</span>
<span class="text-on-surface font-body-sm text-body-sm font-medium">Sinkron Antar Perangkat</span>
</div>
</div>
</div>
<!-- Live Synced Now Playing Widget / Audio Visualizer Box -->
<div id="liveRoomCard" class="hidden p-4 rounded-2xl bg-surface-container-low border border-outline-variant/50 relative overflow-hidden shadow-2xl backdrop-blur-sm group hover:border-primary/50 transition-all duration-300">
<!-- Ambient subtle glow behind album -->
<div class="absolute -top-12 -left-12 w-48 h-48 bg-primary/10 rounded-full blur-3xl pointer-events-none"></div>
<!-- Header of Widget -->
<div class="flex items-center justify-between mb-3.5 relative z-10">
<div class="flex items-center gap-2">
<span class="w-2 h-2 rounded-full bg-primary animate-ping"></span>
<span class="font-label-badge text-label-badge uppercase tracking-wider text-primary font-bold">
              <span id="liveRoomLabel">Room Sedang Berlangsung</span>
            </span>
</div>
<span id="liveSynced" class="px-2 py-0.5 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 font-label-timer text-label-timer items-center gap-1" style="display:none">
<span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
            SYNCED
          </span>
</div>
<!-- Track Info + Album Art Canvas -->
<div class="flex items-center gap-4 relative z-10 mb-3.5">
<div class="relative w-16 h-16 rounded-xl overflow-hidden shrink-0 border border-outline-variant/60 shadow-lg">
<img id="liveCoverImg" alt="" class="hidden w-full h-full object-cover">
<div id="liveCoverIcon" class="absolute inset-0 bg-surface-container-high flex items-center justify-center">
<span class="material-symbols-outlined text-primary text-[24px]">music_note</span>
</div>
</div>
<div class="flex-1 min-w-0">
<div class="flex items-center justify-between">
<h3 class="font-title-sm text-title-sm text-on-surface font-semibold truncate group-hover:text-primary transition-colors">
                <span id="liveRoomTitle">Shinunoga E-Wa</span>
              </h3>
<!-- Micro Animated Equalizer -->
<div class="flex items-end gap-1 h-6 px-1.5 py-0.5 rounded bg-surface-container-highest/60">
<span class="w-1 bg-primary rounded-full eq-1"></span>
<span class="w-1 bg-primary rounded-full eq-2"></span>
<span class="w-1 bg-primary rounded-full eq-3"></span>
<span class="w-1 bg-primary rounded-full eq-4"></span>
<span class="w-1 bg-primary rounded-full eq-5"></span>
</div>
</div>
<p class="font-body-sm text-body-sm text-on-surface-variant truncate">
              <span id="liveRoomMeta">Fujii Kaze • HELP EVER HURT NEVER</span>
            </p>
<div id="liveSource" class="hidden font-label-badge text-label-badge mt-1 text-on-surface-variant"></div>
</div>
</div>
<!-- Timeline Scrubbing Bar -->
<div id="liveProgress" class="hidden space-y-1 relative z-10 mb-3">
<div class="w-full h-1.5 rounded-full bg-surface-container-highest overflow-hidden relative">
<div id="liveProgressBar" class="h-full bg-gradient-to-r from-primary to-primary-container rounded-full" style="width: 0%;"></div>
</div>
<div class="flex justify-between items-center font-label-timer text-label-timer text-on-surface-variant">
<span id="livePos">0:00</span>
<span id="liveDur" class="text-tertiary">0:00</span>
</div>
</div>
<!-- Participants Avatar Cluster & Volume Slider -->
<div class="pt-2 border-t border-outline-variant/30 flex items-center justify-between relative z-10">
<div class="flex items-center gap-2">
<div id="liveAvatars" class="hidden flex -space-x-2 overflow-hidden"></div>
<span class="text-body-sm font-body-sm text-on-surface-variant text-[11px]"><span id="liveRoomPeople">6 teman sinkron</span></span>
</div>
<!-- Volume micro slider -->
<div class="hidden items-center gap-1.5 text-on-surface-variant">
<span class="material-symbols-outlined text-[15px]">volume_up</span>
<div class="w-16 h-1 rounded-full bg-surface-container-highest relative">
<div class="w-11 h-1 rounded-full bg-primary"></div>
</div>
</div>
</div>
</div>
<!-- Latency Footnote / Tech Specs Pill -->
<div class="pt-2">
<div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-surface-container-lowest border border-outline-variant/30 text-on-surface-variant font-label-timer text-label-timer">
<span class="material-symbols-outlined text-[14px] text-tertiary">bolt</span>
<span>WebRTC Mesh Audio Engine</span>
<span class="text-outline-variant">•</span>
<span class="text-emerald-400 font-semibold">&lt; 25ms drift</span>
<span class="text-outline-variant">•</span>
<span>End-to-End Encrypted</span>
</div>
</div>
</section>
<!-- ============================================== -->
<!-- RIGHT COLUMN: Stacked Functional Cards (7 cols)-->
<!-- ============================================== -->
<section class="col-span-12 lg:col-span-7 flex flex-col gap-3 h-full overflow-hidden">
<!-- CARD 1: Profile Identity Chip -->
<div class="p-3 rounded-2xl bg-surface-container-low border border-outline-variant/40 flex items-center justify-between shrink-0 shadow-sm">
<div class="flex items-center gap-3">
<div class="relative">
<div class="w-10 h-10 rounded-full overflow-hidden ring-2 ring-primary/40">
<img alt="Avatar Rian Pratama" class="w-full h-full object-cover" data-alt="Close up photographic avatar of a creative designer with glasses and subtle warm rim lighting." src="https://lh3.googleusercontent.com/aida-public/AB6AXuA-DUZpqpJtCbTRVyUnrUCG8CHSk6fMaqgGKXJvesYX8Ykzg4DsnM2-Y1YE5XEsBGJRu9znFs-qkoOSKOe7NHXOPtGdSH56puaSlzjXr8vUxFFFYow-4yF4v17mfF2Ykt5W46kpmH62T-9cIRLxS3pFKsSmMQFCoHjsKTGudLGbVSvh1RCkFT7j44Vnxgs57RVxu2KdqbHpfkZOxjHepkYpGwn1_zOvx7i2JFjDi6wLeLwf1ajcH67ePWwN1-zmrI35RlXhZddIdQ"/>
</div>
<span class="absolute bottom-0 right-0 w-3 h-3 bg-emerald-400 border-2 border-surface-container-low rounded-full"></span>
</div>
<div>
<div class="flex items-center gap-2">
<label class="text-on-surface-variant font-body-sm text-[11px] uppercase tracking-wider">Identitas Kamu</label>
<span id="hostBadge" class="hidden font-label-badge text-[10px] px-1.5 py-0.2 rounded bg-primary-container/20 text-primary font-semibold">Tuan Rumah</span>
</div>
<div class="flex items-center gap-1.5 mt-0.5">
<input id="profileNameLive" onblur="saveExploreName()" maxlength="20" class="bg-transparent border-0 p-0 text-on-surface font-title-sm text-title-sm font-semibold focus:ring-0 focus:outline-none w-36 hover:underline cursor-pointer" type="text" value="Rian Pratama"/>
<span class="material-symbols-outlined text-[15px] text-outline cursor-pointer hover:text-on-surface">edit</span>
</div>
</div>
</div>
<div class="flex items-center gap-3">
<div class="text-right">
<span class="text-label-timer font-label-timer text-emerald-400 flex items-center gap-1 justify-end">
<span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> Online
            </span>
<span class="text-on-surface-variant text-[11px] font-body-sm">ID: <span id="profileUserId">—</span></span>
</div>
<button type="button" onclick="openProfileEditor()" class="px-2.5 py-1 rounded-xl bg-surface-container-high border border-outline-variant/40 text-on-surface-variant font-body-sm text-[12px] hover:text-on-surface hover:border-outline transition-colors">
            Ganti Akun
          </button>
</div>
</div>
<!-- CARDS 2 & 3: Buat Room + Gabung Room (Compact Side-by-Side Flex Grid) -->
<div class="grid grid-cols-1 md:grid-cols-12 gap-3 shrink-0">
<!-- CARD 2: Buat Room (7 cols) -->
<div id="buatRoomCard" class="md:col-span-7 p-3.5 rounded-2xl bg-surface-container-low border border-outline-variant/40 flex flex-col justify-between shadow-sm">
<div>
<div class="flex items-center justify-between mb-2">
<div class="flex items-center gap-1.5">
<span class="w-6 h-6 rounded-lg bg-primary-container/20 text-primary flex items-center justify-center">
<span class="material-symbols-outlined text-[15px]">add_circle</span>
</span>
<h2 class="font-title-sm text-title-sm font-semibold text-on-surface">Buat Room Baru</h2>
</div>
<span class="text-label-badge font-label-badge text-on-surface-variant bg-surface-container-high px-1.5 py-0.5 rounded">HOST MODE</span>
</div>
<!-- Form fields -->
<div class="space-y-2">
<div>
<label class="block font-body-sm text-[11px] text-on-surface-variant mb-1 font-medium">Nama Room</label>
<div class="relative">
<input class="w-full h-8 px-2.5 bg-surface-container text-on-surface placeholder:text-outline border border-outline-variant/40 rounded-xl font-body-sm text-body-sm focus:border-primary focus:ring-1 focus:ring-primary/40 focus:outline-none transition-all" id="createRoomName" placeholder="e.g. Late Night Lofi Study" type="text"/>
<span class="material-symbols-outlined absolute right-2.5 top-2 text-[15px] text-outline">headphones</span>
</div>
</div>
<!-- Checkbox Options -->
<div class="pt-0.5 space-y-1.5">
<label class="flex items-center gap-2 cursor-pointer group">
<input id="hostOnlyCheck" checked class="w-3.5 h-3.5 rounded bg-surface-container border-outline-variant text-primary focus:ring-0 focus:ring-offset-0" type="checkbox"/>
<span class="font-body-sm text-[12px] text-on-surface-variant group-hover:text-on-surface transition-colors">
                    Hanya host yang dapat mengatur antrian lagu
                  </span>
</label>
<div class="flex items-center justify-between">
<label class="flex items-center gap-2 cursor-pointer group">
<input class="w-3.5 h-3.5 rounded bg-surface-container border-outline-variant text-primary focus:ring-0 focus:ring-offset-0" id="privateRoomCheck" type="checkbox"/>
<span class="font-body-sm text-[12px] text-on-surface-variant group-hover:text-on-surface transition-colors">
                      Private Room (Kunci PIN)
                    </span>
</label>
<input class="w-20 h-6 px-1.5 text-center bg-surface-container text-on-surface border border-outline-variant/30 rounded-lg font-label-code text-[11px] focus:border-primary focus:outline-none" maxlength="4" id="roomPassword" placeholder="PIN 4-Digit" type="text"/>
</div>
</div>
</div>
</div>
<!-- Submit Button -->
<button type="button" onclick="createRoom()" class="mt-3 w-full h-8 px-4 rounded-xl bg-primary-container text-on-primary-container font-body-sm text-body-sm font-semibold hover:bg-primary-container/90 active:scale-[0.98] shadow-[0_0_12px_rgba(142,127,255,0.3)] transition-all flex items-center justify-center gap-1.5">
<span class="material-symbols-outlined text-[16px]">play_arrow</span>
<span>Buat Room Sekarang</span>
</button>
</div>
<!-- CARD 3: Gabung Room (5 cols) -->
<div class="md:col-span-5 p-3.5 rounded-2xl bg-surface-container-low border border-outline-variant/40 flex flex-col justify-between shadow-sm">
<div>
<div class="flex items-center justify-between mb-2">
<div class="flex items-center gap-1.5">
<span class="w-6 h-6 rounded-lg bg-tertiary-container/30 text-tertiary flex items-center justify-center">
<span class="material-symbols-outlined text-[15px]">key</span>
</span>
<h2 class="font-title-sm text-title-sm font-semibold text-on-surface">Gabung Cepat</h2>
</div>
<span class="font-label-badge text-label-badge text-emerald-400">INSTANT</span>
</div>
<p class="font-body-sm text-[11px] text-on-surface-variant mb-2">
              Punya kode undangan teman? Masukkan di sini:
            </p>
<!-- 6-character monospace segmented input style -->
<div class="relative mb-2">
<input class="w-full h-8 px-3 bg-surface-container text-primary font-label-code text-label-code tracking-widest text-center uppercase border border-outline-variant/50 rounded-xl focus:border-primary focus:ring-1 focus:ring-primary/40 focus:outline-none transition-all" id="joinRoomCode" maxlength="6" placeholder="SYN904" type="text" value=""/>
</div>
<p class="font-body-sm text-[10px] text-outline text-center">Format: [3 HURUF]-[3 ANGKA]</p>
</div>
<!-- Gabung Button -->
<button type="button" onclick="joinRoom()" class="mt-3 w-full h-8 px-3 rounded-xl bg-surface-container-high border border-outline-variant text-on-surface font-body-sm text-body-sm font-semibold hover:bg-surface-bright active:scale-[0.98] hover:border-primary/50 transition-all flex items-center justify-center gap-1.5">
<span class="material-symbols-outlined text-[16px] text-tertiary">login</span>
<span>Gabung Room</span>
</button>
</div>
</div>
<!-- CARD 4: Live Room List (Lobby Explorer) - Fills remaining height -->
<div class="flex-1 p-3.5 rounded-2xl bg-surface-container-low border border-outline-variant/40 flex flex-col min-h-0 shadow-sm overflow-hidden">
<!-- Header & Search Toolbar -->
<div class="flex items-center justify-between gap-3 mb-2.5 shrink-0">
<div class="flex items-center gap-2">
<span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
<h2 class="font-title-sm text-title-sm font-semibold text-on-surface">Room Publik Aktif</h2>
<span class="px-2 py-0.5 rounded-full bg-surface-container-highest text-on-surface-variant font-label-badge text-label-badge">
              <span id="roomListCount">0 ROOM</span>
            </span>
</div>
<!-- Micro search box -->
<div class="relative w-48">
<input class="w-full h-7 pl-7 pr-2.5 bg-surface-container text-on-surface placeholder:text-outline border border-outline-variant/40 rounded-lg font-body-sm text-[11px] focus:border-primary focus:outline-none" id="roomListSearch" oninput="renderRoomList(state.listedRooms||[])" placeholder="Cari room atau lagu..." type="text"/>
<span class="material-symbols-outlined absolute left-2 top-1.5 text-[14px] text-outline">search</span>
</div>
</div>
<div id="roomList" class="flex-1 overflow-y-auto space-y-1.5 pr-1 custom-scrollbar"></div>
<input id="joinUserName" class="hidden">
<!-- Footer / Micro room stats -->
<div class="pt-2 mt-1 border-t border-outline-variant/20 flex items-center justify-between text-on-surface-variant font-body-sm text-[11px] shrink-0">
<div class="flex items-center gap-2">
<span class="w-2 h-2 rounded-full bg-primary"></span>
<span id="fbStatus">Semua room berjalan pada master clock UTC-sync</span>
</div>
<button type="button" onclick="refreshRoomList()" class="text-tertiary hover:underline cursor-pointer flex items-center gap-1">
            Refresh Lobby
            <span class="material-symbols-outlined text-[13px]">refresh</span>
</button>
</div>
</div>
</section>
</div>

<div id="docsScreen" class="hidden h-full overflow-y-auto bg-surface p-6 md:p-10">
    <div class="max-w-5xl mx-auto flex flex-col gap-8 pb-16">
      <div>
        <span class="px-2.5 py-1 rounded-md bg-surface-container-high text-primary text-[11px] font-bold tracking-wider border border-outline-variant/30">PANDUAN SISTEM</span>
        <h1 class="text-3xl font-bold mt-3">Dokumentasi</h1>
        <p class="text-sm text-on-surface-variant max-w-2xl mt-2">Cara mengelola room, peran suara, dan batas sinkron antar perangkat.</p>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <article class="bg-surface-container-low border border-outline-variant/60 rounded-2xl p-6"><p class="text-xs text-primary font-mono mb-1">MODUL 01</p><h3 class="font-semibold text-lg mb-2">1. Buat Room</h3><p class="text-sm text-on-surface-variant">Dari Eksplor, centang kunci host bila hanya kamu yang boleh play, pause, dan seek. Room privat butuh PIN.</p></article>
        <article class="bg-surface-container-low border border-outline-variant/60 rounded-2xl p-6"><p class="text-xs text-primary font-mono mb-1">MODUL 02</p><h3 class="font-semibold text-lg mb-2">2. Gabung dengan Kode</h3><p class="text-sm text-on-surface-variant">Kode room 6 karakter. Tempel di Gabung Cepat atau buka room publik dari daftar.</p></article>
        <article class="bg-surface-container-low border border-outline-variant/60 rounded-2xl p-6"><p class="text-xs text-primary font-mono mb-1">MODUL 03</p><h3 class="font-semibold text-lg mb-2">3. Speaker dan Requester</h3><p class="text-sm text-on-surface-variant">Speaker memutar suara di perangkat ini. Requester tetap ikut antrian dan chat, suaranya mati.</p></article>
        <article class="bg-surface-container-low border border-outline-variant/60 rounded-2xl p-6"><p class="text-xs text-primary font-mono mb-1">MODUL 04</p><h3 class="font-semibold text-lg mb-2">4. Hanya Host</h3><p class="text-sm text-on-surface-variant">Jika kunci host aktif, hanya pembuat room yang mengubah play, pause, seek, dan antrian.</p></article>
        <article class="bg-surface-container-low border border-outline-variant/60 rounded-2xl p-6"><p class="text-xs text-primary font-mono mb-1">MODUL 05</p><h3 class="font-semibold text-lg mb-2">5. Sinkron YouTube</h3><p class="text-sm text-on-surface-variant">Posisi YouTube mengikuti jam server. Perangkat lain menyesuaikan jika selisihnya lebih dari seperempat detik.</p></article>
        <article class="bg-surface-container-low border border-outline-variant/60 rounded-2xl p-6"><p class="text-xs text-primary font-mono mb-1">MODUL 06</p><h3 class="font-semibold text-lg mb-2">6. Spotify</h3><p class="text-sm text-on-surface-variant">Spotify tampil sebagai embed. Posisinya tidak bisa di-seek dari luar, jadi kesamaan detik hanya untuk YouTube.</p></article>
      </div>
    </div>
  </div>

  <div id="playerScreen" data-mobile-tab="player" class="hidden h-full flex flex-col overflow-hidden">
    <div class="lg:hidden shrink-0 flex gap-1 p-2 bg-surface-container-low">
      <button type="button" onclick="switchMobileTab('player')" data-tab-btn="player" class="flex-1 py-2 rounded-lg text-xs font-medium">Player</button>
      <button type="button" onclick="switchMobileTab('queue')" data-tab-btn="queue" class="flex-1 py-2 rounded-lg text-xs font-medium">Antrian</button>
      <button type="button" onclick="switchMobileTab('chat')" data-tab-btn="chat" class="flex-1 py-2 rounded-lg text-xs font-medium">Chat</button>
    </div>
    <div id="playerColumns" class="flex-1 grid min-h-0 overflow-hidden lg:grid-cols-[14rem_minmax(0,1fr)_18rem]">
      <aside id="peopleCard" class="bg-surface-container-low border-r border-outline-variant hidden lg:flex flex-col min-h-0 min-w-0">
        <h2 class="p-4 font-semibold text-sm flex items-center gap-2"><span class="material-symbols-outlined text-primary">group</span>Orang online</h2>
        <p id="hostLockLabel" class="px-4 text-[11px] text-amber-200/90"></p>
        <div id="peopleList" class="flex-1 overflow-y-auto p-3 space-y-1 min-h-0"></div>
      </aside>
      <div id="tabContentPlayer" class="flex flex-col min-w-0 min-h-0 bg-surface overflow-y-auto p-4">
        <div class="video-container relative rounded-2xl overflow-hidden bg-black min-h-[180px] flex-1">
          <div id="yt-player"></div>
          <div id="audioCover" class="absolute inset-0 z-30 hidden flex-col items-center justify-center bg-surface">
            <div id="audioCoverArt" class="w-36 h-36 rounded-2xl mb-4 overflow-hidden flex items-center justify-center bg-surface-container-high">
              <img id="audioCoverImg" alt="" class="hidden w-full h-full object-cover">
              <span id="audioCoverIcon" class="material-symbols-outlined text-5xl text-primary">music_note</span>
            </div>
            <p id="audioCoverTitle" class="text-sm">Belum ada lagu</p>
            <p id="audioCoverHint" class="text-xs text-on-surface-variant mt-1">Tempel tautan YouTube atau Spotify</p>
          </div>
          <div id="noSongOverlay" class="absolute inset-0 z-10 flex items-center justify-center text-sm text-on-surface-variant">Belum ada lagu</div>
          <div id="spotifyOverlay" class="absolute inset-0 z-20 hidden bg-surface p-4"><div id="spotifyEmbedContainer" class="w-full max-w-md mx-auto"></div></div>
        </div>
        <div class="mt-4 text-center">
          <div class="flex justify-center gap-2 mb-3 text-[11px]">
            <button type="button" id="modeAudioBtn" onclick="setPlayerMode('audio')" class="px-3 py-1 rounded-full border border-primary/30">Audio</button>
            <button type="button" id="modeVideoBtn" onclick="setPlayerMode('video')" class="px-3 py-1 rounded-full border border-outline-variant">Video</button>
          </div>
          <h3 id="nowPlayingTitle" class="text-xl font-semibold">Tidak ada lagu</h3>
          <p id="nowPlayingArtist" class="text-sm text-secondary">—</p>
          <p class="text-[11px] text-on-surface-variant mt-1"><span id="nowPlayingSource">—</span> <span id="nowPlayingAddedBy"></span></p>
        </div>
        <div class="mt-4 max-w-xl mx-auto w-full">
          <div class="flex items-center gap-2 text-[11px] font-mono text-secondary">
            <span id="currentTime">0:00</span>
            <div id="progressBarContainer" class="flex-1 h-1.5 bg-surface-container-high rounded-full overflow-hidden cursor-pointer">
              <div id="progressBar" class="h-full bg-primary rounded-full" style="width:0%"></div>
            </div>
            <span id="totalTime">0:00</span>
          </div>
          <div class="flex items-center justify-center gap-5 mt-4">
            <button type="button" id="shuffleBtn" data-host-control onclick="toggleShuffle()" class="text-secondary"><span class="material-symbols-outlined">shuffle</span></button>
            <button type="button" data-host-control onclick="playPrevious()" class="text-on-surface"><span class="material-symbols-outlined text-3xl">skip_previous</span></button>
            <button type="button" id="playPauseBtn" data-host-control onclick="togglePlayPause()" class="w-16 h-16 rounded-full bg-primary text-on-primary flex items-center justify-center shadow-[0_0_24px_rgba(139,124,255,0.4)]">
              <span id="playIcon" class="material-symbols-outlined text-3xl">play_arrow</span>
              <span id="pauseIcon" class="material-symbols-outlined text-3xl hidden">pause</span>
            </button>
            <button type="button" data-host-control onclick="skipSong()"><span class="material-symbols-outlined text-3xl">skip_next</span></button>
            <button type="button" id="repeatBtn" onclick="toggleRepeat()" class="text-secondary"><span class="material-symbols-outlined">repeat</span></button>
            <button type="button" id="muteBtn" onclick="toggleMute()" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs bg-surface-container border border-outline-variant">
              <span id="muteIconOff" class="material-symbols-outlined text-base">volume_up</span>
              <span id="muteIconOn" class="material-symbols-outlined text-base hidden">volume_off</span>
              <span id="muteLabel">Mute</span>
            </button>
            <input type="range" min="0" max="100" value="80" oninput="setVolume(this.value)" class="w-20 accent-[#8e7fff]">
          </div>
        </div>
      </div>
      <aside id="tabContentSidebar" class="w-full bg-surface-container-low border-l border-outline-variant flex flex-col min-h-0 min-w-0">
        <div id="tabContentQueue" class="flex-1 flex flex-col min-h-0 p-4 gap-2">
          <div class="flex items-center justify-between">
            <h2 class="font-semibold text-sm">Antrian</h2>
            <span id="queueCount" class="text-xs text-secondary">0</span>
          </div>
          <input id="queueSearch" oninput="updateQueueUI()" placeholder="Cari judul..." class="w-full bg-surface-container border border-outline-variant rounded-xl px-3 py-2 text-sm">
          <div id="addSongPanel" class="flex gap-2">
            <input id="songUrlInput" onkeydown="if(event.key==='Enter') addSongFromInput()" placeholder="Tempel tautan YouTube atau Spotify" class="flex-1 bg-surface-container border border-outline-variant rounded-xl px-3 py-2 text-sm min-w-0">
            <button type="button" onclick="addSongFromInput()" class="px-3 rounded-xl bg-primary-container text-on-primary-container text-sm font-semibold">Tambah</button>
          </div>
          <div id="queueList" class="flex-1 overflow-y-auto custom-scroll min-h-0 space-y-1"></div>
          <div class="flex gap-2">
            <button type="button" id="clearQueueBtn" onclick="clearQueue()" class="flex-1 py-2 text-xs rounded-lg bg-surface-container">Kosongkan</button>
            <button type="button" id="shuffleQueueBtn" onclick="shuffleQueue()" class="flex-1 py-2 text-xs rounded-lg bg-surface-container">Acak</button>
          </div>
        </div>
        <div id="sideBottom" class="h-64 flex flex-col border-t border-outline-variant bg-surface-container-lowest">
          <div id="tabContentChat" class="flex flex-col flex-1 min-h-0">
            <div class="flex border-b border-outline-variant">
              <button type="button" id="tabLogBtn" onclick="switchSideTab('log')" class="flex-1 py-2 text-sm font-semibold">Log</button>
              <button type="button" id="tabChatBtn" onclick="switchSideTab('chat')" class="flex-1 py-2 text-sm">Chat</button>
            </div>
            <div id="logList" class="flex-1 overflow-y-auto p-3 space-y-1 text-xs min-h-0"></div>
            <div id="chatPane" class="flex flex-col flex-1 min-h-0" style="display:none">
              <div id="chatMessages" class="flex-1 overflow-y-auto p-3 space-y-2 min-h-0"></div>
              <div class="p-2 flex gap-2 border-t border-outline-variant">
                <input id="chatInput" maxlength="200" onkeydown="if(event.key==='Enter') sendChatMessage()" placeholder="Kirim pesan" class="flex-1 bg-surface-container border border-outline-variant rounded-xl px-3 py-1.5 text-sm">
                <button type="button" onclick="sendChatMessage()" class="w-9 h-9 rounded-xl bg-primary text-on-primary"><span class="material-symbols-outlined text-sm">send</span></button>
              </div>
            </div>
          </div>
        </div>
      </aside>
    </div>
  </div>
</main>
<script src="assets/player.js?v=<?php echo filemtime(__DIR__ . '/assets/player.js'); ?>"></script>
</body>
</html>
