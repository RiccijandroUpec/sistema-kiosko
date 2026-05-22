# Guía de Despliegue - Sistema Listo para Producción

## Pre-requisitos (Verificar antes de comenzar)

### Cuentas & Claves Requeridas
```bash
☐ Railway Account (railway.app) - Hosting central
☐ Supabase Project - Base de datos + Realtime + Storage
☐ DeepSeek API Key - Inteligencia artificial
☐ Evolution API Setup - Gateway WhatsApp
☐ Domain Name - Para servidor central
☐ GitHub Repository - Para CD/CI automático
☐ SSL Certificate (auto-generado por Railway) - Seguridad
```

### Ambiente Local
```bash
☐ Docker & Docker Compose
☐ Node.js 18+ (para desarrollo)
☐ PHP 8.3+ (para desarrollo local)
☐ Composer (gestor de dependencias PHP)
☐ Git
☐ Terminal/PowerShell (Windows) o Bash (Linux/Mac)
```

### Permisos de Red
```bash
☐ Puertos abiertos: 8000 (Laravel), 3000 (Evolution)
☐ CORS habilitado en Supabase para tu dominio
☐ Webhook URL accesible desde internet
```

---

## Parte 1: Configuración de Servicios Externos (30 minutos)

### 1.1 Railway Setup

**Paso 1:** Ir a https://railway.app y crear cuenta
```
Email → Contraseña → GitHub Connect
```

**Paso 2:** Crear nuevo proyecto desde GitHub
```
"New Project" → "Import from GitHub" → Seleccionar tu repo
```

**Paso 3:** Variables de Entorno
```bash
APP_NAME=Sistema Kiosko Impresiones
APP_ENV=production
APP_KEY=base64:$(php artisan key:generate --show)
APP_URL=https://yourdomain.railway.app

DB_CONNECTION=pgsql
DB_HOST=postgres.railway.internal
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=$(openssl rand -base64 32)

DEEPSEEK_API_KEY=sk-xxxxx # De https://api.deepseek.com
DEEPSEEK_MODEL=deepseek-chat

SUPABASE_URL=https://xxxxx.supabase.co
SUPABASE_ANON_KEY=eyJxxx
SUPABASE_SERVICE_KEY=eyJxxx

EVOLUTION_API_URL=http://evolution:3000
EVOLUTION_API_KEY=secretkey123
```

**Paso 4:** Comandos de Despliegue
```
Build Command:
composer install && php artisan optimize && php artisan migrate

Start Command:
php artisan serve --host=0.0.0.0 --port=8000
```

**Paso 5:** Habilitar Railway Database (PostgreSQL)
```
Agregar servicio → PostgreSQL 15 → Connect
Las credenciales se rellenan automáticamente en variables de entorno
```

---

### 1.2 Supabase Configuration

**Paso 1:** Crear proyecto en https://supabase.com

**Paso 2:** Crear tablas desde SQL Editor
```sql
-- TABLA: USUARIOS
CREATE TABLE users (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  email VARCHAR(255) UNIQUE NOT NULL,
  phone_whatsapp VARCHAR(20),
  password_hash VARCHAR(255) NOT NULL,
  is_active BOOLEAN DEFAULT true,
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
);

-- TABLA: KIOSKOS
CREATE TABLE kiosks (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  name VARCHAR(100) NOT NULL,
  location VARCHAR(255) NOT NULL,
  printer_name VARCHAR(100) NOT NULL,
  status VARCHAR(20) DEFAULT 'offline',
  last_heartbeat TIMESTAMP,
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
);

-- TABLA: ÓRDENES DE IMPRESIÓN
CREATE TABLE print_jobs (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  kiosk_id UUID NOT NULL REFERENCES kiosks(id) ON DELETE RESTRICT,
  pdf_url VARCHAR(500) NOT NULL,
  status VARCHAR(50) DEFAULT 'pending',
  pages_count INTEGER,
  created_at TIMESTAMP DEFAULT NOW(),
  completed_at TIMESTAMP,
  updated_at TIMESTAMP DEFAULT NOW()
);

-- TABLA: TRANSACCIONES
CREATE TABLE transactions (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  amount DECIMAL(10,2) NOT NULL,
  currency VARCHAR(3) DEFAULT 'USD',
  method VARCHAR(50) NOT NULL,
  status VARCHAR(50) DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
);

-- TABLA: SESIONES DE WHATSAPP
CREATE TABLE whatsapp_sessions (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  phone_number VARCHAR(20) UNIQUE NOT NULL,
  status VARCHAR(20) DEFAULT 'active',
  conversation_context JSONB DEFAULT '{}',
  last_message_at TIMESTAMP,
  created_at TIMESTAMP DEFAULT NOW()
);

-- ÍNDICES PARA PERFORMANCE
CREATE INDEX idx_print_jobs_user_id ON print_jobs(user_id);
CREATE INDEX idx_print_jobs_kiosk_id ON print_jobs(kiosk_id);
CREATE INDEX idx_print_jobs_status ON print_jobs(status);
CREATE INDEX idx_transactions_user_id ON transactions(user_id);
CREATE INDEX idx_whatsapp_phone ON whatsapp_sessions(phone_number);

-- HABILITAR ROW LEVEL SECURITY (RLS)
ALTER TABLE users ENABLE ROW LEVEL SECURITY;
ALTER TABLE print_jobs ENABLE ROW LEVEL SECURITY;
ALTER TABLE transactions ENABLE ROW LEVEL SECURITY;

-- POLÍTICAS DE SEGURIDAD
CREATE POLICY "Users can view own data" 
  ON users FOR SELECT 
  USING (auth.uid() = id);

CREATE POLICY "Users can view own orders" 
  ON print_jobs FOR SELECT 
  USING (auth.uid() = user_id);
```

