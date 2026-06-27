# Sistema de Kiosko de Impresiones Inteligente

Sistema híbrido para gestionar kioskos de impresión desatendidos. Los clientes suben PDFs, eligen opciones de impresión y pagan enviando el comprobante por WhatsApp o correo. Todo se valida automáticamente con IA.

## Arquitectura

1. **Servidor Central (Laravel 12):** API, panel administrativo y lógica de negocio.
2. **Kiosk Agent (Node.js):** corre en la PC física del kiosko, recibe trabajos aprobados y los imprime localmente (`pdf-to-printer` / SumatraPDF).
3. **Evolution API (Docker):** conexión WhatsApp Multi-Device, envía webhooks al servidor Laravel.

## Tecnologías

- **Backend:** PHP 8.2+, Laravel 12
- **Base de datos:** Supabase (PostgreSQL)
- **Panel administrativo:** FilamentPHP v3
- **IA de texto:** DeepSeek API (interpreta comandos en lenguaje natural por WhatsApp)
- **IA visual (OCR):** Google Gemini Vision (lee comprobantes de pago)
- **Lector de correos:** `webklex/laravel-imap` (confirma transferencias bancarias por correo)
- **Impresión local:** Node.js + `pdf-to-printer`

## Funcionalidades principales

- Pagos automatizados por foto de comprobante (WhatsApp + Gemini Vision)
- Pagos automatizados por correo (revisión IMAP periódica)
- Bot de WhatsApp (Evolution API): recibe PDFs, cotiza, cobra y da seguimiento
- Panel admin (Filament): gestión de órdenes, kioskos, reembolsos
- Reporte de errores de impresión desde el kiosko al panel, con notificación al cliente y al administrador
- Limpieza automática diaria de órdenes y archivos con más de 48h
- Cálculo de costos según páginas, copias y color

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
- `DEEPSEEK_API_KEY`, `GEMINI_API_KEY`
- `EVOLUTION_API_BASE_URL`, `EVOLUTION_API_KEY`, `EVOLUTION_INSTANCE`
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

- La API de kioskos usa tokens Bearer por kiosko.
- `.env`, `.env.backup` y `.env.production` están en `.gitignore` — nunca se versionan credenciales.
- Evolution API usa locks para evitar mensajes de WhatsApp duplicados o en bucle.
