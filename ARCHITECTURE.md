# Arquitectura del Sistema de Kiosco de Impresiones

## Resumen Ejecutivo

Sistema distribuido de impresión bajo demanda con inteligencia artificial, diseñado para operar kioskos de autoservicio en múltiples ubicaciones. La arquitectura implementa una solución **serverless-first** con comunicación en tiempo real (WebSockets) y procesamiento inteligente de peticiones vía IA.

**Ventajas Competitivas:**
- Costo operativo ultra-bajo (IA y hosting escalables)
- Tiempo de despliegue < 15 minutos por sucursal
- Escalabilidad horizontal sin modificación de código
- Experiencia de usuario conversacional (WhatsApp)

---

## Diagrama de Arquitectura Global

```
┌─────────────────────────────────────────────────────────────────┐
│                      USUARIO / CLIENTE                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Web Browser                    WhatsApp                        │
│  (Dashboard de Upload)          (Agente IA Conversacional)     │
│         │                                  │                   │
│         └──────────────────┬───────────────┘                   │
│                            │                                   │
├────────────────────────────▼────────────────────────────────────┤
│              RAILWAY (PaaS - Servidor Central)                 │
│                                                                 │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  LARAVEL 11 (PHP 8.3+)                                  │  │
│  │  ├─ HTTP Router & Controllers                           │  │
│  │  ├─ API REST Endpoints                                  │  │
│  │  ├─ Validación & Lógica de Negocio                      │  │
│  │  └─ Middleware de Autenticación                         │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                 │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  FILAMENT PHP (Panel Administrativo)                    │  │
│  │  ├─ Dashboard con KPIs en Tiempo Real                   │  │
│  │  ├─ Gestión de Usuarios & Permisos                      │  │
│  │  ├─ Histórico de Transacciones                          │  │
│  │  └─ Reportes de Ventas & Analytics                      │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                 │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  DEEPSEEK API Integration                               │  │
│  │  ├─ NLP para interpretación de solicitudes              │  │
│  │  ├─ Generación de respuestas naturales                  │  │
│  │  └─ Context-aware conversation memory                   │  │
│  └──────────────────────────────────────────────────────────┘  │
└────────────────┬────────────────────────────┬──────────────────┘
                 │                            │
        ┌────────▼──────────┐      ┌──────────▼─────────┐
        │   SUPABASE        │      │  EVOLUTION API     │
        │   (Backend-as-    │      │  (WhatsApp Node    │
        │   a-Service)      │      │  Gateway)          │
        │                   │      │                    │
        │ ┌─────────────┐   │      │  PostgreSQL        │
        │ │ PostgreSQL  │   │      │  (Sesiones de      │
        │ │ (Datos)     │   │      │   WhatsApp)        │
        │ │             │   │      │                    │
        │ │ ├─ Kiosks   │   │      │  Node.js Runtime   │
        │ │ ├─ PrintJobs│   │      │  + Webhooks        │
        │ │ ├─ Users    │   │      │                    │
        │ │ ├─Transact. │   │      └────────────────────┘
        │ │ └─ Sessions │   │
        │ └─────────────┘   │
        │                   │
        │ ┌─────────────┐   │
        │ │  Realtime   │   │
        │ │ (WebSockets)│───┼─────────────────┐
        │ │             │   │                 │
        │ │ Pub/Sub     │   │                 │
        │ └─────────────┘   │                 │
        │                   │                 │
        │ ┌─────────────┐   │                 │
        │ │  Storage    │   │                 │
        │ │  (Buckets)  │   │                 │
        │ │             │   │                 │
        │ │ PDF temp.   │   │                 │
        │ └─────────────┘   │                 │
        └───────────────────┘                 │
                 ▲                            │
                 │                            │
                 └────────────────────────────┘
                        (Webhooks)
                
        ┌─────────────────────────────────────┐
        │   KIOSK AGENT (LOCAL - Node.js)     │
        │                                     │
        │  ┌──────────────────────────────┐   │
        │  │ Supabase Realtime Client     │   │
        │  │ (Escucha eventos)            │   │
        │  └──────────────────────────────┘   │
        │                                     │
        │  ┌──────────────────────────────┐   │
        │  │ Print Job Handler            │   │
        │  │ ├─ Descarga de PDFs          │   │
        │  │ ├─ Gestión de Colas          │   │
        │  │ └─ Comando de Impresión      │   │
        │  └──────────────────────────────┘   │
        │                                     │
        │  ┌──────────────────────────────┐   │
        │  │ CUPS Integration (Linux)     │   │
        │  │ └─ lp -d printer_name file   │   │
        │  └──────────────────────────────┘   │
        │                                     │
        │  ┌──────────────────────────────┐   │
        │  │ System Monitor               │   │
        │  │ ├─ Printer Status            │   │
        │  │ ├─ Network Health            │   │
        │  │ └─ Error Logging             │   │
        │  └──────────────────────────────┘   │
        │                                     │
        │  Docker Container (Production)      │
        └─────────────────────────────────────┘
```