**Paso 3:** Crear Buckets de Storage
```
Storage → Crear nuevo bucket → "pdfs" (Público)
Storage → Crear nuevo bucket → "receipts" (Privado)
```

**Paso 4:** Habilitar Realtime
```
Database → Supabase Realtime → Enable for print_jobs table
(Habilita WebSocket pub/sub automáticamente)
```

**Paso 5:** Configurar CORS
```
Settings → API → CORS Configuration
Agregar: https://yourdomain.railway.app
```

---

### 1.3 DeepSeek API Setup

**Paso 1:** Registrar en https://platform.deepseek.com
```
Email → Verificación → Crear API Key
```

**Paso 2:** Obtener API Key
```
Settings → API Keys → Create New Key
Copiar key: sk-xxxxxxxxxxxxxxxx
Agregar a Railway env var: DEEPSEEK_API_KEY=sk-xxx
```

**Paso 3:** Configurar modelo (en app/Services/DeepseekService.php)
```php
$model = 'deepseek-chat'; // Modelo por defecto
$maxTokens = 1000;
$temperature = 0.7; // Balance entre creatividad y determinismo
```

---

### 1.4 Evolution API Setup

**Paso 1:** Clonar repositorio
```bash
git clone https://github.com/EvolutionAPI/evolution-api.git
cd evolution-api
```

**Paso 2:** Crear .env
```env
DATABASE_URL=postgresql://user:pass@host:port/evolution_db
API_SECRET=your-secret-key-min-32-chars
LOG_LEVEL=info
WEBHOOK_URL=https://yourdomain.railway.app/api/whatsapp/webhook
```

**Paso 3:** Desplegar con Docker (en Railway)
```bash
docker build -t evolution-api .
docker run -d -p 3000:3000 \
  -e DATABASE_URL=$DATABASE_URL \
  -e API_SECRET=$API_SECRET \
  evolution-api
```

**Paso 4:** Obtener QR Code y vincular WhatsApp
```bash
curl -X POST http://evolution:3000/instance \
  -H "Authorization: Bearer $API_SECRET" \
  -H "Content-Type: application/json" \
  -d '{"instanceName":"my_business"}'

# Respuesta incluirá QR code
```

**Paso 5:** Escanear QR con tu número de WhatsApp
```
Se vinculará automáticamente
Status: Instance activa
```

---

## Parte 2: Despliegue Local (Testing) - 20 minutos

### 2.1 Clonar y Configurar Proyecto

```bash
# Clonar proyecto
git clone https://github.com/yourusername/sistema-kiosko.git
cd sistema-kiosko

# Copiar variables de entorno
cp .env.example .env

# Completar variables (usar datos de Railway + Supabase)
nano .env
```

### 2.2 Instalar Dependencias

```bash
# PHP Dependencies
composer install

# Node Dependencies (para Vite + dev tools)
npm install

# Generar app key
php artisan key:generate

# Ejecutar migraciones
php artisan migrate
```

### 2.3 Servir en Local

```bash
# Terminal 1: Laravel Server
php artisan serve

# Terminal 2: Vite Dev Server
npm run dev

# Terminal 3 (opcional): Queue Worker
php artisan queue:work
```

**Verificar:**
- Frontend: http://localhost:5173
- API: http://localhost:8000/api/health
- Dashboard Filament: http://localhost:8000/admin

---

### 2.4 Desplegar Agente Local (Kiosk Agent)

