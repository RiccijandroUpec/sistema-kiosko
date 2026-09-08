import http from 'node:http';
import fs from 'node:fs/promises';
import path from 'node:path';
import { state, log } from './state.js';
import { config } from './config.js';
import { releaseOrderByPin } from './api.js';

function renderAdminPage() {
  const statusLabels = {
    starting: 'Iniciando',
    online: 'Conectado',
    degraded: 'Con problemas',
    error: 'Error',
  };
  const statusLabel = statusLabels[state.status] || state.status;

  const logsHtml = state.recentLogs.length
    ? state.recentLogs.map((entry) => `
        <div class="log ${entry.level}">
          <div class="meta">${entry.timestamp} • ${entry.level.toUpperCase()}</div>
          <div class="message">${escapeHtml(entry.message)}</div>
        </div>
      `).join('')
    : '<div class="empty">Sin actividad todavía.</div>';

  return `<!doctype html>
  <html lang="es">
    <head>
      <meta charset="utf-8" />
      <meta name="viewport" content="width=device-width, initial-scale=1" />
      <title>Kiosk Agent - Admin</title>
      <link rel="preconnect" href="https://fonts.googleapis.com" />
      <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet" />
      <style>
        :root { color-scheme: light; }
        body { margin: 0; font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif; background: radial-gradient(circle at top right, #e0e7ff 0%, #f8fafc 50%); color: #0f172a; }
        .wrap { max-width: 1100px; margin: 0 auto; padding: 32px; }
        .grid { display: grid; gap: 16px; grid-template-columns: 1.1fr 0.9fr; }
        .card { background: rgba(255, 255, 255, .92); border: 1px solid #f1f5f9; border-radius: 28px; padding: 24px; box-shadow: 0 24px 60px rgba(99, 102, 241, .12); }
        h1 { margin: 0 0 8px; font-size: 32px; }
        .muted { color: #94a3b8; }
        .pill { display: inline-flex; align-items: center; padding: 6px 10px; border-radius: 999px; background: #eef2ff; color: #4f46e5; font-size: 12px; font-weight: 800; }
        .status-online { color: #6ee7b7; border-color: rgba(16, 185, 129, .35); }
        .status-degraded { color: #fcd34d; border-color: rgba(245, 158, 11, .35); }
        .status-error { color: #fca5a5; border-color: rgba(248, 113, 113, .35); }
        .stats { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; margin-top: 16px; }
        .stat { border-radius: 16px; padding: 14px; background: #f8fafc; border: 1px solid #f1f5f9; }
        .label { color: #94a3b8; font-size: 12px; text-transform: uppercase; letter-spacing: .08em; }
        .value { margin-top: 4px; font-size: 18px; font-weight: 800; word-break: break-word; }
        .buttons { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 16px; }
        button, a.btn { appearance: none; border: 0; cursor: pointer; padding: 12px 16px; border-radius: 14px; font-weight: 800; text-decoration: none; color: #fff; background: #4f46e5; box-shadow: 0 10px 24px rgba(79, 70, 229, .18); }
        button.secondary, a.secondary { background: #fff; color: #475569; border: 1px solid #e2e8f0; box-shadow: none; }
        .logs { display: grid; gap: 10px; max-height: 530px; overflow: auto; }
        .log { border-radius: 14px; padding: 12px 14px; background: #f8fafc; border: 1px solid #f1f5f9; }
        .log .meta { color: #94a3b8; font-size: 12px; margin-bottom: 4px; }
        .log .message { font-size: 14px; line-height: 1.4; }
        .log.error { border-color: rgba(248, 113, 113, 0.35); }
        .log.warn { border-color: rgba(250, 204, 21, 0.35); }
        .empty { color: #94a3b8; padding: 24px; text-align: center; }
        .footer { margin-top: 14px; color: #64748b; font-size: 12px; }
        @media (max-width: 900px) { .grid { grid-template-columns: 1fr; } }
      </style>
    </head>
    <body>
      <div class="wrap">
        <div class="card">
          <div class="pill">Consola de Administración Local</div>
          <h1>${escapeHtml(state.kioskName || config.kioskName || 'Kiosk Agent')}</h1>
          <div class="muted">Este panel sirve para ver el estado del agente sin abrir la consola.</div>
          <div class="buttons">
            <button onclick="testConnection()">Probar conexión</button>
            <button class="secondary" onclick="location.reload()">Actualizar</button>
            <a class="btn secondary" href="/api/state" target="_blank">Ver JSON</a>
            <a class="btn secondary" href="/" target="_self">Pantalla de Impresión</a>
          </div>
          <div class="stats">
            <div class="stat"><div class="label">Estado</div><div class="value status-${escapeHtml(state.status)}">${escapeHtml(statusLabel)}</div></div>
            <div class="stat"><div class="label">Kiosko ID</div><div class="value">${escapeHtml(String(state.kioskId ?? 'sin registrar'))}</div></div>
            <div class="stat"><div class="label">Impresora</div><div class="value">${escapeHtml(state.printerName || config.printerName || 'no configurada')}</div></div>
            <div class="stat"><div class="label">Trabajo actual</div><div class="value">${escapeHtml(state.currentJob || 'ninguno')}</div></div>
            <div class="stat"><div class="label">Último heartbeat</div><div class="value">${escapeHtml(state.lastHeartbeatAt || 'nunca')}</div></div>
            <div class="stat"><div class="label">Última sincronización</div><div class="value">${escapeHtml(state.lastSyncAt || 'nunca')}</div></div>
          </div>
          <div class="stat" style="margin-top: 12px; display: ${state.lastError ? 'block' : 'none'}; border-color: rgba(248, 113, 113, .35);">
            <div class="label">Último error</div>
            <div class="value" style="color: #fca5a5;">${escapeHtml(state.lastError || '')}</div>
          </div>
          <div class="footer">
            Central: ${escapeHtml(state.centralUrl || config.centralUrl)} • Modo impresión: ${escapeHtml(config.printMode)}
          </div>
        </div>

        <div class="card" style="margin-top: 20px;">
          <h2 style="margin-top:0">🔑 Liberar Impresión con PIN de Retiro</h2>
          <div class="muted" style="margin-bottom: 12px;">Si el cliente tiene un código de retiro de 4 dígitos, ingrésalo aquí para imprimir inmediatamente.</div>
          <div style="display: flex; gap: 10px; max-width: 400px;">
            <input type="text" id="kioskPinInput" maxlength="4" placeholder="Ej: 1234" style="flex: 1; padding: 12px 16px; border: 1px solid #cbd5e1; border-radius: 14px; font-size: 18px; font-weight: 800; text-align: center; letter-spacing: 4px;" />
            <button onclick="releasePin()" id="btnRelease">Liberar</button>
          </div>
          <div id="pinMsg" style="margin-top: 10px; font-size: 14px; font-weight: 700;"></div>
        </div>

        <div class="card" style="margin-top: 20px;">
          <h2 style="margin-top:0">Actividad reciente</h2>
          <div class="logs">${logsHtml}</div>
        </div>
      </div>

      <script>
        async function releasePin() {
          const pin = document.getElementById('kioskPinInput').value.trim();
          const msg = document.getElementById('pinMsg');
          const btn = document.getElementById('btnRelease');
          if (pin.length !== 4) {
            msg.style.color = '#ef4444';
            msg.innerText = 'El PIN debe tener 4 dígitos.';
            return;
          }
          btn.disabled = true;
          btn.innerText = 'Consultando...';
          try {
            const res = await fetch('/api/release-pin', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ pin })
            });
            const data = await res.json();
            if (data.success) {
              msg.style.color = '#10b981';
              msg.innerText = data.message;
              document.getElementById('kioskPinInput').value = '';
            } else {
              msg.style.color = '#ef4444';
              msg.innerText = data.message || 'Error al liberar orden.';
            }
          } catch(e) {
            msg.style.color = '#ef4444';
            msg.innerText = 'Error de conexión con el agente.';
          } finally {
            btn.disabled = false;
            btn.innerText = 'Liberar';
          }
        }
        async function testConnection() {
          const response = await fetch('/api/test-connection', { method: 'POST' });
          const data = await response.json();
          alert(data.message || 'Respuesta recibida');
          location.reload();
        }
      </script>
    </body>
  </html>`;
}

