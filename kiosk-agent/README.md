# Kiosk Agent

Esta carpeta representa el agente local que se instalará en cada kiosko físico.

Aquí debe vivir:
- Autenticación con token contra el VPS
- Heartbeat de conexión
- Consulta de trabajos pendientes
- Descarga de PDFs
- Impresión local
- Reporte de finalización y errores

No debe incluir el panel admin completo ni la lógica de WhatsApp.

## Interfaz Local

El agente incluye un panel local para ver estado y logs.

Abrir en el navegador:

```text
http://127.0.0.1:8787
```

Desde ahí puedes:
- ver el estado del agente,
- revisar logs recientes,
- probar la conexión contra el VPS.

## Empaquetar como ejecutable e instalar en un kiosko físico

El agente se puede compilar a un `.exe`/binario único con `pkg` (via `@yao-pkg/pkg`,
el fork mantenido — el paquete `pkg` original de Vercel está abandonado). Esto evita
tener que instalar Node.js en cada PC de kiosko.

> ⚠️ Compilar necesita bajar binarios base de Node desde internet. Si tu red bloquea
> eso (proxys corporativos restrictivos, CI sandboxeado, etc.) el build falla o cae a
> compilar Node desde el código fuente (tarda muchísimo). Compílalo en una máquina con
> internet normal — tu PC de desarrollo o un runner de GitHub Actions — no en el kiosko.

```bash
cd kiosk-agent
npm install
npm run build:win     # genera build/kiosk-agent-win.exe
npm run build:linux   # genera build/kiosk-agent-linux
# npm run build:exe   # ambos targets de una
```

Copia el `.exe`/binario correspondiente a la PC del kiosko junto con un `.env`
(mismo formato que `.env.example`, con el `KIOSK_API_TOKEN` real de ese kiosko).

### Que arranque solo al prender la PC

El agente **no** se relanza solo si la PC se reinicia (el watchdog interno solo
cubre que el proceso se caiga *mientras ya está corriendo*). Para eso hay un
instalador por sistema operativo en `install/`:

**Windows** (PowerShell como Administrador, en la PC del kiosko):
```powershell
cd install
.\install-windows.ps1 -ExePath "C:\kiosk-agent\kiosk-agent-win.exe"
```
Registra una Tarea Programada que arranca cuando el usuario del kiosko inicia sesión
(necesita ver la impresora mapeada en esa sesión, por eso no corre como SYSTEM) y que
Windows reintenta solo si el proceso se cae. Requiere que la PC tenga inicio de sesión
automático configurado para el usuario del kiosko.

**Linux** (con sudo, en la PC del kiosko):
```bash
cd install
sudo ./install-linux.sh /ruta/al/kiosk-agent-linux
```
Instala un servicio systemd (`Restart=always`) que arranca en cada boot. El usuario que
lo corre necesita estar en el grupo `lp` para poder imprimir via CUPS
(`sudo usermod -aG lp <usuario>` si hace falta).