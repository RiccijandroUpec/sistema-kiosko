import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

function readEnvFile(filePath) {
  if (!fs.existsSync(filePath)) {
    return {};
  }

  const content = fs.readFileSync(filePath, 'utf8');
  const env = {};

  for (const line of content.split(/\r?\n/)) {
    const trimmed = line.trim();
    if (!trimmed || trimmed.startsWith('#')) {
      continue;
    }

    const equalIndex = trimmed.indexOf('=');
    if (equalIndex === -1) {
      continue;
    }

    const key = trimmed.slice(0, equalIndex).trim();
    const value = trimmed.slice(equalIndex + 1).trim();
    env[key] = value;
  }

  return env;
}

const currentFile = fileURLToPath(import.meta.url);
const rootDir = path.resolve(path.dirname(currentFile), '..');
const localEnv = readEnvFile(path.join(rootDir, '.env'));

export const config = {
  centralUrl: process.env.CENTRAL_URL || localEnv.CENTRAL_URL || 'https://kiosko.cyrshop.app',
  kioskApiToken: process.env.KIOSK_API_TOKEN || localEnv.KIOSK_API_TOKEN || '',
  pollIntervalMs: Number(process.env.POLL_INTERVAL_MS || localEnv.POLL_INTERVAL_MS || 10000),
  kioskName: process.env.KIOSK_NAME || localEnv.KIOSK_NAME || 'Kiosko',
  printerName: process.env.PRINTER_NAME || localEnv.PRINTER_NAME || '',
  downloadDir: process.env.DOWNLOAD_DIR || localEnv.DOWNLOAD_DIR || 'downloads',
  outputDir: process.env.OUTPUT_DIR || localEnv.OUTPUT_DIR || 'output',
  printMode: (process.env.PRINT_MODE || localEnv.PRINT_MODE || 'printer').toLowerCase(),
  rootDir,
};