---

## 1. Capa de Presentación & Acceso

### 1.1 Frontend Web
- **Tecnología:** Vue.js 3 + Vite
- **Ubicación:** `/resources/js/` y `/resources/views/`
- **Funcionalidades:**
  - Dashboard de usuario para subida de PDFs
  - Sistema de autenticación (Laravel Breeze/Sanctum)
  - Visualización del estado de órdenes en tiempo real
  - Integración con Supabase Realtime para notificaciones instantáneas

### 1.2 WhatsApp Conversacional
- **Tecnología:** Evolution API + DeepSeek API
- **Flujo:**
  1. Usuario envía mensaje/PDF a número WhatsApp
  2. Evolution recibe el webhook
  3. DeepSeek procesa el lenguaje natural
  4. Laravel ejecuta la lógica de negocio
  5. Sistema responde por WhatsApp con estado de la orden

---

## 2. Servidor Central (Railway PaaS)

### 2.1 Laravel 11 (PHP 8.3+)

**Responsabilidades:**
- Orquestación de toda la lógica de negocio
- Validación de PDFs y archivos
- Autenticación y autorización de usuarios
- Integración con APIs externas
- Gestión de transacciones monetarias

**Estructura del Código:**
```
app/
├── Http/
│   ├── Controllers/
│   │   ├── PrintJobController.php
│   │   ├── PaymentController.php
│   │   └── WhatsAppController.php
│   ├── Middleware/
│   │   ├── VerifyApiToken.php
│   │   └── LogUserActivity.php
│   └── Requests/
│       └── StorePrintJobRequest.php
├── Models/
│   ├── User.php
│   ├── PrintJob.php
│   ├── Payment.php
│   ├── Transaction.php
│   ├── Kiosk.php
│   └── PdfFile.php
└── Services/
    ├── DeepseekService.php
    ├── PrintJobService.php
    └── PaymentGatewayService.php
```

**Configuraciones Críticas:** (`/config/`)
- `deepseek.php` - API credentials y modelos IA
- `evolution.php` - Integración WhatsApp
- `whatsapp-business.php` - Webhook & autenticación
- `printing.php` - Configuración de kioskos

### 2.2 Filament PHP (Panel Administrativo)

**Dashboard Ejecutivo:**
- KPIs en tiempo real:
  - Órdenes procesadas (hoy/mes)
  - Ingresos acumulados
  - Tasa de éxito de impresiones
  - Número de usuarios activos
  
- Tablas interactivas:
  - Gestión de kioskos (ubicaciones, estado, historial)
  - Revisión de transacciones
  - Reportes de errores

**Stack Tecnológico:**
- Componentes nativos de Livewire
- CRUD automático
- Gráficas con Chart.js/ApexCharts
- Autenticación integrada

### 2.3 DeepSeek API Integration

**Propósito:** Procesamiento de lenguaje natural para WhatsApp

**Ejemplo de Flujo:**
```
Usuario WhatsApp: "Quiero imprimir un PDF en el kiosko de Zona 1"
    ↓
Evolution Webhook → Laravel Controller
    ↓
DeepseekService::processIntent()
    ↓
Interpretación IA: 
  {
    "intent": "print_job",
    "location": "Zona 1",
    "confidence": 0.95
  }
    ↓
PrintJobService::create()
    ↓
Respuesta WhatsApp: "Listo, tu documento está en la cola. 
                     Dirigirse al kiosko de Zona 1"
```

**Ventajas de DeepSeek:**
- Costo 90% más bajo que GPT-4
- Latencia < 1 segundo
- Soporte para múltiples idiomas

---

## 3. Capa de Datos & Almacenamiento (Supabase)

### 3.1 PostgreSQL (Base de Datos Gestionada)

**Esquema Principal:**

