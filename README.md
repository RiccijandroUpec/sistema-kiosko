# Sistema de Kiosko de Impresiones

Un sistema web completo para gestionar la impresión de archivos PDF en un kiosko. Permite a los usuarios subir archivos PDF, calcular el costo de impresión y gestionar trabajos de impresión con un panel de administración para monitorear todas las actividades.

## Arquitectura Separada

Este repositorio seguirá siendo la base del **servidor central** por ahora. La separación física del proyecto quedará organizada dentro de estas subcarpetas:

```text
central/
   - servidor Laravel central
   - admin
   - WhatsApp
   - API para kioskos

kiosk-agent/
   - agente ligero para Windows/Linux
   - autenticación con token
   - consulta de trabajos
   - descarga e impresión de PDFs
```

La carpeta raíz conserva el backend actual mientras se termina la fase de migración. No muevas todavía el código productivo al agente hasta que la integración esté cerrada.

## Características Principales

- **Gestión de Usuarios**
  - Registro e inicio de sesión
  - Perfiles de usuario personalizables
  - Sistema de roles (cliente/administrador)
  - Historial de actividades

- **Gestión de Archivos PDF**
  - Carga de archivos PDF
  - Análisis automático del número de páginas
  - Almacenamiento seguro en servidor
  - Historial de archivos subidos

- **Cálculo de Costos de Impresión**
  - Basado en número de páginas
  - Número de copias
  - Tipo de impresión (blanco y negro / color)
  - Tamaño de papel (A4, Letter, Legal)
  - Orientación (vertical/horizontal)

- **Gestión de Trabajos de Impresión**
  - Crear trabajos de impresión desde PDFs
  - Rastrear estado de impresiones
  - Seguimiento de fecha/hora de impresión
  - Cola de trabajos pendientes

- **Sistema de Transacciones**
  - Registro de pagos completados
  - Reembolsos por cancelación
  - Historial de transacciones
  - Ingresos totales

- **Panel de Administración**
  - Dashboard con estadísticas en tiempo real
  - Gestión de trabajos de impresión
  - Monitoreo de usuarios
  - Reportes de transacciones
  - Aprobar/cancelar trabajos

- **API REST**
  - Endpoints para integración con otros sistemas
  - Gestión remota de trabajos
  - Estadísticas del sistema

## Requisitos

- PHP 8.2+
- Laravel 12
- MySQL 8.0+
- Node.js 18+ y npm
- Composer
- Docker (opcional)

## Instalación

### Opción 1: Instalación Manual en Ubuntu Server

```bash
# 1. Descargar el script de instalación
wget https://your-repo/install-ubuntu.sh
chmod +x install-ubuntu.sh

# 2. Ejecutar el script
sudo ./install-ubuntu.sh

# El script automatizará:
# - Instalación de dependencias del sistema
# - Configuración de PHP y MySQL
# - Instalación de Composer y Node.js
# - Creación de base de datos
# - Instalación de dependencias del proyecto
# - Ejecución de migraciones
# - Configuración de Nginx
# - Inicio de servicios
```

### Opción 2: Instalación con Docker

```bash
# 1. Clonar el repositorio
git clone https://your-repo sistema-kiosko
cd sistema-kiosko

# 2. Ejecutar Docker Compose
docker-compose up -d

# 3. Ejecutar migraciones
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed

# La aplicación estará disponible en http://localhost:8000
```

### Opción 3: Instalación Manual Local

```bash
# 1. Clonar el repositorio
git clone https://your-repo sistema-kiosko
cd sistema-kiosko

# 2. Instalar dependencias
composer install
npm install

# 3. Configurar archivo .env
cp .env.example .env
php artisan key:generate

# Editar .env con tus datos de base de datos:
# DB_HOST=127.0.0.1
# DB_DATABASE=kiosko_db
# DB_USERNAME=root
# DB_PASSWORD=tu_contraseña

# 4. Crear base de datos
mysql -u root -p
CREATE DATABASE kiosko_db;
EXIT;

# 5. Ejecutar migraciones y seeders
php artisan migrate
php artisan db:seed

# 6. Compilar assets
npm run build

# 7. Iniciar servidor de desarrollo
php artisan serve
# La aplicación estará en http://localhost:8000
```

## Usuario Administrador por Defecto

| Campo | Valor |
|-------|-------|
| Email | admin@kiosko.com |
| Contraseña | password |

**Importante:** Cambiar la contraseña después del primer acceso.

## Estructura del Proyecto

