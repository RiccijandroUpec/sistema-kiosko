# Sistema de Kiosko de Impresiones Inteligente

Sistema híbrido para gestionar kioskos de impresión desatendidos. Los clientes suben PDFs, eligen opciones de impresión y pagan enviando el comprobante por WhatsApp o correo. Todo se valida automáticamente con IA.

## Estado del proyecto

El flujo principal ya fue probado de extremo a extremo en Windows con una impresora Epson L4360:

- PDF subido desde la página del kiosko.
- PDF validado y páginas detectadas correctamente.
- Configuración de color, copias, orientación y rango de páginas guardada.
- Precio calculado y orden creada.
- Orden marcada como pagada para la prueba.
- Kiosk Agent autenticado contra la API central.
- PDF descargado por el agente.
- Trabajo enviado físicamente a `EPSONF878E1 (L4360 Series)`.
- Orden marcada como completada después de imprimir.

La prueba local usa Laravel con MySQL de XAMPP. La arquitectura de producción está preparada para Laravel + PostgreSQL/Supabase + Storage persistente.

## Arquitectura

1. **Servidor Central (Laravel 12):** API, panel administrativo y lógica de negocio. Una sola instalación administra muchos kioskos.
2. **Kiosk Agent (Node.js):** corre en la PC física de cada kiosko, recibe trabajos aprobados y los imprime localmente (`pdf-to-printer` / SumatraPDF). Debe existir un agente por computadora/impresora.
3. **Evolution API (Docker):** conexión WhatsApp Multi-Device, envía webhooks al servidor Laravel. Desplegado como servicio aparte en Railway, usa un schema dedicado (`evolution`) dentro del mismo proyecto Supabase — no tiene base de datos propia de pago.

El agente local no es un segundo sistema de negocio: es el puente entre la API central y la impresora física.

```text
Cliente web o WhatsApp
	|
	v
Servidor Laravel central
  BD + Storage + pagos + API
	|
	v
Kiosk Agent del kiosko asignado
	|
	v
Impresora local
```

## Tecnologías

- **Backend:** PHP 8.2+, Laravel 12
- **Base de datos:** Supabase (PostgreSQL)
- **Panel administrativo:** FilamentPHP v3
- **IA de texto:** DeepSeek API (interpreta comandos en lenguaje natural por WhatsApp)
- **IA visual (OCR):** Google Gemini Vision (lee comprobantes de pago)
- **Lector de correos:** `webklex/laravel-imap` (confirma transferencias bancarias por correo)
- **Impresión local:** Node.js + `pdf-to-printer`
- **Desarrollo local probado:** XAMPP MySQL + `php artisan serve` + Kiosk Agent en Windows
- **Producción prevista:** Railway + Supabase PostgreSQL/Storage + Evolution API

## Funcionalidades principales

- **Cada kiosko tiene su propia URL fija** (`/k/{slug}`): el cliente entra directo a subir su archivo en la sede correcta, sin elegir nada ni confundirse de lugar. La home (`/`) ya no es un flujo de cliente — es la landing de negocio (pitch para dueños de local).
- Pagos automatizados por foto de comprobante (WhatsApp + Gemini Vision)
- Pagos automatizados por correo (revisión IMAP periódica, con búsqueda optimizada por referencia en el servidor)
- Bot de WhatsApp (Evolution API): recibe PDFs, cotiza, cobra y da seguimiento
- Panel admin (Filament): gestión de órdenes, kioskos, reembolsos — login unificado en `/login` con 3 modos (PIN de kiosko, email/password de admin, PIN de admin)
- **Copias, rango de páginas personalizado, color/B-N y orientación se respetan de verdad** al imprimir (antes eran solo decorativos en pantalla: se cobraba por N copias pero solo se imprimía 1, por ejemplo)
- Reporte de errores de impresión desde el kiosko al panel, con notificación al cliente y al administrador
- **Alertas automáticas por WhatsApp al admin**: kiosko desconectado (sin heartbeat 5+ min), pago sin verificar atascado (25+ min), o si la verificación de correos falla
- Limpieza automática diaria de órdenes y archivos con más de 48h (BD y Supabase Storage)
- Cálculo de costos según páginas realmente impresas (respeta el rango personalizado), copias y color
- PDFs respaldados en Supabase Storage (no se pierden si el servidor se redespliega)
- Rate limiting en login por PIN, liberar orden por PIN y API de kioskos