```sql
-- Usuarios del sistema
Table: users
├── id (UUID)
├── email (UNIQUE)
├── phone_whatsapp
├── is_active (BOOLEAN)
└── created_at

-- Kioskos de impresión
Table: kiosks
├── id (UUID)
├── name
├── location (VARCHAR)
├── printer_name (CUPS)
├── status (online/offline)
├── last_heartbeat
└── created_at

-- Órdenes de impresión
Table: print_jobs
├── id (UUID)
├── user_id (FK)
├── kiosk_id (FK)
├── pdf_url (Supabase Storage)
├── status (pending/processing/completed/error)
├── pages_count
├── created_at
└── completed_at

-- Transacciones de pago
Table: transactions
├── id (UUID)
├── user_id (FK)
├── amount (DECIMAL)
├── currency (USD/MXN)
├── method (credit_card/wallet)
├── status (pending/completed/failed)
└── created_at

-- Sesiones de WhatsApp
Table: whatsapp_sessions
├── id (UUID)
├── phone_number
├── status (active/inactive)
├── conversation_context (JSONB)
├── last_message_at
└── created_at
```

**Índices Estratégicos:**
```sql
CREATE INDEX idx_print_jobs_user_id ON print_jobs(user_id);
CREATE INDEX idx_print_jobs_kiosk_id ON print_jobs(kiosk_id);
CREATE INDEX idx_print_jobs_status ON print_jobs(status);
CREATE INDEX idx_transactions_user_id ON transactions(user_id);
CREATE INDEX idx_whatsapp_phone ON whatsapp_sessions(phone_number);
```

### 3.2 Supabase Realtime (WebSockets)

**Canales Pub/Sub:**

```javascript
// Suscripción en el Kiosk Agent (Local)
const channel = supabase
  .channel('print_jobs:kiosk_id=xyz')
  .on('postgres_changes', 
    { 
      event: 'INSERT',
      schema: 'public',
      table: 'print_jobs',
      filter: `kiosk_id=eq.xyz`
    },
    (payload) => {
      console.log('Nueva orden:', payload.new);
      // Descargar PDF e imprimir
      downloadAndPrint(payload.new);
    }
  )
  .subscribe();
```

**Ventajas:**
- Notificación instantánea (< 50ms)
- Sin polling a base de datos
- Uso eficiente de ancho de banda
- Escalable a miles de clientes

### 3.3 Supabase Storage (Buckets)

**Estructura de Almacenamiento:**

```
buckets/
├── pdfs/ (Público con tokens seguros)
│   ├── {year}/{month}/{uuid}_original.pdf
│   └── {year}/{month}/{uuid}_temporary.pdf
├── receipts/ (Privado)
│   └── {user_id}/{transaction_id}.pdf
└── logs/ (Privado)
    └── {kiosk_id}/{date}/
```

**Política de Ciclo de Vida:**
- PDFs temporales: Expiración 24 horas
- Recibos: Retención 7 años
- Logs: Retención 30 días

---

## 4. Inteligencia Artificial & Procesamiento

### 4.1 DeepSeek API
- **Modelo:** deepseek-chat
- **Casos de Uso:**
  - Clasificación de intenciones de usuario
  - Generación de respuestas naturales
  - Extracción de entidades (ubicación, cantidad)
  - Resolución de consultas FAQ

**Configuración de Prompts:**
```
System Prompt:
"Eres un asistente amable para un sistema de impresión por demanda.
Tu rol es:
1. Interpretar intenciones del usuario
2. Solicitar información faltante
3. Proporcionar instrucciones claras
4. Mantener contexto de conversación"
```

**Estimado de Costos:**
- $0.002 USD por 1000 tokens (entrada)
- $0.006 USD por 1000 tokens (salida)
- Promedio por interacción: ~200 tokens = $0.0014 USD

---

## 5. Capa de Comunicación (Evolution API)

### 5.1 Arquitectura

**Componentes:**
- **Node.js Runtime:** Evolution API en contenedor Docker
- **PostgreSQL:** Base de datos de sesiones WhatsApp
- **Webhook Parser:** Recibe eventos de WhatsApp Business
- **Message Queue:** Almacena mensajes pendientes

**Flujo de Mensajería:**

```
WhatsApp Business API
       ↓
Evolution Webhook Listener (Node.js)
       ↓
Validation & Parsing
       ↓
Laravel Controller via HTTP POST
       ↓
Procesamiento de Negocio (DeepSeek + BD)
       ↓
Response Builder
       ↓
Evolution API Client
       ↓
WhatsApp Business API
       ↓
Usuario
```

