import fs from 'node:fs/promises';
import path from 'node:path';
import { execFile } from 'node:child_process';
import { promisify } from 'node:util';
import { config } from './config.js';

const execFileAsync = promisify(execFile);

export async function ensureDownloadDir() {
  await fs.mkdir(path.join(config.rootDir, config.downloadDir), { recursive: true });
}

export async function ensureOutputDir() {
  await fs.mkdir(path.join(config.rootDir, config.outputDir), { recursive: true });
}

export async function savePdf(jobReference, pdfBuffer) {
  await ensureDownloadDir();

  const fileName = `${jobReference}.pdf`;
  const filePath = path.join(config.rootDir, config.downloadDir, fileName);
  await fs.writeFile(filePath, pdfBuffer);

  return filePath;
}

export async function printPdf(filePath) {
  if (config.printMode === 'pdf') {
    await ensureOutputDir();

    const outputFile = path.join(config.rootDir, config.outputDir, path.basename(filePath));
    await fs.copyFile(filePath, outputFile);
    return outputFile;
  }

  if (process.platform === 'win32') {
    const script = `Start-Process -FilePath '${filePath.replace(/'/g, "''")}' -Verb Print`;
    await execFileAsync('powershell.exe', ['-NoProfile', '-Command', script]);
    return;
  }

  if (process.platform === 'linux') {
    await execFileAsync('lp', [filePath]);
    return;
  }

  throw new Error('Printing is not configured for this platform yet.');
}