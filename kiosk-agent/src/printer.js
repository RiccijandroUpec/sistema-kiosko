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

export async function printPdf(filePath, printerName = null, options = {}) {
  if (config.printMode === 'pdf') {
    await ensureOutputDir();

    const outputFile = path.join(config.rootDir, config.outputDir, path.basename(filePath));
    await fs.copyFile(filePath, outputFile);
    return outputFile;
  }

  if (process.platform === 'win32') {
    let script = '';
    if (printerName) {
      script = `Set-DefaultPrinter -Name '${printerName.replace(/'/g, "''")}'; Start-Process -FilePath '${filePath.replace(/'/g, "''")}' -Verb Print`;
    } else {
      script = `Start-Process -FilePath '${filePath.replace(/'/g, "''")}' -Verb Print`;
    }
    await execFileAsync('powershell.exe', ['-NoProfile', '-Command', script]);
    return;
  }

  if (process.platform === 'linux') {
    const args = [];
    if (printerName) {
      args.push('-d', printerName);
    }

    // Opciones de color de CUPS
    if (options.colorType === 'bw') {
      args.push('-o', 'ColorModel=Gray', '-o', 'ColorMode=monochrome');
    } else if (options.colorType === 'color') {
      args.push('-o', 'ColorModel=Color', '-o', 'ColorMode=color');
    }

    // Auto-rotar y ajustar página
    args.push('-o', 'fit-to-page');
    args.push(filePath);

    await execFileAsync('lp', args);
    return;
  }

  throw new Error('Printing is not configured for this platform yet.');
}