**Ventajas de Evolution:**
- No requiere costo de WhatsApp Business (usa número normal + QR)
- Código abierto (auditable)
- Escalable con Docker
- Bajo consumo de recursos

---

## 6. Agente Local (Kiosk Agent)

### 6.1 Tecnología

**Stack:**
- Runtime: Node.js 18+
- Client: @supabase/supabase-js
- Print Control: node-cups (wrapper de CUPS)
- Logging: winston

**Estructura:**
```
kiosk-agent/
├── src/
│   ├── index.js (Entry point)
│   ├── supabase.js (Client init)
│   ├── printer.js (CUPS integration)
│   ├── monitor.js (Health checks)
│   └── logger.js (Winston config)
├── package.json
├── Dockerfile
└── .env.example
```

### 6.2 Funcionamiento

**1. Inicialización:**
```javascript
const supabase = createClient(SUPABASE_URL, KIOSK_SERVICE_KEY);

const channel = supabase
  .channel(`print_jobs:kiosk_id=${KIOSK_ID}`)
  .on('postgres_changes', { event: 'INSERT', table: 'print_jobs' }, 
    handleNewPrintJob
  )
  .subscribe();
```

**2. Manejo de Orden de Impresión:**
```javascript
async function handleNewPrintJob(payload) {
  const { pdf_url, id: jobId } = payload.new;
  
  try {
    // Descargar PDF desde Supabase Storage
    const buffer = await downloadPDF(pdf_url);
    
    // Imprimir a través de CUPS
    await printViaCUPS(buffer, PRINTER_NAME);
    
    // Actualizar estado en BD
    await supabase
      .from('print_jobs')
      .update({ status: 'completed' })
      .eq('id', jobId);
      
  } catch (error) {
    // Registrar error y actualizar estado
    logger.error('Print failed', { jobId, error });
    await supabase
      .from('print_jobs')
      .update({ status: 'error', error_message: error.message })
      .eq('id', jobId);
  }
}
```

**3. Monitoreo Continuo:**
```javascript
setInterval(async () => {
  const status = await getCUPSStatus();
  const networkHealth = await checkNetworkConnectivity();
  
  await supabase
    .from('kiosks')
    .update({
      status: status.enabled ? 'online' : 'offline',
      last_heartbeat: new Date(),
      metrics: { networkHealth, queueLength: status.jobs }
    })
    .eq('id', KIOSK_ID);
}, 30000); // Cada 30 segundos
```

### 6.3 Control de Impresión (CUPS)

**Comandos Utilizados:**
```bash
# Listar impresoras disponibles
lpstat -p -d

# Imprimir directamente
lp -d MyPrinter -h localhost /path/to/file.pdf

# Verificar estado de cola
lpstat -o

# Cancelar trabajo
cancel job_id
```

---

## 7. Infraestructura & Despliegue

### 7.1 Contenedores Docker

**docker-compose.yml (Producción en Railway):**
```yaml
version: '3.9'

services:
  app:
    build: .
    environment:
      - APP_ENV=production
      - DB_CONNECTION=pgsql
      - DB_HOST=${DB_HOST}
      - DEEPSEEK_API_KEY=${DEEPSEEK_API_KEY}
      - SUPABASE_URL=${SUPABASE_URL}
      - SUPABASE_KEY=${SUPABASE_KEY}
    ports:
      - "8080:8000"
    volumes:
      - ./storage/logs:/app/storage/logs

  evolution:
    image: evolution-api:latest
    environment:
      - DATABASE_URL=${EVOLUTION_DB_URL}
      - API_SECRET=${EVOLUTION_API_SECRET}
    ports:
      - "3000:3000"
    depends_on:
      - evolution-db

  evolution-db:
    image: postgres:15
    environment:
      - POSTGRES_PASSWORD=${EVOLUTION_DB_PASSWORD}
    volumes:
      - evolution_db_data:/var/lib/postgresql/data

volumes:
  evolution_db_data:
```

**Dockerfile (Kiosk Agent):**
```dockerfile
FROM node:18-alpine

WORKDIR /app

# Instalar CUPS (solo en Linux/Ubuntu)
RUN apk add --no-cache cups cups-client

COPY package*.json ./
RUN npm ci --only=production

COPY src ./src

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
  CMD node src/health.js

CMD ["node", "src/index.js"]
```

