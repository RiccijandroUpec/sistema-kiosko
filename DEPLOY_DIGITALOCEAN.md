# Despliegue en DigitalOcean VPS (Servidor General + Evolution API)

Esta guía despliega el **servidor general (Laravel central)** y **Evolution API**
en un VPS DigitalOcean (Ubuntu). La **base de datos y el almacenamiento viven en
Supabase** (remoto). El **kiosk-agent** NO se instala aquí: corre en la PC física
de cada sucursal conectada a su impresora.

> Ejecuta todos los comandos por SSH desde tu máquina Windows/PowerShell:
> `ssh root@TU_IP_DEL_DROPLET`
> Reemplaza `TU_IP_DEL_DROPLET` por tu IP (no se incluyen credenciales aquí).

---

## 0. Estado antes de empezar (requisitos)

- [ ] Droplet DigitalOcean Ubuntu 22.04/24.04 con acceso root por SSH.
- [ ] Un **dominio** apuntando (registro A) a la IP del droplet, p. ej.
      `kiosko.midominio.com`. Necesario para HTTPS y para que WhatsApp vía
      Evolution funcione en producción.
- [ ] Proyecto **Supabase** con: credenciales (`DB_*`, `SUPABASE_URL`,
      `SUPABASE_ANON_KEY`, `SUPABASE_SERVICE_KEY`), bucket de storage `pdfs`
      marcado como público.
- [ ] Repo `sistema-kiosko` accesible para clonar y para `git pull`.

---

## 1. Instalar Docker y Docker Compose en el droplet

Conectate por SSH y ejecuta:

```bash
# Actualizar el sistema
sudo apt update && sudo apt upgrade -y

# Instalar dependencias
sudo apt install -y ca-certificates curl gnupg lsb-release

# Agregar repo oficial de Docker
sudo install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | \
  sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
sudo chmod a+r /etc/apt/keyrings/docker.gpg

echo \
  "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] \
  https://download.docker.com/linux/ubuntu $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | \
  sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

# Instalar Docker Engine + Compose plugin
sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin

# Verificar
docker --version
docker compose version
```

---

## 2. Clonar el proyecto y preparar `.env`

```bash
cd /opt
sudo git clone https://github.com/RiccijandroUpec/sistema-kiosko.git
cd sistema-kiosko
sudo mkdir -p storage/app/public storage/framework/{cache,sessions,views} \
             storage/logs bootstrap/cache

# Copiar la plantilla y generar la clave (repasa, edita con tus credenciales)
sudo cp .env.example .env
php 2>/dev/null; sudo docker run --rm -v "$PWD":/app -w /app php:8.2-cli \
  php -r "copy('.env.example', '.env');"   # alternativa: crea el .env a mano
```

> **Recomendación:** edita el `.env` directamente. Las variables imprescindibles
> para arrancar se listan aqui. Rellena todas con los valores reales de tu cuenta:

```env
APP_NAME="Sistema Kiosko Impresiones"
APP_ENV=production
APP_KEY=base64:XXXXXX        # genera con php artisan key:generate
APP_URL=https://kiosko.midominio.com

APP_DEBUG=false

# ----- Supabase (PostgreSQL). Usar pooler MODO SESION (puerto 5432) -----
DB_CONNECTION=pgsql
DB_HOST=aws-0-us-east-1.pooler.supabase.com
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres.TUREF
DB_PASSWORD=TU_PASSWORD_SUPABASE

# ----- Supabase Storage / API -----
SUPABASE_URL=https://TUREF.supabase.co
SUPABASE_ANON_KEY=TU_ANON_KEY
SUPABASE_SERVICE_KEY=TU_SERVICE_ROLE_KEY
SUPABASE_STORAGE_BUCKET=pdfs

# ----- IA -----
DEEPSEEK_API_KEY=sk_...
GEMINI_API_KEY=AIza...

# ----- Evolution API (WhatsApp) -----
# En la red docker ambos servicios se ven por su nombre. NO uses 127.0.0.1:
EVOLUTION_API_BASE_URL=http://evolution_api:8080
EVOLUTION_API_KEY=cambia_esta_evolution_key_secreta
EVOLUTION_INSTANCE=kiosko
EVOLUTION_WEBHOOK_SECRET=gEnera_un_valor_random_largo

# ----- IMAP (verificar transferencias por correo) -----
IMAP_HOST=
IMAP_USERNAME=
IMAP_PASSWORD=

# ----- Otros -----
ADMIN_PHONE=593999999999
```

> ⚠️ **Ojo:** `EVOLUTION_API_BASE_URL` debe ser `http://evolution_api:8080`
> (nombre del servicio en la red docker), no `127.0.0.1`, porque Laravel corre
> EN UN CONTENEDOR y webhooks/llamadas van dentro de la red docker.
> `EVOLUTION_WEBHOOK_SECRET` y `AUTHENTICATION_API_KEY` del compose deben COINCIDIR.

---

## 3. Configurar docker-compose.vps.yml

Ya tenes un archivo específico para VPS en el repo: `docker-compose.vps.yml`.
Abrilo y **cambiá dos valores obligatorios**:

1. `AUTHENTICATION_API_KEY` -> tu clave secreta de Evolution (misma que
   `EVOLUTION_API_KEY` del `.env`).
