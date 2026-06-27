# RickTech - Sistema Híbrido de Kioskos Inteligentes
**Manual Técnico, Arquitectura y Guía de Usuario**

---

## 1. Visión General del Proyecto
RickTech es un sistema integral diseñado para automatizar kioskos de impresión desatendidos. Combina una arquitectura híbrida donde un cerebro central alojado en la nube administra los trabajos, pagos e inteligencia artificial, mientras que un agente local instalado en la computadora del local físico se encarga de la comunicación directa con el hardware (la impresora).

## 2. Tecnologías Utilizadas

### Stack Central (Nube / Servidor)
- **Framework Principal:** Laravel 11 (PHP 8.2+).
- **Panel Administrativo:** FilamentPHP v3 (Interfaz de administración robusta y estética).
- **Base de Datos Central:** MySQL / PostgreSQL (Gestionada por Eloquent ORM).
- **Estilos / Frontend:** Tailwind CSS, Alpine.js, Livewire, Vanilla CSS (Para Landing Page Glassmorphism).
- **Tiempo Real:** Supabase Realtime (WebSockets) para emitir eventos de pago instantáneos.

### Inteligencia Artificial & Comunicaciones
- **Google Gemini 1.5 Flash Vision:** Lee y extrae datos de comprobantes de pago bancarios enviados por los clientes (fotos y screenshots).
- **DeepSeek AI:** Analiza el texto libre de los clientes en WhatsApp para entender si están pidiendo el catálogo, reportando un problema o simplemente saludando.
- **Evolution API (Docker):** Gateway no oficial para WhatsApp que permite conectar un número físico al sistema mediante escaneo de código QR (Multi-Device).

### Kiosk Agent (Hardware Local)
- **Entorno de Ejecución:** Node.js v18+.
- **Empaquetado:** `pkg` (Permite generar ejecutables `.exe` independientes que no requieren que el cliente instale Node.js).
- **Impresión (Windows):** `pdf-to-printer` (Librería que utiliza el ejecutable de SumatraPDF en modo silencioso).
- **Impresión (Linux):** Comandos nativos de CUPS (`lp`).

---

## 3. Arquitectura del Flujo de Trabajo

### El Camino del Usuario
1. **Subida:** El cliente escanea el código QR físico del local o entra a la página web y sube su archivo PDF.
2. **Configuración:** La web le pregunta cuántas copias desea, si es a color y genera un Código de Orden único y un costo dinámico basado en las páginas leídas.
3. **Pago:** El cliente transfiere el dinero al Banco Pichincha o Banco Guayaquil. Envía el comprobante (foto) al WhatsApp del Kiosko.
4. **Inteligencia Artificial:** Evolution API recibe la imagen, Laravel la envía a Gemini Vision. Gemini extrae el valor, la fecha y el banco. Si todo coincide con la orden, Laravel la marca como `pagada`.
5. **Emisión WebSocket:** Supabase detecta que la orden cambió a `pagada` y le grita a todos los clientes suscritos.
6. **Impresión:** El `kiosk-agent` (Node.js), que estaba escuchando en la computadora física, recibe el aviso. Descarga el PDF desde Laravel de manera segura y ejecuta el comando de impresión en segundo plano hacia la impresora configurada.

---

## 4. Manual de Usuario (Administrador)

### Despliegue de un Nuevo Kiosko Físico
1. Enciende la computadora con Windows 10/11 del local y conecta tu impresora (Asegúrate de que imprima bien localmente).
2. Entra al panel central (Filament) en `tusitio.com/admin` y ve a la sección **Kioskos**. Añade uno nuevo y copia el `API Token` generado.
3. Copia el archivo empaquetado `kiosk-agent-win.exe` en el escritorio de la computadora.
4. Crea un archivo `.env` en la misma carpeta con este formato:
   ```env
   CENTRAL_URL=https://tusitio.com
   KIOSK_API_TOKEN=tu_token_copiado_en_el_paso_2
   PRINTER_NAME=Nombre_Exacto_Impresora_En_Windows
   ```
5. Haz doble clic en el ejecutable. ¡El kiosko está en línea!

### Reembolsos de Emergencia
Si un cliente transfiere pero la impresora física sufre un daño catastrófico:
1. En el Panel Admin (Filament), ve a "Órdenes de Impresión".
2. Localiza la orden en rojo (Estado: `error`).
3. Presiona el botón de acción "Reembolsar". El cliente recibirá un WhatsApp avisando que el reembolso está en camino.

### Limpieza de Archivos
- El sistema es 100% autogestionable.
- Laravel eliminará los registros de la base de datos de más de 48 horas todas las madrugadas.
- El Agente Node.js borrará los PDFs físicos de su carpeta de descargas cada 48 horas.

---

## 5. Variables de Entorno Clave (.env)

### Laravel
- `EVOLUTION_API_URL` y `EVOLUTION_API_KEY`: Para la conexión con el bot de WhatsApp.
- `GEMINI_API_KEY`: API Key de Google Studio para validación visual de comprobantes.
- `DEEPSEEK_API_KEY`: API Key para procesar los mensajes NLP de los clientes.
- `ADMIN_PHONE`: Número con prefijo (Ej: +593983185069) para recibir alertas críticas si el kiosko físico falla.

### Kiosk Agent (Node.js)
- `PRINTER_NAME`: Nombre de la cola de impresión del sistema operativo. Si se deja en blanco, usará la impresora predeterminada.

---
*Documento autogenerado por el Sistema de IA de Soporte Técnico RickTech.*
