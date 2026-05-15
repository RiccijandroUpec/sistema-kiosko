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