```bash
# Navegar a agente
cd kiosk-agent

# Instalar dependencias
npm install

# Configurar variables (crear .env)
cp .env.example .env

# Editar con tu Kiosk ID y CUPS printer name
nano .env
```

**`.env` del agente:**
```env
SUPABASE_URL=https://xxxxx.supabase.co
SUPABASE_SERVICE_KEY=eyJxxx
KIOSK_ID=your-kiosk-uuid
PRINTER_NAME=HP-LaserJet-4050
LOG_LEVEL=info
```

**Ejecutar agente:**
```bash
# Modo desarrollo
npm run dev

# Modo producción
npm start

# O en Docker
docker build -t kiosk-agent .
docker-compose up -d
```

**Verificar:**
```bash
# El agente debe conectarse a Realtime de Supabase
# y escuchar nuevas órdenes automáticamente
tail -f logs/kiosk-agent.log
```

---

## Parte 3: Testing End-to-End - 30 minutos

### 3.1 Test de Flujo Web

```bash
1. Ir a http://localhost:5173
2. Registrar usuario
3. Login
4. Subir un PDF de prueba
5. Seleccionar kiosk
6. Verificar que aparece en BD (print_jobs con status='pending')
7. Verificar que el agente local lo captura automáticamente
8. Verificar que se envía a la impresora (check CUPS queue)
9. Verificar que status cambia a 'completed' en BD
```

### 3.2 Test de Flujo WhatsApp

```bash
1. Enviar mensaje a WhatsApp del bot: "Hola"
2. Verificar que Evolution recibe webhook
3. Verificar que DeepSeek procesa intent
4. Verificar que Laravel ejecuta lógica
5. Verificar que bot responde por WhatsApp
6. Enviar PDF adjunto
7. Verificar que se guarda en Storage
8. Verificar que se crea order en BD
9. Verificar que agente local lo imprime
10. Verificar que estado se actualiza
```

### 3.3 Test de Realtime

```bash
1. Abrir 2 navegadores (usuarios diferentes)
2. Usuario A: Sube PDF en Kiosk 1
3. Usuario B: Ve notificación instantánea de nueva orden
4. Verificar latencia < 100ms (check browser console)
5. Orden procesada en realtime
```

### 3.4 Test de Failover

```bash
1. Desconectar agente local (simular apagón)
2. Subir orden desde web
3. Verificar que status='pending' (esperando kiosk)
4. Reconectar agente
5. Verificar que agente retoma órdenes pendientes
6. Verificar que se imprime correctamente
```

---

## Parte 4: Despliegue en Producción - 15 minutos

### 4.1 Railway Final Deployment

```bash
# El código ya está en Railway con CD automático vía GitHub

# Si necesitas forzar despliegue:
# 1. Push a rama main
# 2. Railway detecta cambios automáticamente
# 3. Ejecuta build & start commands
# 4. Visualizar logs en Railway dashboard

# Primera vez: ejecutar migraciones
railway run php artisan migrate --force
```

### 4.2 Evolution API en Producción

**Opción 1: En Railway**
```bash
# Agregar servicio Docker a Railway
# Usar la imagen pre-build: ghcr.io/evolutionapi/evolution-api:latest

# Variables de entorno:
DATABASE_URL=postgres://[Railway Postgres]
API_SECRET=[Generar secret seguro]
WEBHOOK_URL=https://yourdomain.railway.app/api/whatsapp/webhook
```

**Opción 2: En servidor dedicado**
```bash
docker run -d \
  --name evolution \
  -p 3000:3000 \
  -e DATABASE_URL=$DATABASE_URL \
  -e API_SECRET=$API_SECRET \
  ghcr.io/evolutionapi/evolution-api:latest
```

### 4.3 Kiosk Agent en Sucursales

**Para cada ubicación:**

```bash
# 1. En la PC de la sucursal:
cd /opt/kiosk-agent
git pull origin main

# 2. Crear archivo .env específico de la sucursal
cat > .env << EOF
SUPABASE_URL=https://xxxxx.supabase.co
SUPABASE_SERVICE_KEY=eyJ...
KIOSK_ID=$(uuid -v4)  # Generar UUID único
PRINTER_NAME=$(lpstat -p | grep -oP 'printer \K[^:]+' | head -1)
EOF

# 3. Registrar kiosk en BD (IMPORTANTE: hacer una sola vez)
curl -X POST https://yourdomain.railway.app/api/kiosks \
  -H "Authorization: Bearer $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "id": "'$KIOSK_ID'",
    "name": "Sucursal Centro",
    "location": "Calle Principal 123",
    "printer_name": "'$PRINTER_NAME'"
  }'

# 4. Iniciar servicio
docker-compose up -d

# 5. Verificar logs
docker-compose logs -f
```

