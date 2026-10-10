const fs = require('fs');

function getYtDlpAuthFlags() {
  const flags = {};
  const cookiesFile = (process.env.YT_DLP_COOKIES || '').trim();
  if (cookiesFile && fs.existsSync(cookiesFile)) {
    flags.cookies = cookiesFile;
  }
  return flags;
}

function getYtDlpCommonFlags() {
  return {
    ...getYtDlpAuthFlags(),
    extractorArgs: 'youtube:player_client=android,web',
  };
}

module.exports = {
  getYtDlpAuthFlags,
  getYtDlpCommonFlags,
};
