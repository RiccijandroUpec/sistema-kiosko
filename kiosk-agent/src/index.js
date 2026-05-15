import { config } from './config.js';
import {
  authenticateKiosk,
  completeJob,
  downloadPdf,
  fetchPendingJobs,
  markPrinting,
  sendHeartbeat,
} from './api.js';
import { printPdf, savePdf } from './printer.js';
import { log, setStatus, state } from './state.js';
import { startWebPanel } from './server.js';

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

async function processJob(job) {
  state.currentJob = job.job_reference;
  log(`Procesando ${job.job_reference}`);

  await markPrinting(job.id);

  const pdfBuffer = await downloadPdf(job.id);
  const filePath = await savePdf(job.job_reference, pdfBuffer);

  log(`PDF descargado en ${filePath}`);

  await printPdf(filePath);

  await completeJob(job.id, 'Impreso desde kiosk-agent');
  state.currentJob = null;
  log(`Trabajo completado: ${job.job_reference}`);
}

async function testConnection() {
  const auth = await authenticateKiosk();
  await sendHeartbeat();

  setStatus('online', {
    kioskId: auth.data.id,
    kioskName: auth.data.nombre,
    centralUrl: config.centralUrl,
    lastHeartbeatAt: new Date().toISOString(),
  });

  return {
    message: `Conectado correctamente como ${auth.data.nombre}`,
  };
}

async function mainLoop() {
  if (!config.kioskApiToken) {
    log('Falta KIOSK_API_TOKEN en .env', 'error');
    process.exit(1);
  }

  state.kioskName = config.kioskName;
  state.centralUrl = config.centralUrl;
  setStatus('starting');

  startWebPanel({ onTestConnection: testConnection });

  log(`Agente iniciado: ${config.kioskName}`);
  log(`Conectando a: ${config.centralUrl}`);

  try {
    const auth = await authenticateKiosk();
    setStatus('online', {
      kioskId: auth.data.id,
      kioskName: auth.data.nombre,
      centralUrl: config.centralUrl,
      lastHeartbeatAt: new Date().toISOString(),
    });
    log(`Autenticado como kiosko #${auth.data.id} (${auth.data.nombre})`);
  } catch (error) {
    setStatus('error', { lastError: error.message });
    log(`No se pudo autenticar el kiosko: ${error.message}`, 'error');
    process.exit(1);
  }

  while (true) {
    try {
      await sendHeartbeat();
      setStatus('online', { lastHeartbeatAt: new Date().toISOString(), lastError: null });
      const response = await fetchPendingJobs();
      const jobs = Array.isArray(response.data) ? response.data : [];
      state.lastSyncAt = new Date().toISOString();

      if (jobs.length === 0) {
        log('Sin trabajos pendientes');
      }

      for (const job of jobs) {
        await processJob(job);
      }
    } catch (error) {
      setStatus('degraded', { lastError: error.message });
      log(`Error en el ciclo: ${error.message}`, 'error');
    }

    await sleep(config.pollIntervalMs);
  }
}

mainLoop().catch((error) => {
  console.error(error);
  process.exit(1);
});