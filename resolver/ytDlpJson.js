const { spawn, spawnSync } = require('child_process');
const fs = require('fs');
const path = require('path');
const { getYtDlpCommonFlags } = require('./ytDlpAuth');

const WINDOWS_BINARY = path.join(
  'C:\\laragon\\www\\bot-discord',
  'node_modules',
  '@distube',
  'yt-dlp',
  'bin',
  'yt-dlp.exe',
);

function commandExists(cmd) {
  try {
    const which = process.platform === 'win32' ? 'where' : 'which';
    const result = spawnSync(which, [cmd], { encoding: 'utf8', windowsHide: true });
    return result.status === 0;
  } catch {
    return false;
  }
}

function fileReady(file) {
  return Boolean(file) && fs.existsSync(file);
}

function resolveLaunch() {
  if (fileReady(process.env.YT_DLP_PATH)) {
    return { cmd: process.env.YT_DLP_PATH, argsPrefix: [], label: process.env.YT_DLP_PATH };
  }
  if (process.platform === 'win32' && fileReady(WINDOWS_BINARY)) {
    return { cmd: WINDOWS_BINARY, argsPrefix: [], label: WINDOWS_BINARY };
  }
  if (commandExists('yt-dlp')) {
    return { cmd: 'yt-dlp', argsPrefix: [], label: 'yt-dlp' };
  }
  const pythons = [
    process.env.YT_DLP_PYTHON,
    process.platform === 'win32' ? 'C:\\laragon\\bin\\python\\python-3.10\\python.exe' : '',
    'python3',
    'python',
  ].filter(Boolean);
  for (const bin of pythons) {
    const isPath = bin.includes('\\') || bin.includes('/');
    if (isPath ? !fileReady(bin) : !commandExists(bin)) continue;
    return { cmd: bin, argsPrefix: ['-m', 'yt_dlp'], label: `${bin} -m yt_dlp` };
  }
  return { cmd: 'yt-dlp', argsPrefix: [], label: 'yt-dlp (tidak ditemukan di PATH)' };
}

const LAUNCH = resolveLaunch();
console.error(`[yt-dlp] memakai ${LAUNCH.label}`);

function kebab(key) {
  return key.replace(/[A-Z]/g, (letter) => `-${letter.toLowerCase()}`);
}

function buildArgs(url, flags = {}) {
  const args = [url];
  Object.entries(flags).forEach(([key, value]) => {
    if (value === false || value == null) return;
    const flag = `--${kebab(key)}`;
    if (value === true) {
      args.push(flag);
      return;
    }
    args.push(flag, String(value));
  });
  return args;
}

function parseJsonOutput(stdout, stderr) {
  const primary = stdout.trim();

  if (primary) {
    try {
      return JSON.parse(primary);
    } catch {
      const start = primary.indexOf('{');
      const end = primary.lastIndexOf('}');
      if (start !== -1 && end > start) {
        return JSON.parse(primary.slice(start, end + 1));
      }
    }
  }

  const fallback = stderr.trim();
  const start = fallback.indexOf('{');
  const end = fallback.lastIndexOf('}');

  if (start !== -1 && end > start) {
    return JSON.parse(fallback.slice(start, end + 1));
  }

  throw new Error(fallback.slice(0, 300) || primary.slice(0, 300) || 'Output yt-dlp kosong');
}

function spawnYtDlp(args, options) {
  return spawn(LAUNCH.cmd, [...LAUNCH.argsPrefix, ...args], options);
}

function ytDlpJson(url, flags = {}, options = {}) {
  const mergedFlags = { ...getYtDlpCommonFlags(), ...flags };
  const child = spawnYtDlp(buildArgs(url, mergedFlags), options);

  return new Promise((resolve, reject) => {
    let stdout = '';
    let stderr = '';

    child.stdout?.on('data', (chunk) => {
      stdout += chunk;
    });

    child.stderr?.on('data', (chunk) => {
      stderr += chunk;
    });

    child.on('error', (error) => {
      reject(new Error(`yt-dlp tidak bisa dijalankan (${LAUNCH.label}): ${error.message}`));
    });

    child.on('close', (code) => {
      if (code !== 0) {
        reject(new Error((stderr.trim() || stdout.trim() || `yt-dlp exit code ${code}`).slice(0, 400)));
        return;
      }

      try {
        resolve(parseJsonOutput(stdout, stderr));
      } catch (error) {
        reject(new Error(`Gagal parse JSON yt-dlp: ${error.message}`));
      }
    });
  });
}

module.exports = { ytDlpJson, YTDLP_PATH: LAUNCH.label };