```
├── central/                  # Servidor central separado por carpeta
├── kiosk-agent/              # Agente local del kiosko
├── app/
│   ├── Console/              # Comandos personalizados
│   ├── Http/
│   │   ├── Controllers/      # Controladores de la app
│   │   ├── Middleware/       # Middleware (roles, auth)
│   │   └── Requests/         # Validaciones de requests
│   ├── Models/               # Modelos de base de datos
│   │   ├── User.php
│   │   ├── PdfFile.php
│   │   ├── PrintJob.php
│   │   └── Transaction.php
│   └── Services/
│       └── PrintService.php  # Servicio de impresión
├── database/
│   ├── migrations/           # Migraciones de BD
│   └── seeders/              # Datos iniciales
├── resources/
│   ├── css/                  # Estilos
│   ├── js/                   # JavaScript
│   └── views/                # Vistas Blade
│       ├── layouts/          # Layouts base
│       ├── admin/            # Vistas de admin
│       └── pdf/              # Vistas de PDF
├── routes/
│   ├── web.php               # Rutas web
│   └── api.php               # Rutas API REST
├── storage/                  # Almacenamiento de archivos
└── public/                   # Carpeta pública (acceso público)
```

## Uso de la Aplicación

### Para Usuarios Normales

1. **Registrarse/Iniciar Sesión**
   - Ir a `/register` para crear una nueva cuenta
   - O iniciar sesión en `/login`

2. **Subir Archivo PDF**
   - Ir al dashboard
   - Hacer clic en "Nueva Impresión"
   - Seleccionar archivo PDF
   - Se analizarán automáticamente el número de páginas

3. **Crear Trabajo de Impresión**
   - Ver el PDF subido
   - Seleccionar opciones:
     - Número de copias
     - Color (Color/B&N)
     - Tamaño de papel
     - Orientación
   - Sistema calcula el costo automáticamente
   - Confirmar y crear trabajo

4. **Seguimiento**
   - Ver historial de impresiones
   - Ver estado de cada trabajo
   - Acceder a archivos anteriores

### Para Administradores

1. **Acceso al Panel Admin**
   - Ir a `/admin/dashboard`
   - Ver estadísticas globales
   - Monitorear trabajos de impresión

2. **Gestionar Trabajos**
   - Ver todos los trabajos pendientes
   - Aprobar trabajos para impresión
   - Cancelar trabajos si es necesario

3. **Ver Transacciones**
   - Historial de pagos
   - Ingresos totales
   - Reembolsos realizados

4. **Monitorear Usuarios**
   - Usuarios activos
   - Usuarios principales
   - Estadísticas de uso

## API REST

### Endpoints Disponibles

#### Listar Trabajos de Impresión
```
GET /api/print-jobs
```

#### Obtener Trabajo Específico
```
GET /api/print-jobs/{id}
```

#### Crear Trabajo de Impresión
```
POST /api/print-jobs
Content-Type: application/json

{
  "pdf_file_id": 1,
  "user_id": 1,
  "copies": 2,
  "color_type": "color",
  "paper_size": "a4",
  "orientation": "portrait"
}
```

#### Actualizar Estado de Trabajo
```
PATCH /api/print-jobs/{id}/status
Content-Type: application/json

{
  "status": "completed"
}
```

#### Estadísticas del Sistema
```
GET /api/print-jobs/statistics
```

#### Health Check
```
GET /api/health
```

## Configuración

### Integración WhatsApp + Deepseek

El proyecto incluye integración para responder mensajes de WhatsApp mediante Evolution API y generar respuestas con Deepseek.

1. Configura variables en `.env`:
   - `DEEPSEEK_API_KEY`
   - `DEEPSEEK_ENDPOINT` (default: `https://api.deepseek.ai/v1/chat`)
   - `EVOLUTION_API_BASE_URL`
   - `EVOLUTION_API_KEY`
   - `EVOLUTION_INSTANCE`
2. Expón tu app local con ngrok y usa la URL HTTPS en Evolution API.
3. En Evolution API configura el webhook para tu instancia con:
   - `POST https://TU-NGROK.ngrok.io/webhook/evolution`
4. Limpia caché de configuración:

```bash
php artisan config:clear
```

5. Prueba enviando un mensaje al número de WhatsApp conectado a tu instancia.

### Variables de Entorno (.env)

