# Sistema de Kiosko de Impresiones Inteligente

Sistema híbrido para gestionar kioskos de impresión desatendidos. Los clientes suben PDFs, eligen opciones de impresión y pagan enviando el comprobante por WhatsApp o correo. Todo se valida automáticamente con IA.

## Arquitectura

1. **Servidor Central (Laravel 12):** API, panel administrativo y lógica de negocio. Desplegado en Railway (un solo contenedor corre web + `queue:work` + `schedule:run` vía supervisord).
2. **Kiosk Agent (Node.js):** corre en la PC física del kiosko, recibe trabajos aprobados y los imprime localmente (`pdf-to-printer` / SumatraPDF). Siempre local, nunca en la nube.
3. **Evolution API (Docker):** conexión WhatsApp Multi-Device, envía webhooks al servidor Laravel. Desplegado como servicio aparte en Railway, usa un schema dedicado (`evolution`) dentro del mismo proyecto Supabase — no tiene base de datos propia de pago.

## Tecnologías

- **Backend:** PHP 8.2+, Laravel 12
- **Base de datos:** Supabase (PostgreSQL)
- **Panel administrativo:** FilamentPHP v3
- **IA de texto:** DeepSeek API (interpreta comandos en lenguaje natural por WhatsApp)
- **IA visual (OCR):** Google Gemini Vision (lee comprobantes de pago)
- **Lector de correos:** `webklex/laravel-imap` (confirma transferencias bancarias por correo)
- **Impresión local:** Node.js + `pdf-to-printer`

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

**Funciona y está probado en vivo:** flujo completo de pago por WhatsApp/correo, panel admin, impresión real vía kiosk-agent (con copias/rango/color/orientación correctos), storage persistente en Supabase, alertas por WhatsApp, despliegue en Railway (web + evolution-api).

**Pendiente / conocido:**
- `php artisan serve` es de un solo hilo — en una sesión de prueba real, el tráfico de WhatsApp llegando seguido fue suficiente para saturarlo y bloquear otras peticiones. Railway corre lo mismo en producción. Para más volumen, cambiar a nginx+php-fpm o varios workers.
- El kiosk-agent no se recupera solo si falla la autenticación o se cae — hay que reiniciarlo a mano.
- Las alertas dependen 100% de WhatsApp (Evolution API): si esa pieza se cae, no hay ningún aviso de respaldo (correo, etc.).
- Quedan controladores de autenticación huérfanos sin usar (`RegisteredUserController`, `PasswordResetLinkController`, etc.) — no rompen nada, pero son código muerto sin limpiar.
- Un solo número/instancia de WhatsApp (Evolution API) — no escala a muchos kioskos simultáneos sin arquitectura adicional.
- Sin backups automáticos ni error tracking (Sentry u otro) configurado.
- Hay PDFs de clientes reales en el historial de git (`kiosk-agent/downloads`/`output`, de antes del `.gitignore`) — pendiente de limpiar si importa la privacidad de esos archivos.
