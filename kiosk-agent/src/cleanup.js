import fs from 'node:fs/promises';
import path from 'node:path';
import { config } from './config.js';

const CLEANUP_AGE_MS = 48 * 60 * 60 * 1000; // 48 horas

async function cleanDirectory(dirPath) {
    try {
        const fullPath = path.join(config.rootDir, dirPath);
        
        // Comprobar si el directorio existe
        try {
            await fs.access(fullPath);
        } catch {
            return; // Si no existe, no hay nada que limpiar
        }

        const files = await fs.readdir(fullPath);
        const now = Date.now();
        let deleted = 0;

        for (const file of files) {
            const filePath = path.join(fullPath, file);
            const stats = await fs.stat(filePath);

            if (now - stats.mtimeMs > CLEANUP_AGE_MS) {
                await fs.unlink(filePath);
                deleted++;
            }
        }

        if (deleted > 0) {
            console.log(`[CLEANUP] Eliminados ${deleted} archivos antiguos en ${dirPath}`);
        }
    } catch (error) {
        console.error(`[ERROR] Fallo al limpiar directorio ${dirPath}:`, error.message);
    }
}

export async function runCleanup() {
    await cleanDirectory(config.downloadDir);
    await cleanDirectory(config.outputDir);
}
