import { spawn } from 'node:child_process';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

// Si el agente se cae por una excepcion no atrapada, nadie en la sucursal se
// entera hasta que un cliente se queja de que no imprime. Este wrapper lo
// vuelve a levantar solo, con backoff para no entrar en un loop de reinicios
// si el problema es persistente (ej. .env mal configurado).
const __dirname = path.dirname(fileURLToPath(import.meta.url));
const agentEntry = path.join(__dirname, 'index.js');

const minDelayMs = 2000;
const maxDelayMs = 60000;
const stableAfterMs = 5 * 60 * 1000;

let delayMs = minDelayMs;

function startAgent() {
  const startedAt = Date.now();
  console.log(`[watchdog] Iniciando kiosk-agent (${agentEntry})`);

  const child = spawn(process.execPath, [agentEntry], {
    stdio: 'inherit',
    env: process.env,
  });

  child.on('exit', (code, signal) => {
    const uptimeMs = Date.now() - startedAt;

    if (uptimeMs > stableAfterMs) {
      delayMs = minDelayMs;
    }

    console.error(`[watchdog] kiosk-agent termino (code=${code}, signal=${signal}) tras ${Math.round(uptimeMs / 1000)}s. Reiniciando en ${Math.round(delayMs / 1000)}s...`);

    setTimeout(startAgent, delayMs);
    delayMs = Math.min(delayMs * 2, maxDelayMs);
  });
}

startAgent();
