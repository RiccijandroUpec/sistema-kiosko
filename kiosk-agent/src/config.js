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

const isPkg = typeof process.pkg !== 'undefined';
const executableDir = process.cwd(); // Directorio donde corre el ejecutable o comando
const srcDir = fileURLToPath(import.meta.url);
const rootDir = isPkg ? executableDir : path.resolve(path.dirname(srcDir), '..');

// Si está empaquetado, busca el .env al lado del .exe. Si no, en rootDir
const envPath = isPkg ? path.join(executableDir, '.env') : path.join(rootDir, '.env');
const localEnv = readEnvFile(envPath);

export const config = {
  centralUrl: process.env.CENTRAL_URL || localEnv.CENTRAL_URL || 'http://127.0.0.1:8000',
  kioskApiToken: process.env.KIOSK_API_TOKEN || localEnv.KIOSK_API_TOKEN || '',
  supabaseUrl: process.env.SUPABASE_URL || localEnv.SUPABASE_URL || '',
  supabaseKey: process.env.SUPABASE_ANON_KEY || localEnv.SUPABASE_ANON_KEY || '',
  pollIntervalMs: Number(process.env.POLL_INTERVAL_MS || localEnv.POLL_INTERVAL_MS || 10000),
  kioskName: process.env.KIOSK_NAME || localEnv.KIOSK_NAME || 'Kiosko',
  printerName: process.env.PRINTER_NAME || localEnv.PRINTER_NAME || '',
  downloadDir: process.env.DOWNLOAD_DIR || localEnv.DOWNLOAD_DIR || 'downloads',
  outputDir: process.env.OUTPUT_DIR || localEnv.OUTPUT_DIR || 'output',
  printMode: (process.env.PRINT_MODE || localEnv.PRINT_MODE || 'printer').toLowerCase(),
  rootDir,
};