## Configuración de impresión y precios

Cada kiosko tiene su propia configuración de precios y su propia impresora. Actualmente se pueden configurar:

- Precio por página blanco y negro.
- Precio por página a color.
- Número de copias, entre 1 y 999.
- Todas las páginas o un rango personalizado, por ejemplo `1-5,8`.
- Tamaño de papel soportado por backend: A4, Letter y Legal.
- Orientación vertical u horizontal.

El costo actual se calcula así:

```text
páginas seleccionadas × copias × precio por página
```

El precio se vuelve a calcular en el servidor al crear la orden; el valor mostrado en el navegador no es la fuente de seguridad. El precio queda guardado en la orden y no cambia si el administrador modifica las tarifas después.

La interfaz web actualmente muestra A4 como opción de papel. Letter y Legal ya están aceptados por el backend, pero todavía deben exponerse como selector visible. La impresión a doble cara todavía no está implementada.

## Requisitos

- PHP 8.2+, Composer
- Node.js 18+
- Docker Desktop (solo para Evolution API)
- Cuenta de Supabase
- API keys: DeepSeek, Google Gemini

## Instalación

```bash
git clone https://github.com/RiccijandroUpec/sistema-kiosko.git
cd sistema-kiosko
composer install
npm install
npm run build
cp .env.example .env   # completar con tus credenciales (ver más abajo)
php artisan key:generate
php artisan migrate
```

### Variables de entorno necesarias (`.env`)

No se versionan los valores reales. Completa tu propio `.env` con:

- `DB_*` — conexión a tu proyecto de Supabase (Postgres, modo pooler)
- `SUPABASE_URL`, `SUPABASE_ANON_KEY` — **deben pertenecer al mismo proyecto** que las credenciales `DB_*`
- `SUPABASE_SERVICE_KEY` — clave `service_role` (Settings → API en Supabase), usada por el servidor para subir/borrar PDFs en Storage. Nunca usar la `anon key` para esto: las políticas de seguridad bloquean escritura sin ella.
- `SUPABASE_STORAGE_BUCKET` — normalmente `pdfs`. El bucket debe existir y estar marcado como público (lectura pública; la escritura siempre va por `service_role`, sin importar el toggle de público).
- `DEEPSEEK_API_KEY`, `GEMINI_API_KEY`
- `EVOLUTION_API_BASE_URL`, `EVOLUTION_API_KEY`, `EVOLUTION_INSTANCE`
- `EVOLUTION_WEBHOOK_SECRET` — genera un valor random y configura el webhook en Evolution API como `https://tu-app.com/webhook-bot?secret=ESE_VALOR`. Sin esto, el webhook acepta peticiones de cualquiera sin verificar que vengan realmente de Evolution.
- `IMAP_HOST`, `IMAP_USERNAME`, `IMAP_PASSWORD` (contraseña de aplicación, no la real)
- `ADMIN_PHONE`

> ⚠️ Si `SUPABASE_URL` y `DB_USERNAME` apuntan a proyectos distintos de Supabase, la conexión a base de datos falla con `tenant/user not found`.

### Levantar el servidor

```bash
php artisan serve
php artisan queue:work
php artisan schedule:work   # corre la verificación de pagos por correo cada minuto
```

### Desarrollo local con XAMPP

Para la prueba local se utilizó MySQL de XAMPP:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kiosko_impresiones
DB_USERNAME=root
DB_PASSWORD=
```

Después de configurar la base de datos:

```bash
php artisan migrate:fresh --seed
php artisan serve --host=127.0.0.1 --port=8000
```

La URL del kiosko de prueba fue `http://127.0.0.1:8000/k/central`.

### Levantar WhatsApp (Evolution API)

```bash
docker compose -f docker-compose-evolution.yml up -d
```

### Levantar el Kiosk Agent (en la PC del kiosko)

```bash
cd kiosk-agent
npm install
cp .env.example .env   # completar CENTRAL_URL y KIOSK_API_TOKEN
npm start
```