```env
# Aplicación
APP_NAME="Kiosko de Impresiones"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com
APP_KEY=base64:tu-clave-aqui

# Base de Datos
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kiosko_db
DB_USERNAME=kiosko_user
DB_PASSWORD=contraseña_segura

# Mail (opcional, para notificaciones)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=465
MAIL_USERNAME=tu_email
MAIL_PASSWORD=tu_contraseña
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@kiosko.com

# Almacenamiento
FILESYSTEM_DRIVER=local
FILESYSTEM_VISIBILITY=public

# Deepseek (IA)
DEEPSEEK_API_KEY=tu_api_key
DEEPSEEK_ENDPOINT=https://api.deepseek.ai/v1/chat

# Evolution API WhatsApp
EVOLUTION_API_BASE_URL=http://127.0.0.1:8080
EVOLUTION_API_KEY=tu_evolution_api_key
EVOLUTION_INSTANCE=kiosko
EVOLUTION_WHATSAPP_NUMBER=+14155238886
```

## Seguridad

### Recomendaciones de Seguridad para Producción

1. **HTTPS/SSL**
   - Usar certificado SSL válido
   - Redirigir HTTP a HTTPS
   - Usar HSTS headers

2. **Contraseñas**
   - Cambiar credenciales de base de datos
   - Cambiar contraseña del admin
   - Usar contraseñas fuertes (16+ caracteres)

3. **Permisos de Archivos**
   ```bash
   sudo chmod -R 755 /var/www/kiosko-impresiones
   sudo chmod -R 777 /var/www/kiosko-impresiones/storage
   sudo chmod -R 777 /var/www/kiosko-impresiones/bootstrap/cache
   sudo chown -R www-data:www-data /var/www/kiosko-impresiones
   ```

4. **Firewall**
   - Permitir solo puertos 80 y 443
   - Limitar acceso SSH
   - Usar fail2ban para proteger contra ataques

5. **Backups**
   - Hacer backups diarios de la base de datos
   - Hacer backups de archivos subidos
   - Almacenar backups en ubicación segura

## Mantenimiento

### Tareas Cron Recomendadas

```bash
# Agregar a crontab
crontab -e

# Ejecutar tareas programadas de Laravel
* * * * * php /var/www/kiosko-impresiones/artisan schedule:run >> /dev/null 2>&1

# Limpiar logs antiguos (diariamente a las 3 AM)
0 3 * * * find /var/www/kiosko-impresiones/storage/logs -mtime +30 -delete
```

### Monitoreo del Sistema

```bash
# Ver logs de la aplicación
tail -f /var/www/kiosko-impresiones/storage/logs/laravel.log

# Ver estado de servicios
sudo systemctl status nginx
sudo systemctl status php8.2-fpm
sudo systemctl status mysql

# Ver uso de disco
df -h

# Ver uso de memoria
free -h
```

## Troubleshooting

### Problema: Página 500
**Solución:**
```bash
cd /var/www/kiosko-impresiones
sudo php artisan optimize
sudo php artisan cache:clear
sudo chown -R www-data:www-data storage bootstrap/cache
```

### Problema: Archivos no se suben
**Solución:**
```bash
sudo chmod -R 777 storage/app/public
sudo chmod -R 777 storage/logs
```

### Problema: Base de datos no conecta
**Solución:**
```bash
# Verificar conexión a MySQL
mysql -h localhost -u kiosko_user -p
# Ingresar contraseña y verificar

# Reiniciar MySQL
sudo systemctl restart mysql
```

### Problema: Nginx da error 502
**Solución:**
```bash
# Verificar que PHP-FPM está corriendo
sudo systemctl status php8.2-fpm

# Si no está, iniciarlo
sudo systemctl start php8.2-fpm

# Reiniciar Nginx
sudo systemctl restart nginx
```

## Desarrollo

### Ejecutar Tests
```bash
php artisan test
```

### Compilar Assets en Desarrollo
```bash
npm run dev
```

### Generar Documentación API
```bash
php artisan scribe:generate
```

## Contribuciones

Las contribuciones son bienvenidas. Por favor:

1. Fork el proyecto
2. Crear rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abrir Pull Request

## Licencia

Este proyecto está bajo licencia MIT. Ver archivo [LICENSE](LICENSE) para más detalles.

## Soporte

Para reportar bugs o solicitar features, crear un issue en GitHub o contactar al equipo de soporte.

## Créditos

- Framework: [Laravel](https://laravel.com)
- Frontend: [Tailwind CSS](https://tailwindcss.com)
- PDF Parser: [Smalot PDF Parser](https://github.com/smalot/pdfparser)
- Desarrollado con ❤️

---

**Última actualización:** 7 de Mayo de 2026