**Automatizar inicio (systemd para Linux):**
```ini
# /etc/systemd/system/kiosk-agent.service
[Unit]
Description=Kiosk Agent Service
After=docker.service
Requires=docker.service

[Service]
Type=simple
WorkingDirectory=/opt/kiosk-agent
ExecStart=docker-compose up
Restart=always
RestartSec=10s

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable kiosk-agent
sudo systemctl start kiosk-agent
```

---

## Parte 5: Monitoreo Inicial - 10 minutos

### 5.1 Health Checks

```bash
# API Central
curl -X GET https://yourdomain.railway.app/api/health

# Expected Response:
# {"status":"healthy","uptime":"2h 43m"}

# Supabase Connection
curl -X GET https://yourdomain.railway.app/api/supabase/status

# Evolution Webhook
curl -X GET http://evolution:3000/

# Agente Local
curl http://localhost:3001/health
```

### 5.2 Dashboard Filament

```
Acceso: https://yourdomain.railway.app/admin
Usuario: admin@example.com
Contraseña: [La que configuraste]

Verificar:
- Número de kioskos online
- Órdenes en cola
- Transacciones últimas horas
- Errores registrados
```

### 5.3 Supabase Dashboard

```
https://app.supabase.com → Tu proyecto

Verificar:
- Database → Tablas llenas de datos
- Storage → PDFs guardándose correctamente
- Realtime → Eventos propagándose
- Auth → Usuarios creándose
```

### 5.4 Railway Logs

```
Railway Dashboard → Tu app → Deployment → View Logs

Buscar:
- Errores (ERROR nivel)
- Warnings (WARN nivel)
- Request count
- Database connection pool
```

---

## Checklist Final (Antes de Ir a Producción)

```
SEGURIDAD
☐ SSL/TLS habilitado (Railway lo hace automático)
☐ API Keys en Railway (no en .env de repo)
☐ CORS configurado solo para tu dominio
☐ Rate limiting activo en API
☐ Validación de entrada en todos los endpoints
☐ Logs sin data sensible (emails/phones)

PERFORMANCE
☐ Índices creados en PostgreSQL
☐ Caching configurado (Redis)
☐ CDN habilitado para Static Assets (Cloudflare)
☐ Lazy loading en frontend

CONFIABILIDAD
☐ Backups automáticos (Supabase cada 24h)
☐ Error tracking habilitado (Sentry o similar)
☐ Alertas configuradas (CPU, DB, errors)
☐ Plan de disaster recovery documentado

OPERACIONAL
☐ Documentación completa (ARCHITECTURE.md)
☐ Runbooks para incident response
☐ Acceso de emergencia documentado
☐ Contacto de soporte técnico

LEGAL
☐ GDPR compliance (data privacy)
☐ Terms of Service
☐ Privacy Policy
☐ Data retention policy
```

---

## Troubleshooting Rápido

### Error: Database Connection Failed
```bash
# Verificar variables de entorno
railway vars

# Verificar conectividad
psql $DATABASE_URL -c "SELECT version();"

# Si Railway Postgres, asegurar firewall abierto
```

### Error: DeepSeek API Timeout
```bash
# Verificar API Key
grep DEEPSEEK_API_KEY .env

# Probar conexión
curl https://api.deepseek.com/v1/models \
  -H "Authorization: Bearer $DEEPSEEK_API_KEY"
```

### Error: WhatsApp Webhooks No Recibidos
```bash
# Verificar Evolution logs
docker-compose logs evolution

# Verificar webhook URL en Evolution settings
# Debe ser: https://yourdomain.railway.app/api/whatsapp/webhook

# Probar manualmente
curl -X POST https://yourdomain.railway.app/api/whatsapp/webhook \
  -H "Content-Type: application/json" \
  -d '{"test":"data"}'
```

### Error: Agente No Escucha Órdenes
```bash
# Verificar conexión Supabase Realtime
docker-compose logs kiosk-agent | grep "realtime"

# Verificar RLS policies
supabase db pull

# Verificar service key tiene permisos
```

---

## Soporte Técnico

**Problemas comunes:** Ver `/troubleshooting/` en docs
**Contacto:** tech@yourdomain.com
**Slack:** #soporte-tecnico
**Status Page:** status.yourdomain.com

---

## Conclusión

**Con esta guía, cualquier technical person puede desplegar el sistema completo en < 2 horas.**

Esto demuestra:
✅ Operacionalidad
✅ Escalabilidad
✅ Madurez del código
✅ Readiness para inversión

---

*Última actualización: Mayo 2026*
*Versión: 1.0 | Production Ready*
