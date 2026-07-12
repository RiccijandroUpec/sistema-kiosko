#!/bin/bash
# Instala el kiosk-agent como servicio systemd: arranca solo al prender la PC
# y se reinicia solo si el proceso se cae (Restart=always), sin depender de
# que alguien lo levante a mano en la sucursal.
#
# Uso (como root o con sudo):
#   ./install-linux.sh /ruta/al/kiosk-agent-linux [usuario_del_kiosko]
#
# Requisitos previos:
#   - El ejecutable ya construido (npm run build:exe) en la PC del kiosko.
#   - Un .env junto al ejecutable con KIOSK_API_TOKEN, CENTRAL_URL, etc.
#   - El usuario indicado debe poder imprimir por CUPS (grupo "lp" o
#     impresora compartida sin password), porque el servicio corre como el.

set -euo pipefail

if [[ $EUID -ne 0 ]]; then
    echo "Este script necesita sudo/root (crea una unit de systemd)." >&2
    exit 1
fi

EXE_PATH="${1:-}"
SERVICE_USER="${2:-$(logname 2>/dev/null || echo "$SUDO_USER")}"

if [[ -z "$EXE_PATH" || ! -f "$EXE_PATH" ]]; then
    echo "Uso: $0 /ruta/al/kiosk-agent-linux [usuario]" >&2
    echo "No se encontro el ejecutable. Compila primero con 'npm run build:exe'." >&2
    exit 1
fi

if [[ -z "$SERVICE_USER" ]]; then
    echo "No se pudo determinar el usuario del kiosko. Pasalo como segundo argumento." >&2
    exit 1
fi

EXE_PATH="$(readlink -f "$EXE_PATH")"
WORKING_DIR="$(dirname "$EXE_PATH")"
chmod +x "$EXE_PATH"

if [[ ! -f "$WORKING_DIR/.env" ]]; then
    echo "Advertencia: no hay .env junto al ejecutable en $WORKING_DIR. El agente no podra autenticarse sin KIOSK_API_TOKEN." >&2
fi

if ! id -nG "$SERVICE_USER" | grep -qw lp; then
    echo "Advertencia: '$SERVICE_USER' no esta en el grupo 'lp'. Puede no tener permiso para imprimir via CUPS." >&2
    echo "  Arreglalo con: sudo usermod -aG lp $SERVICE_USER" >&2
fi

UNIT_PATH="/etc/systemd/system/kiosk-agent.service"

cat > "$UNIT_PATH" <<EOF
[Unit]
Description=Kiosk Agent - Impresion Local
After=network-online.target
Wants=network-online.target
StartLimitIntervalSec=0

[Service]
Type=simple
User=${SERVICE_USER}
WorkingDirectory=${WORKING_DIR}
ExecStart=${EXE_PATH}
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable kiosk-agent.service
systemctl restart kiosk-agent.service

echo "Servicio 'kiosk-agent' instalado y arrancado. Va a levantar solo en cada reinicio de la PC."
echo "Ver estado:  systemctl status kiosk-agent"
echo "Ver logs:    journalctl -u kiosk-agent -f"