`npm start` corre el agente a través de un watchdog (`src/watchdog.js`): si el proceso se cae por cualquier motivo (excepción no atrapada, pérdida de red al arrancar) se reinicia solo con backoff, sin que alguien tenga que ir a la PC del kiosko a reiniciarlo a mano. Para correr el proceso sin el watchdog (debugging), usa `npm run start:direct`.

Variables importantes del agente:

```env
CENTRAL_URL=http://127.0.0.1:8000
KIOSK_API_TOKEN=<token-unico-del-kiosko>
KIOSK_NAME=Kiosko Central - Epson L4360
PRINTER_NAME=EPSONF878E1 (L4360 Series)
POLL_INTERVAL_MS=5000
LOCAL_PANEL_PORT=8787
```

El nombre de `PRINTER_NAME` debe coincidir exactamente con el nombre instalado en Windows. El panel local del agente está disponible en `http://127.0.0.1:8787`.

Para agregar otro kiosko no se duplica Laravel: se registra el kiosko, se genera un token diferente y se instala/configura un nuevo agente en la computadora conectada a su impresora.

## Despliegue en Railway

El `Dockerfile` raíz ya está listo para Railway (incluye `pdo_pgsql`, `mbstring`, `supervisord`). Servicios necesarios en el proyecto:

- **web**: build desde este repo (Dockerfile). Corre web + queue + scheduler en un solo contenedor. Variables: las del `.env` de arriba.
- **evolution-api**: imagen Docker `evoapicloud/evolution-api:latest`. `DATABASE_CONNECTION_URI` debe usar el **pooler en modo sesión (puerto 5432)** de Supabase, no el de transacción (6543) — Prisma no migra bien sobre pgbouncer en modo transacción. Apunta a un schema propio (`?schema=evolution`) para no mezclar tablas con la app principal.

No se necesita una base de datos de Railway: ambos servicios usan el mismo proyecto Supabase.

## Estructura del proyecto

```
├── kiosk-agent/              # Agente Node.js de impresión local
├── app/
│   ├── Filament/             # Panel de administración
│   ├── Http/Controllers/     # Controladores y webhooks
│   ├── Console/Commands/     # Comandos artisan (verificación de pagos, limpieza)
│   ├── Models/
│   └── Services/
│       ├── DeepseekService.php
│       ├── GeminiVisionService.php
│       ├── EvolutionService.php
│       └── PaymentVerificationService.php
├── database/                 # Migraciones
└── public/
```

## Seguridad

- La API de kioskos usa un `api_token` propio por kiosko (columna separada, oculta en el modelo), **no** su UUID: el UUID aparece en URLs públicas (poster, QR, formulario de configuración), así que nunca sirve como credencial. El token real se ve/regenera desde el panel Filament (acción "Regenerar Token" en la tabla de Kioskos) y va en `KIOSK_API_TOKEN` del `.env` del kiosk-agent.
- El webhook de WhatsApp (`/webhook-bot`) exige `EVOLUTION_WEBHOOK_SECRET` como query param (`?secret=...`) o header `X-Webhook-Secret`; sin él, cualquiera podría simular mensajes entrantes.
- Verificación de pago por imagen (WhatsApp + Gemini Vision): un mismo comprobante no puede reutilizarse para liberar dos órdenes (se rechaza si esa referencia ya fue usada), y si no hay match por cliente solo se libera automáticamente cuando existe una única orden pendiente con ese monto exacto en todo el sistema.
- La API de kioskos envía ese token mediante el encabezado `X-Kiosk-Token`.
- `.env`, `.env.backup` y `.env.production` están en `.gitignore` — nunca se versionan credenciales.
- Evolution API usa locks para evitar mensajes de WhatsApp duplicados o en bucle.
- Rate limiting (`throttle`) en login por PIN del panel de kiosko (scoped al kiosko elegido), login PIN de admin, liberar orden por PIN, subida/creación de trabajos, y la API de kioskos/webhook.
- Subida a Supabase Storage solo con `service_role` key desde el servidor — nunca con la `anon key`.

## Tests

```bash
php artisan test
```

Corren contra SQLite en memoria (`phpunit.xml`), nunca contra la base de datos real. Cubren lo más crítico del negocio:
- `PrintJobCreationTest` — cálculo de costos (documento completo, rango personalizado, rango inválido)
- `KioskApiTest` — autenticación, heartbeat, `pendingJobs`, descarga de PDF (local y externa), `completeJob`, `reportError`
- `CheckSystemHealthCommandTest` — alertas de kiosko desconectado y pago atascado (sin duplicarse)