function escapeHtml(value) {
  return String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;');
}

export function startWebPanel({ onTestConnection }) {
  const server = http.createServer(async (req, res) => {
    const url = new URL(req.url, `http://${req.headers.host}`);

    // Serve local touchscreen interface at root
    if (url.pathname === '/' || url.pathname === '/index.html') {
      try {
        const filePath = path.join(config.rootDir, 'public', 'index.html');
        const content = await fs.readFile(filePath, 'utf8');
        res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
        res.end(content);
      } catch (err) {
        res.writeHead(500, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ error: 'Failed to load index.html' }));
      }
      return;
    }

    // Serve admin dashboard at /admin
    if (url.pathname === '/admin' || url.pathname === '/admin.html') {
      res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
      res.end(renderAdminPage());
      return;
    }

    // JSON Kiosk State
    if (url.pathname === '/api/state') {
      res.writeHead(200, { 'Content-Type': 'application/json; charset=utf-8' });
      res.end(JSON.stringify(state, null, 2));
      return;
    }

    // Fetch order details from central backend
    if (url.pathname === '/api/order-details') {
      const orderId = url.searchParams.get('id');
      if (!orderId) {
        res.writeHead(400, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ success: false, message: 'Falta parametro ID' }));
        return;
      }

      try {
        const response = await fetch(`${config.centralUrl}/api/kiosk/jobs/${orderId}`, {
          method: 'GET',
          headers: {
            'Accept': 'application/json',
            'X-Kiosk-Token': config.kioskApiToken,
          }
        });

        const text = await response.text();
        res.writeHead(response.status, { 'Content-Type': 'application/json; charset=utf-8' });
        res.end(text);
      } catch (err) {
        log(`Error al consultar orden ${orderId} en backend: ${err.message}`, 'error');
        res.writeHead(500, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ success: false, message: err.message }));
      }
      return;
    }

    // Test connection action
    if (url.pathname === '/api/test-connection' && req.method === 'POST') {
      try {
        const result = await onTestConnection();
        res.writeHead(200, { 'Content-Type': 'application/json; charset=utf-8' });
        res.end(JSON.stringify({ success: true, ...result }));
      } catch (error) {
        log(`Prueba de conexión falló: ${error.message}`, 'error');
        res.writeHead(500, { 'Content-Type': 'application/json; charset=utf-8' });
        res.end(JSON.stringify({ success: false, message: error.message }));
      }
      return;
    }

    // Release order with PIN
    if (url.pathname === '/api/release-pin' && req.method === 'POST') {
      let body = '';
      req.on('data', (chunk) => { body += chunk; });
      req.on('end', async () => {
        try {
          const { pin } = JSON.parse(body || '{}');
          if (!pin) {
            res.writeHead(400, { 'Content-Type': 'application/json; charset=utf-8' });
            res.end(JSON.stringify({ success: false, message: 'Falta PIN' }));
            return;
          }
          const result = await releaseOrderByPin(pin);
          log(`Orden liberada con PIN ${pin} desde panel local: ${result.message}`);
          res.writeHead(200, { 'Content-Type': 'application/json; charset=utf-8' });
          res.end(JSON.stringify(result));
        } catch (error) {
          log(`Fallo al liberar con PIN: ${error.message}`, 'warn');
          res.writeHead(400, { 'Content-Type': 'application/json; charset=utf-8' });
          res.end(JSON.stringify({ success: false, message: error.message }));
        }
      });
      return;
    }

    res.writeHead(404, { 'Content-Type': 'application/json; charset=utf-8' });
    res.end(JSON.stringify({ success: false, message: 'Not found' }));
  });

  server.listen(8787, '127.0.0.1', () => {
    log('Panel local iniciado en http://127.0.0.1:8787');
  });

  return server;
}

const isDirectRun = process.argv[1] && import.meta.url === new URL(`file://${process.argv[1]}`).href;

if (isDirectRun) {
  startWebPanel({
    onTestConnection: async () => ({
      message: 'Panel local activo. Ejecuta src/index.js para conectar con el VPS.',
    }),
  });
}