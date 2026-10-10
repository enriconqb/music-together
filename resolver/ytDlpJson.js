const { spawn } = require('child_process');
const path = require('path');
const { getYtDlpCommonFlags } = require('./ytDlpAuth');

const BOT_BINARY = path.join(
  'C:\\laragon\\www\\bot-discord',
  'node_modules',
  '@distube',
  'yt-dlp',
  'bin',
  process.platform === 'win32' ? 'yt-dlp.exe' : 'yt-dlp',
);
const PYTHON = process.env.YT_DLP_PYTHON || 'C:\\laragon\\bin\\python\\python-3.10\\python.exe';
const YTDLP_PATH = process.env.YT_DLP_PATH || BOT_BINARY;

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
  const fs = require('fs');
  if (process.env.YT_DLP_PATH || fs.existsSync(YTDLP_PATH)) {
    return spawn(YTDLP_PATH, args, options);
  }
  return spawn(PYTHON, ['-m', 'yt_dlp', ...args], options);
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
      reject(new Error(`yt-dlp tidak bisa dijalankan (${YTDLP_PATH}): ${error.message}`));
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

module.exports = { ytDlpJson, YTDLP_PATH };