**docker-compose.yml (Kiosk Local):**
```yaml
version: '3.9'

services:
  kiosk-agent:
    build: .
    restart: always
    environment:
      - SUPABASE_URL=${SUPABASE_URL}
      - SUPABASE_KEY=${KIOSK_SERVICE_KEY}
      - KIOSK_ID=${KIOSK_ID}
      - PRINTER_NAME=${PRINTER_NAME}
    volumes:
      - /var/run/cups:/var/run/cups:ro
      - ./logs:/app/logs
    networks:
      - local

networks:
  local:
    driver: bridge
```

### 7.2 Railway Deployment

**Variables de Entorno:**
```env
APP_NAME=Sistema Kiosko Impresiones
APP_ENV=production
APP_KEY=base64:xxx
APP_URL=https://yourdomain.com

DB_CONNECTION=pgsql
DB_HOST=prod.railway.app
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=xxx

DEEPSEEK_API_KEY=sk-xxx
DEEPSEEK_MODEL=deepseek-chat

SUPABASE_URL=https://xxx.supabase.co
SUPABASE_KEY=eyJxxx

EVOLUTION_API_URL=http://evolution:3000
EVOLUTION_API_KEY=xxx
```

**Railway Deployment Steps:**
1. Conectar repositorio GitHub
2. Agregar variables de entorno
3. Configurar build command: `composer install && php artisan migrate`
4. Configurar start command: `php artisan serve --host=0.0.0.0`
5. Railway despliega automáticamente en cada push

---

## 8. Análisis de Costos Operativos (Mensual)

| Componente | Costo | Justificación |
|-----------|-------|--------------|
| **Railway** (App + DB) | $50-200 | Escalable según tráfico; primera app free-tier |
| **Supabase** | $25-100 | PostgreSQL + Storage + Auth (generous free tier) |
| **Evolution API** | $0 | Auto-hosted en Railway |
| **DeepSeek API** | $10-50 | Basado en uso (promedio 100k-500k tokens/mes) |
| **Domain** | $10 | DNS y certificados SSL |
| **Kiosk Agent** (por sucursal) | $0 | Costo de computadora + internet local |
| **TOTAL** | **$95-360** | Escalable linealmente |

**Comparativo:**
- Solución Enterprise (SAP/Oracle): $5,000-50,000/mes
- Solución SaaS competidor: $500-2,000/mes
- **Esta solución:** $100-300/mes ✓ **95% más barato**

---

## 9. Seguridad

### 9.1 Autenticación & Autorización

```
┌─────────────────────────────────┐
│ Usuario                         │
│ (Email + Password)              │
└──────────────┬──────────────────┘
               │
               ▼
┌─────────────────────────────────┐
│ Laravel Sanctum                 │
│ (Session + Token Management)    │
└──────────────┬──────────────────┘
               │
               ▼
┌─────────────────────────────────┐
│ Supabase Auth                   │
│ (JWT + MFA Support)             │
└──────────────┬──────────────────┘
               │
               ▼
┌─────────────────────────────────┐
│ Protegido: API + Base de Datos  │
└─────────────────────────────────┘
```

### 9.2 Políticas de Row-Level Security (RLS)

```sql
-- Solo un usuario puede ver sus propias órdenes
CREATE POLICY "Users can view own orders"
  ON print_jobs FOR SELECT
  USING (auth.uid() = user_id);

-- Los kioskos solo pueden actualizar su propio estado
CREATE POLICY "Kiosks can update own status"
  ON kiosks FOR UPDATE
  USING (auth.uid() = created_by_id);
```

### 9.3 Encriptación

- **En Tránsito:** TLS 1.3 (Railway + Supabase)
- **En Reposo:** AES-256 (Supabase Storage)
- **Variables de Entorno:** Encrypted by Railway
- **Tokens API:** Rotativos cada 30 días

### 9.4 Validación de Entrada

```php
// Laravel Request Validation
class StorePrintJobRequest extends FormRequest {
    public function rules() {
        return [
            'pdf_file' => 'required|file|mimes:pdf|max:50000',
            'kiosk_id' => 'required|uuid|exists:kiosks,id',
            'copies' => 'integer|between:1,100'
        ];
    }
}
```

---

## 10. Escalabilidad & Performance

### 10.1 Horizontal Scaling

