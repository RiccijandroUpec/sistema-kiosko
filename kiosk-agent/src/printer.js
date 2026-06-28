import fs from 'node:fs/promises';
import path from 'node:path';
import { execFile } from 'node:child_process';
import { promisify } from 'node:util';
import { config } from './config.js';
import ptp from 'pdf-to-printer';

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
    const printOptions = {};
    if (printerName) {
      printOptions.printer = printerName;
    }

    // Copias reales pagadas por el cliente (antes siempre se imprimía 1 sin importar lo pagado)
    if (options.copies && options.copies > 1) {
      printOptions.copies = options.copies;
    }

    // Rango de páginas personalizado (ej. "1-5,8"). Sin esto, siempre se imprimía el documento completo.
    if (options.pagesRange) {
      printOptions.pages = options.pagesRange;
    }

    if (options.orientation === 'portrait' || options.orientation === 'landscape') {
      printOptions.orientation = options.orientation;
    }

    // Antes esta opción no se aplicaba en Windows: se imprimía con lo que la impresora
    // tuviera configurado por defecto, sin importar si el cliente pagó color o B/N.
    if (options.colorType === 'bw') {
      printOptions.monochrome = true;
    } else if (options.colorType === 'color') {
      printOptions.monochrome = false;
    }

    const paperSizeMap = { a4: 'A4', letter: 'Letter', legal: 'Legal' };
    if (options.paperSize && paperSizeMap[options.paperSize]) {
      printOptions.paperSize = paperSizeMap[options.paperSize];
    }

    await ptp.print(filePath, printOptions);
    return;
  }

  if (process.platform === 'linux') {
    const args = [];
    if (printerName) {
      args.push('-d', printerName);
    }

    if (options.copies && options.copies > 1) {
      args.push('-n', String(options.copies));
    }

    if (options.pagesRange) {
      args.push('-P', options.pagesRange);
    }

    if (options.orientation === 'landscape') {
      args.push('-o', 'landscape');
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