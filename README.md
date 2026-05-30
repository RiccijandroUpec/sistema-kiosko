# Sistema de Kiosko de Impresiones Inteligente 🖨️🤖

Un sistema web y local híbrido de última generación para gestionar kioskos de impresión desatendidos. Permite a los usuarios subir PDFs, configurar sus opciones de impresión y pagar automáticamente enviando comprobantes bancarios por WhatsApp o por correo electrónico. Todo está impulsado por Inteligencia Artificial.

## Arquitectura del Sistema

Este proyecto cuenta con una arquitectura híbrida distribuida:

1. **Servidor Central (Laravel 12):** En la carpeta raíz, maneja la API, la interfaz de usuario, el panel de administración, y se comunica con la nube.
2. **Kiosk Agent (Node.js):** En la carpeta `kiosk-agent/`, es un agente ligero que corre en la computadora física del kiosko, escucha los trabajos aprobados y los envía directamente a la impresora local (usando `pdf-to-printer` / SumatraPDF).
3. **Evolution API (Docker):** Sistema local para conexión de WhatsApp Multi-Device, envía Webhooks al servidor de Laravel.

## Tecnologías Principales 🚀

- **Backend:** PHP 8.2+, Laravel 12
- **Base de Datos & Almacenamiento:** Supabase (PostgreSQL + Supabase Storage S3)
- **Panel Administrativo:** FilamentPHP v3
- **Inteligencia Artificial Textual:** DeepSeek API (OpenAI-compatible) para entender comandos en lenguaje natural vía WhatsApp (ej: *"quiero 2 copias a color"*).
- **Inteligencia Artificial Visual (OCR):** Google Gemini 1.5 Flash Vision API para analizar fotos de comprobantes de pago enviados por WhatsApp y extraer el monto/referencia.
- **Lector de Correos:** `webklex/laravel-imap` para monitorear correos de confirmación de transferencias bancarias en tiempo real.
- **Motor de Impresión Local:** Node.js + `pdf-to-printer` (SumatraPDF) bypassando los diálogos de Windows.

## Características Principales

- **Gestión Automatizada de Pagos (Visión por IA):** Los clientes mandan foto de su transferencia por WhatsApp. Gemini extrae el monto y número de comprobante, lo cruza con su orden, y libera la impresión en segundos.
- **Gestión Automatizada de Pagos (Correos):** El sistema revisa cada minuto la bandeja de entrada del administrador (IMAP) para cruzar los comprobantes anotados en la web con los correos del banco (Pichincha, DeUna, etc).
- **Bot Inteligente de WhatsApp:** Integración con Evolution API. Recibe PDFs directamente por WhatsApp, pregunta opciones de impresión, cotiza y cobra.
- **Panel Administrativo (Filament):** Dashboard en tiempo real (`/admin/kioskos`), gestión de trabajos, configuración de precios por kiosko, y control total.
- **Cálculo de Costos:** Automático basado en páginas, copias y color.

## Requisitos del Sistema

- PHP 8.2+
- Composer
- Node.js 18+ (Para el agente y compilar assets)
- Docker Desktop (Solo para correr Evolution API localmente)
- Cuenta en Supabase (Gratis)
- APIs: DeepSeek (Texto), Google Gemini (Visión)

## Instalación y Despliegue

### 1. Clonar e Instalar Servidor Central
```bash
git clone https://tu-repo sistema-kiosko
cd sistema-kiosko
composer install
npm install
npm run build
```

### 2. Configurar Variables de Entorno (.env)
```env
# Conexión Supabase
DB_CONNECTION=pgsql
DB_HOST=aws-0-....pooler.supabase.com
DB_PORT=6543
DB_DATABASE=postgres
DB_USERNAME=postgres.xxxx
DB_PASSWORD=xxxx
SUPABASE_URL=xxxx
SUPABASE_ANON_KEY=xxxx

# Evolution API
EVOLUTION_API_BASE_URL=http://localhost:8080
EVOLUTION_API_KEY=xxxx
EVOLUTION_WHATSAPP_NUMBER=+593...

# Inteligencia Artificial
DEEPSEEK_API_KEY=xxxx
GEMINI_API_KEY=xxxx

# Lector de Correos IMAP
IMAP_HOST=imap.gmail.com
IMAP_PORT=993
IMAP_ENCRYPTION=ssl
IMAP_USERNAME="tu-correo@gmail.com"
IMAP_PASSWORD="tu-contraseña-app"
```

### 3. Base de Datos y Trabajadores
```bash
# Migraciones
php artisan migrate

# Encender el sistema de colas y CRON (Revisión de Correos)
php artisan schedule:work
```

### 4. Inicializar Kiosk Agent (La Computadora Física)
```bash
cd kiosk-agent
npm install
# Iniciar el agente que conectará tu impresora local al servidor
npm start
```

### 5. Iniciar Evolution API (WhatsApp)
Asegúrate de que Docker esté abierto y ejecuta:
```bash
docker start evolution_api
```
*(Si es la primera vez, utiliza `docker-compose up -d` desde la carpeta correspondiente o clónalo de su repo oficial).*

## Estructura del Proyecto
```
├── kiosk-agent/              # Agente Node.js de impresión local
├── app/
│   ├── Filament/             # Panel de Administración de Kioskos
│   ├── Http/Controllers/     # Controladores Web y Webhooks
│   │   └── WhatsAppController.php # Cerebro del Bot y Webhooks
│   ├── Models/               # Modelos BD
│   └── Services/             
│       ├── DeepseekService.php       # IA de Texto
│       ├── GeminiVisionService.php   # IA Visual (Recibos)
│       ├── EvolutionService.php      # API de WhatsApp
│       └── PaymentVerificationService.php # Escucha de Correos
├── database/                 # Migraciones para Supabase
└── public/                   # Archivos expuestos
```

## Seguridad y Privacidad
- La API de Kioskos usa tokens de Bearer locales.
- Supabase se comunica de forma cifrada (SSL).
- Las contraseñas de aplicación de Gmail y los Webhooks están protegidos.
- Evolution API usa caché y *locks* (bloqueos) para evitar mensajes duplicados y procesamiento en bucle.

---
**Última actualización:** Mayo de 2026
**Desarrollado con ❤️ y mucha IA.**