2. `DATABASE_CONNECTION_URI` -> la conexión PostgreSQL de Supabase **apuntando
   al schema `evolution`** (pooler sesión, puerto 5432):

```
postgresql://postgres.TUREF:TU_PASS@aws-0-us-east-1.pooler.supabase.com:5432/postgres?schema=evolution&sslmode=require
```

---

## 4. Construir y levantar

```bash
cd /opt/sistema-kiosko
sudo docker compose -f docker-compose.vps.yml build app
sudo docker compose -f docker-compose.vps.yml up -d
```

> La primera vez que corre, `entrypoint.sh`/`supervisord.conf` ejecutan
> `php artisan migrate --force` automáticamente contra Supabase.

### 4.1 Verificar logs y salud

```bash
sudo docker compose -f docker-compose.vps.yml logs -f app
curl http://127.0.0.1/api/health        # debe responder {"status":"ok"}
curl -I http://127.0.0.1                # debe responder 200
```

---

## 5. Asegurar con HTTPS (nginx + Certbot en el host)

El contenedor expone `80/443` del droplet. Para HTTPS real se recomienda un
proxy nginx en el HOST apuntando al puerto 80 del contenedor y emitir
certificados con Let's Encrypt. Ejemplo mínimo:

```bash
sudo apt install -y nginx certbot python3-certbot-nginx
```

Crea `/etc/nginx/sites-available/kiosko`:

```nginx
server {
    listen 80;
    server_name kiosko.midominio.com;

    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        client_max_body_size 25m;
    }
}
```

```bash
sudo ln -sf /etc/nginx/sites-available/kiosko /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx

# Emitir certificado
sudo certbot --nginx -d kiosko.midominio.com
```

> Con TLS activo, actualizá `APP_URL=https://kiosko.midominio.com` en `.env` y
> el webhook de Evolution debe apuntar a `https://kiosko.midominio.com/webhook-bot`.

---

## 6. Registrar en Evolution el webhook con el secret

El webhook de Laravel es `/webhook-bot`. Para evitar que cualquiera lo golpee,
Evolution debe llamarlo con `?secret=...`:

```
https://kiosko.midominio.com/webhook-bot?secret=TU_EVOLUTION_WEBHOOK_SECRET
```

Revisá que `EVOLUTION_WEBHOOK_SECRET` coincida entre `.env` de Laravel y la
configuración del webhook de Evolution.

---

## 7. Escanear el QR para vincular WhatsApp

```bash
# Instancia debe existir: Evo crea la instancia al primer envio; en manager web
# (si descomentaste el puerto) o por API:
curl -X POST http://127.0.0.1:8085/instance/create \
  -H "apikey: cambia_esta_evolution_key_secreta" \
  -H "Content-Type: application/json" \
  -d '{"instanceName":"kiosko","integration":"WHATSAPP-BAILEYS"}'

# Estado de la instancia / QR (escribe manual en manager web o via API)
curl http://127.0.0.1:8085/instance/connectionState/kiosko \
  -H "apikey: cambia_esta_evolution_key_secreta"
```

> El QR se escanea con el WhatsApp del negocio. Si `integration` difiere segun la
> version de Evolution, ajusta segun la doc de Evolution API.

---

## 8. Panel de administración

Con acceso vía `https://kiosko.midominio.com/admin`.

Para crear el primer admin (si no existe) en el schema de la app de Supabase,
ejecutá dentro del contenedor app:

```bash
sudo docker exec -it kiosko_app php artisan tinker --execute="
  App\Models\User::updateOrCreate(
    ['email' => 'admin@midominio.com'],
    ['name' => 'Admin', 'password' => bcrypt('TU_PASS_SEGURA'), 'is_admin' => true]
  );
"
```

---

## 9. Puertos del droplet

En el panel de DigitalOcean, **Firewall (cloud firewall)** debes abrir:

| Puerto | Uso |
|--------|-----|
| 22     | SSH |
| 80     | HTTP (Laravel) |
| 443    | HTTPS (Laravel) |
| 8085   | Evolution manager web (SOLO si la descomentaste; preferible restringir) |

**NO** expongas el puerto 5432 (Postgres va en Supabase). Mantené los puertos
de Evolution cerrados a internet cuando sea posible y accede por red interna.

---

## 10. Nota: conversión de Word/PPT a PDF

La función de conversión de archivos de Office (.docx/.ppt/… ) a PDF requiere
**LibreOffice dentro del contenedor**. El `Dockerfile` raiz actual **no lo
incluye**, así que si un cliente sube un `.docx` en producción, la conversión
fallará. Para habilitarla en el VPS, crea un `Dockerfile.prod` (o build target)
que instale LibreOffice:

```dockerfile
# Dockerfile.prod
FROM sistema-kiosko_app:latest   # o simplemente parte del Dockerfile raiz y añade:
# COPY  --- pseudo-ejemplo; en tu Dockerfile.prod hereda el build base e instala lo extra
RUN apt-get update && DEBIAN_FRONTEND=noninteractive apt-get install -y libreoffice-writer libreoffice-impress && rm -rf /var/lib/apt/lists/*
```

Luego usá `build: { context: ., dockerfile: Dockerfile.prod }` en el compose
cuando necesites convertir Office a PDF en el servidor.
