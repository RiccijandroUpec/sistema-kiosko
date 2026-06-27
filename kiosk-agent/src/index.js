import { config } from './config.js';
import {
  authenticateKiosk,
  completeJob,
  downloadPdf,
  fetchPendingJobs,
  markPrinting,
  sendHeartbeat,
  reportJobError
} from './api.js';
import { printPdf, savePdf } from './printer.js';
import { runCleanup } from './cleanup.js';
import { log, setStatus, state } from './state.js';
import { startWebPanel } from './server.js';
import { createClient } from '@supabase/supabase-js';

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

// Set to avoid processing the same job multiple times concurrently
const processingJobs = new Set();

async function processJob(job) {
  if (processingJobs.has(job.id)) {
    return;
  }
  processingJobs.add(job.id);

  try {
    state.currentJob = job.job_reference;
    log(`Procesando orden ${job.job_reference}...`);

    await markPrinting(job.id);

    // downloadPdf follows the 302 redirect from Laravel API to Supabase automatically
    const pdfBuffer = await downloadPdf(job.id);
    const filePath = await savePdf(job.job_reference, pdfBuffer);

    log(`PDF descargado en ${filePath}`);

    await printPdf(filePath, state.printerName, {
      colorType: job.color_type,
    });

    await completeJob(job.id, 'Impreso desde kiosk-agent');
    log(`Trabajo completado con éxito: ${job.job_reference}`);
  } catch (error) {
    log(`Error procesando orden ${job.id}: ${error.message}`, 'error');
    try {
      await reportJobError(job.id, error.message);
      log(`Error reportado a la API central para la orden ${job.id}`);
    } catch (reportError) {
      log(`Fallo al reportar el error a la API: ${reportError.message}`, 'error');
    }
  } finally {
    state.currentJob = null;
    processingJobs.delete(job.id);
  }
}

async function testConnection() {
  const auth = await authenticateKiosk();
  await sendHeartbeat();

  setStatus('online', {
    kioskId: auth.data.id,
    kioskName: auth.data.nombre,
    printerName: auth.data.nombre_cups,
    centralUrl: config.centralUrl,
    lastHeartbeatAt: new Date().toISOString(),
  });

  return {
    message: `Conectado correctamente como ${auth.data.nombre}`,
  };
}

function setupSupabaseRealtime(kioskId) {
  if (!config.supabaseUrl || !config.supabaseKey) {
    log('Falta SUPABASE_URL o SUPABASE_ANON_KEY en .env. Realtime desactivado.', 'warn');
    return null;
  }

  try {
    const supabase = createClient(config.supabaseUrl, config.supabaseKey);

    log('Conectando a Supabase Realtime...');
    
    const channel = supabase
      .channel(`kiosk-orders-${kioskId}`)
      .on(
        'postgres_changes',
        {
          event: '*', // Listen to INSERTs and UPDATEs
          schema: 'public',
          table: 'ordenes_impresion',
          filter: `kiosko_id=eq.${kioskId}`,
        },
        async (payload) => {
          const job = payload.new;
          if (!job) return;

          log(`[REALTIME] Cambio detectado en orden ${job.id}. Estado: ${job.estado}`);

          // En la arquitectura, cuando el usuario paga, el estado cambia a 'pagado'
          if (job.estado === 'pagado') {
            log(`[REALTIME] ¡Orden ${job.id} pagada! Iniciando impresión...`);
            
            // Mapear al payload que espera processJob
            const jobPayload = {
              id: job.id,
              job_reference: job.id,
              kiosk_id: job.kiosko_id,
              status: job.estado,
              color_type: job.color ? 'color' : 'bw',
            };
            
            await processJob(jobPayload);
          }
        }
      )
      .subscribe((status) => {
        log(`[REALTIME] Canal WebSocket: ${status}`);
      });

    return supabase;
  } catch (error) {
    log(`Error al configurar Supabase Realtime: ${error.message}`, 'error');
    return null;
  }
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

  let kioskId = null;

  try {
    const auth = await authenticateKiosk();
    kioskId = auth.data.id;
    
    setStatus('online', {
      kioskId: auth.data.id,
      kioskName: auth.data.nombre,
      printerName: auth.data.nombre_cups,
      centralUrl: config.centralUrl,
      lastHeartbeatAt: new Date().toISOString(),
    });
    log(`Autenticado como kiosko #${auth.data.id} (${auth.data.nombre})`);
  } catch (error) {
    setStatus('error', { lastError: error.message });
    log(`No se pudo autenticar el kiosko: ${error.message}`, 'error');
    process.exit(1);
  }

  // Setup WebSocket Listener
  setupSupabaseRealtime(kioskId);

  // Sync Loop (Heartbeat & Fallback Polling)
  while (true) {
    try {
      await sendHeartbeat();
      setStatus('online', { lastHeartbeatAt: new Date().toISOString(), lastError: null });
      
      // Fallback: fetch any pending jobs (state 'pagado' or 'imprimiendo') to catch missed events
      const response = await fetchPendingJobs();
      const jobs = Array.isArray(response.data) ? response.data : [];
      state.lastSyncAt = new Date().toISOString();

      if (jobs.length > 0) {
        log(`Sincronización: Encontrados ${jobs.length} trabajos pendientes.`);
        for (const job of jobs) {
          await processJob(job);
        }
      }
    } catch (error) {
      setStatus('degraded', { lastError: error.message });
      log(`Error en el ciclo de sincronización (Modo offline / reintentando): ${error.message}`, 'error');
    }

    try {
        await runCleanup();
    } catch (e) {
        log(`Error en limpieza: ${e.message}`, 'error');
    }

    await sleep(config.pollIntervalMs);
  }
}

mainLoop().catch((error) => {
  console.error(error);
  process.exit(1);
});