```
┌─────────────────────────────────┐
│ Supabase Load Balancer          │
├─────────────────────────────────┤
│ PostgreSQL Replica Pool         │
│ (Read Replicas automáticas)     │
└─────────────────────────────────┘

┌─────────────────────────────────┐
│ Railway Auto-scaling            │
├─────────────────────────────────┤
│ App Instance 1 (CPU: 100%)      │
│ App Instance 2 (CPU: 100%)      │
│ App Instance 3 (CPU: 100%)      │
│ → Trigger: CPU > 80%            │
│ → Max Instances: 10             │
└─────────────────────────────────┘

┌─────────────────────────────────┐
│ Kiosk Agents (Independientes)   │
│ → Crecimiento lineal            │
│ → Costo: $0 en infraestructura  │
└─────────────────────────────────┘
```

### 10.2 Benchmarks Esperados

| Métrica | Valor |
|---------|-------|
| Latencia API (p95) | < 100ms |
| Throughput | 1,000 req/seg |
| Realtime Notification | < 50ms |
| Tasa de Disponibilidad | 99.9% |
| MTTR (Mean Time to Recovery) | < 5 min |

### 10.3 Caching Strategy

```
┌──────────────┐
│ Browser      │ ← Service Worker (Cache API)
└──────────────┘

┌──────────────┐
│ Laravel      │ ← Redis/Memcached
│ (Cache)      │   - Kiosks list (TTL: 1h)
└──────────────┘   - User sessions (TTL: 24h)
                   - Rate limits (TTL: 1min)

┌──────────────┐
│ Supabase     │ ← Query Results Cache
│ (Queries)    │   (Automático via CDN)
└──────────────┘
```

---

## 11. Monitoreo & Observabilidad

### 11.1 Logging Distribuido

```
Aplicación → Winston Logger → Supabase Logs Table
                          ↓
                    Grafana Dashboard
                    (KPIs en tiempo real)
```

**Ejemplo:**
```javascript
logger.info('Print job completed', {
  jobId: '123e4567',
  kiosk: 'Zone-1',
  duration: '2.3s',
  timestamp: new Date()
});
```

### 11.2 Alertas Críticas

```
1. Kiosk Offline > 5 min        → SMS Alert
2. Error Rate > 5%              → Email Alert
3. API Latency > 500ms          → Slack Notification
4. Database Connection Pool > 90% → Auto Scale
5. Disk Space < 20%             → Alert + Cleanup
```

### 11.3 Métricas Clave (KPIs)

- **Operacionales:**
  - Órdenes procesadas / hora
  - Tasa de éxito de impresión
  - Número de kioskos activos

- **Financieras:**
  - Revenue por kiosko
  - Costo promedio por orden
  - Margen operativo

- **Técnicas:**
  - Latencia API (p50, p95, p99)
  - Disponibilidad del sistema
  - Número de órdenes en cola

---

## 12. Roadmap de Mejoras Futuras

### Q3 2026
- [ ] Análisis de datos con BigQuery
- [ ] Machine Learning para predicción de demanda
- [ ] Soporte para múltiples métodos de pago (Apple Pay, Google Pay)

### Q4 2026
- [ ] Integración con otros canales (Telegram, SMS)
- [ ] Impresión de códigos QR dinámicos
- [ ] SDK para terceros

### 2027
- [ ] Blockchain para auditoría (opcional)
- [ ] Integración con servicios de entrega
- [ ] Marketplace de templates de diseño

---

## 13. Referencias Técnicas

### Documentación
- Laravel: https://laravel.com/docs/11
- Supabase: https://supabase.com/docs
- Evolution API: https://evolution-api.com/docs
- DeepSeek: https://platform.deepseek.com/docs

### Repositorios
- Kiosk Agent: `/kiosk-agent/`
- Backend: Raíz del proyecto
- Configuración Docker: `docker-compose.yml`

### Contacto & Soporte
- Issues: GitHub Issues
- Docs: `/QUICK_START.md`, `/WEB_ONLY_GUIDE.md`
- Arquitecto: Tu nombre / equipo

---

## Conclusión

Esta arquitectura representa una solución **enterprise-grade** con:
✅ Costo operativo ultra-bajo
✅ Escalabilidad de 1 a 1000+ kioskos
✅ Tiempo de despliegue < 15 minutos
✅ Experiencia de usuario conversacional
✅ Seguridad industrial
✅ Observabilidad completa

**Está lista para inversión, replicación y crecimiento exponencial.**

---

*Documento generado el 22 de mayo de 2026*
*Versión: 1.0 | Estado: Production Ready*
