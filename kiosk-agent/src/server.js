import http from 'node:http';
import { state, log } from './state.js';
import { config } from './config.js';

function renderPage() {
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
      <title>Kiosk Agent</title>
      <style>
        :root { color-scheme: dark; }
        body { margin: 0; font-family: Inter, system-ui, sans-serif; background: linear-gradient(135deg, #0f172a, #111827 45%, #1f2937); color: #e5e7eb; }
        .wrap { max-width: 1100px; margin: 0 auto; padding: 32px; }
        .grid { display: grid; gap: 16px; grid-template-columns: 1.1fr 0.9fr; }
        .card { background: rgba(15, 23, 42, 0.72); border: 1px solid rgba(148, 163, 184, 0.18); border-radius: 20px; padding: 20px; backdrop-filter: blur(16px); box-shadow: 0 20px 60px rgba(0,0,0,.25); }
        h1 { margin: 0 0 8px; font-size: 32px; }
        .muted { color: #94a3b8; }
        .pill { display: inline-flex; align-items: center; padding: 6px 10px; border-radius: 999px; background: rgba(59, 130, 246, 0.12); color: #93c5fd; font-size: 12px; font-weight: 700; }
        .stats { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; margin-top: 16px; }
        .stat { border-radius: 16px; padding: 14px; background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(148, 163, 184, 0.12); }
        .label { color: #94a3b8; font-size: 12px; text-transform: uppercase; letter-spacing: .08em; }
        .value { margin-top: 4px; font-size: 18px; font-weight: 800; word-break: break-word; }
        .buttons { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 16px; }
        button, a.btn { appearance: none; border: 0; cursor: pointer; padding: 12px 16px; border-radius: 14px; font-weight: 800; text-decoration: none; color: #fff; background: linear-gradient(135deg, #2563eb, #7c3aed); }
        button.secondary, a.secondary { background: rgba(30, 41, 59, 0.9); border: 1px solid rgba(148, 163, 184, .2); }
        .logs { display: grid; gap: 10px; max-height: 530px; overflow: auto; }
        .log { border-radius: 14px; padding: 12px 14px; background: rgba(15, 23, 42, 0.75); border: 1px solid rgba(148, 163, 184, 0.12); }
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
          <div class="pill">Interfaz local del kiosko</div>
          <h1>${escapeHtml(state.kioskName || config.kioskName || 'Kiosk Agent')}</h1>
          <div class="muted">Este panel sirve para ver el estado del agente sin abrir la consola.</div>
          <div class="buttons">
            <button onclick="testConnection()">Probar conexión</button>
            <button class="secondary" onclick="location.reload()">Actualizar</button>
            <a class="btn secondary" href="/api/state" target="_blank">Ver JSON</a>
          </div>
          <div class="stats">
            <div class="stat"><div class="label">Estado</div><div class="value">${escapeHtml(state.status)}</div></div>
            <div class="stat"><div class="label">Kiosko ID</div><div class="value">${escapeHtml(String(state.kioskId ?? 'sin registrar'))}</div></div>
            <div class="stat"><div class="label">Último heartbeat</div><div class="value">${escapeHtml(state.lastHeartbeatAt || 'nunca')}</div></div>
            <div class="stat"><div class="label">Última sincronización</div><div class="value">${escapeHtml(state.lastSyncAt || 'nunca')}</div></div>
          </div>
          <div class="footer">
            Central: ${escapeHtml(state.centralUrl || config.centralUrl)} • Modo impresión: ${escapeHtml(config.printMode)}
          </div>
        </div>

        <div class="card">
          <h2 style="margin-top:0">Actividad reciente</h2>
          <div class="logs">${logsHtml}</div>
        </div>
      </div>

      <script>
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

    if (url.pathname === '/' || url.pathname === '/index.html') {
      res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
      res.end(renderPage());
      return;
    }

    if (url.pathname === '/api/state') {
      res.writeHead(200, { 'Content-Type': 'application/json; charset=utf-8' });
      res.end(JSON.stringify(state, null, 2));
      return;
    }

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