Los 14 tests que siguen fallando (`Tests\Feature\Auth\*`, `ProfileTest`) son el scaffolding por defecto de Laravel — prueban rutas (`/login` clásico, `/profile`, registro, reset de password) desactivadas desde la migración a FilamentPHP. No es una regresión, nunca se reconectaron.

## Estado actual y pendientes

**Funciona y está probado:** flujo web local, panel administrativo, creación de órdenes, cálculo de precios, autenticación del agente, descarga del PDF e impresión física con Epson L4360. También están implementados los componentes de pagos por WhatsApp/correo, Storage persistente, alertas y despliegue en Railway.

**Resuelto recientemente:**
- El contenedor web ahora corre **nginx + php-fpm** en vez de `php artisan serve` (que es de un solo hilo y se saturaba con tráfico real de WhatsApp, confirmado en pruebas en vivo). Sirven requests en paralelo de verdad; sin cambios en el código de la app.
- El kiosk-agent ya no se queda muerto si falla la autenticación o se cae: reintenta con backoff y corre bajo un watchdog (`npm start`) que lo reinicia solo ante una excepción no atrapada.
- `WhatsAppController` (webhook, matching de pagos por imagen) tenía cero tests; ahora tiene cobertura de los casos de fraude/seguridad corregidos (`tests/Feature/WhatsAppWebhookTest.php`).

**Pendiente / conocido:**
- Unificar completamente las opciones del flujo web y WhatsApp. El flujo de WhatsApp actualmente configura principalmente copias y color; debe compartir el mismo servicio de cálculo que la web para evitar diferencias.
- Mejorar el selector de papel para mostrar A4, Letter y Legal y, si aplica, cobrar tarifas diferentes.
- Validar rangos de páginas también en la interfaz y bloquear el pago si el rango es inválido, en lugar de mostrar temporalmente un total cero.
- Agregar impresión a doble cara cuando la impresora del kiosko la soporte.
- Reemplazar cálculos monetarios con `float` por centavos enteros o una estrategia decimal precisa.
- Agregar capacidades por kiosko: tamaños de papel, color, doble cara, límite de copias y estado de impresora.
- Definir políticas comerciales: precio mínimo por orden, precio mínimo por página y posibles recargos.
- Las alertas dependen 100% de WhatsApp (Evolution API): si esa pieza se cae, no hay ningún aviso de respaldo (correo, etc.).
- Quedan controladores de autenticación huérfanos sin usar (`RegisteredUserController`, `PasswordResetLinkController`, etc.) — no rompen nada, pero son código muerto sin limpiar.
- Un solo número/instancia de WhatsApp (Evolution API) — para muchos kioskos conviene separar canales, webhooks y colas por volumen.
- Sin backups automáticos ni error tracking (Sentry u otro) configurado.
- Hay PDFs de clientes reales en el historial de git (`kiosk-agent/downloads`/`output`, de antes del `.gitignore`) — pendiente de limpiar si importa la privacidad de esos archivos.
<<<<<<< HEAD
- La verificación de pago sigue basada en OCR (WhatsApp) y parseo de correo (IMAP) en vez de una integración real con la pasarela de pago — es la fuente de la mayoría de los casos borde de fraude/latencia. Requiere gestión comercial con el banco/Deuna, no es solo código.
- `PITCH_DECK.md` describe una arquitectura multi-tenant ("1000+ kiosks", Grafana, etc.) que todavía no existe: hoy es de un solo dueño/admin, con un único número de WhatsApp compartido.

### Ruta recomendada de escalabilidad

1. Publicar Laravel con una URL HTTPS y mover producción a Supabase PostgreSQL/Storage.
2. Registrar cada kiosko con un token independiente y configurar un agente local por impresora.
3. Usar nginx + PHP-FPM y workers separados para colas y tareas programadas.
4. Centralizar el cálculo de precios y validación de opciones para web y WhatsApp.
5. Añadir supervisión automática del agente, backups, logs centralizados y error tracking.
6. Medir órdenes, tiempos de impresión, fallos, ingresos y disponibilidad por kiosko desde Filament.
