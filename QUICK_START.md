# 🚀 Guía Rápida de Instalación - Sistema de Kiosko de Impresiones

## Instalación Rápida (5 minutos)

### 1. En Ubuntu Server (Recomendado para Producción)

```bash
# Descargar e instalar en una línea
wget -O - https://your-repo/raw/main/install-ubuntu.sh | bash
```

**La instalación incluye:**
- ✓ PHP 8.2, MySQL, Node.js
- ✓ Composer, npm
- ✓ Nginx con configuración SSL-ready
- ✓ Base de datos preconfigu
- ✓ Aplicación completamente funcional

**Después de completar:**
- URL: `http://tu-dominio.com`
- Admin Email: `admin@kiosko.com`
- Admin Password: `password`
- Cambiar contraseña en primer acceso

---

### 2. Con Docker (Más Rápido)

```bash
# Clonar proyecto
git clone https://your-repo sistema-kiosko
cd sistema-kiosko

# Iniciar con Docker
docker-compose up -d

# Ejecutar setup
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed

# Acceder a http://localhost:8000
```

**Con Docker:**
- Base de datos incluida
- Nginx incluido
- Todo preconfigurado

---

### 3. Local para Desarrollo

```bash
# Instalar dependencias
git clone https://your-repo sistema-kiosko
cd sistema-kiosko
composer install
npm install

# Configurar
cp .env.example .env
php artisan key:generate

# Editar .env con datos de MySQL local
# DB_HOST=127.0.0.1
# DB_DATABASE=kiosko_db
# DB_USERNAME=root
# DB_PASSWORD=tu_contraseña

# Crear BD
mysql -u root -p < /dev/null <<EOF
CREATE DATABASE kiosko_db;
EOF

# Setup
php artisan migrate
php artisan db:seed
npm run build

# Correr
php artisan serve
# http://localhost:8000
```

---

## Acceso Inicial

| Usuario | Email | Contraseña |
|---------|-------|-----------|
| Admin | admin@kiosko.com | password |

⚠️ **CAMBIAR CONTRASEÑA INMEDIATAMENTE**

---

## Primeros Pasos

### Como Usuario (Cliente)
1. Registrarse o iniciar sesión
2. Hacer clic en "Nueva Impresión"
3. Subir archivo PDF
4. Seleccionar opciones:
   - Copias
   - Color (Color/B&N)
   - Tamaño papel (A4/Letter/Legal)
   - Orientación
5. Ver costo calculado
6. Confirmar y crear trabajo
7. Ver estado en historial

### Como Admin
1. Ir a `/admin/dashboard`
2. Ver estadísticas en tiempo real
3. Gestionar trabajos pendientes
4. Aprobar/cancelar trabajos
5. Ver transacciones y pagos

---

## URLs Importantes

```
Inicio:               http://tu-dominio.com/
Login:                http://tu-dominio.com/login
Registro:             http://tu-dominio.com/register
Dashboard:            http://tu-dominio.com/dashboard
Subir PDF:            http://tu-dominio.com/pdf/upload
Historial:            http://tu-dominio.com/print-history
Archivos:             http://tu-dominio.com/pdf-history
Panel Admin:          http://tu-dominio.com/admin/dashboard
API Base:             http://tu-dominio.com/api/
```

---

## API REST (Para Integración)

### Listar Trabajos
```bash
curl -X GET http://tu-dominio.com/api/print-jobs
```

### Crear Trabajo
```bash
curl -X POST http://tu-dominio.com/api/print-jobs \
  -H "Content-Type: application/json" \
  -d '{
    "pdf_file_id": 1,
    "user_id": 1,
    "copies": 2,
    "color_type": "color",
    "paper_size": "a4",
    "orientation": "portrait"
  }'
```

### Actualizar Estado
```bash
curl -X PATCH http://tu-dominio.com/api/print-jobs/1/status \
  -H "Content-Type: application/json" \
  -d '{"status": "completed"}'
```

### Estadísticas
```bash
curl -X GET http://tu-dominio.com/api/print-jobs/statistics
```

---

## Troubleshooting

### Página 500
```bash
php artisan optimize
php artisan cache:clear
php artisan migrate --force
```

### Archivos no se suben
```bash
chmod -R 777 storage/app/public
chmod -R 777 storage/logs
```

### Base de datos
```bash
# Verificar conexión
php artisan db
```

### Nginx error 502
```bash
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx
```

---

## Mantenimiento Básico

### Logs
```bash
tail -f storage/logs/laravel.log
```

### Usar artisan
```bash
php artisan tinker              # REPL interactivo
php artisan queue:work          # Procesar colas
php artisan schedule:run        # Ejecutar tareas programadas
php artisan migrate --refresh   # Resetear BD (desarrollo)
```

### Backup Base de Datos
```bash
mysqldump -u kiosko_user -p kiosko_db > backup.sql
```

### Restaurar Base de Datos
```bash
mysql -u kiosko_user -p kiosko_db < backup.sql
```

---

## Configuraciones Recomendadas

### Producción
```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com
MAIL_MAILER=smtp
```

### SSL/HTTPS con Let's Encrypt
```bash
sudo certbot --nginx -d tu-dominio.com
```

### Performance
```bash
php artisan config:cache
php artisan route:cache
npm run prod
```

---

## Costumización

### Cambiar Costos de Impresión
Editar `.env`:
```
PRINT_COST_BW=0.10      # Blanco y Negro $0.10 por página
PRINT_COST_COLOR=0.50   # Color $0.50 por página
```

### Tamaño Máximo de Archivo
Editar `.env`:
```
MAX_FILE_SIZE=20480     # 20 MB en KB
```

---

## Contacto y Soporte

- **Email:** support@kiosko.com
- **Issues:** https://github.com/tu-repo/issues
- **Documentación:** Consultar README.md

---

## Próximos Pasos

- [ ] Cambiar contraseña de admin
- [ ] Configurar SSL/HTTPS
- [ ] Configurar backups automáticos
- [ ] Personalizar emails
- [ ] Probar API
- [ ] Configurar monitoreo
- [ ] Entrenar usuarios

---

**¡Sistema listo para usar! 🎉**

Para dudas consultar la documentación completa en README.md
