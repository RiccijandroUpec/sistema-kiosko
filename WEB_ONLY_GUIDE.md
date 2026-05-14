# Guía de Uso: Kiosko de Impresiones (Web Only)

## Modo Web - Sin WhatsApp

Este sistema funciona completamente a través de la página web. No requiere WhatsApp activado para operar.

### Flujo de Uso

#### **1. Usuario - Subir PDF**
```
http://localhost:8000
↓
Click "Subir PDF"
↓
Selecciona archivo PDF (máx 10MB)
↓
Email (opcional)
↓
Click "Subir"
```

#### **2. Usuario - Configurar Impresión**
```
Número de copias
Selecciona color: B/N o Color
Tamaño papel: A4, Carta, Oficio
Orientación: Vertical u Horizontal
↓
Sistema calcula automáticamente el costo
↓
Click "Crear Trabajo"
```

#### **3. Usuario - Ver Código de Pago**
```
Se muestra:
- Código de referencia (ej: REF-123456789)
- Costo total
- Detalles del trabajo
↓
Usuario toma nota del código
↓
Usuario paga en caja (efectivo, transferencia, etc)
```

#### **4. Admin - Confirmar Pago**
```
Accede a: http://localhost:8000/admin
Login con credenciales
↓
Dashboard → Trabajos Pendientes
↓
Busca código de referencia
↓
Click "Confirmar Pago"
↓
Trabajo cambia a estado: "Listo para Imprimir"
```

#### **5. Usuario - Descargar o Recoger**
```
Opción 1: Descarga PDF
  Accede a: http://localhost:8000/buscar
  Ingresa código de referencia
  Click "Descargar PDF"

Opción 2: Retiro físico
  Se entrega en mostrador
```

---

## Precios Configurables

Edita en `config/printing.php`:

```php
'cost_bw' => 0.05,        // B/N: $0.05 por página
'cost_color' => 0.20,     // Color: $0.20 por página
```

---

## Pasos Iniciales

### 1. Instalar dependencias
```bash
composer install
npm install
```

### 2. Configurar base de datos (.env)
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kiosko_db
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Ejecutar migraciones
```bash
php artisan migrate
php artisan db:seed --class=AdminSeeder
```

### 4. Generar APP_KEY
```bash
php artisan key:generate
```

### 5. Iniciar servidor
```bash
php artisan serve
```

Accede a: `http://localhost:8000`

---

## Rutas Principales

| Ruta | Descripción |
|---|---|
| `/` | Página de inicio |
| `/subir` | Subir PDF |
| `/configurar/{id}` | Configurar impresión |
| `/pago/{id}` | Ver datos de pago |
| `/estado/{referencia}` | Ver estado del trabajo |
| `/buscar` | Buscar trabajo |
| `/admin` | Dashboard del administrador |

---

## Admin - Gestión de Trabajos

Desde `/admin`:

1. **Trabajos Pendientes** - Click en trabajo → "Confirmar Pago"
2. **Ver Detalles** - PDF, copias, color, precio
3. **Descargar PDF** - Para imprimir
4. **Marcar como Impreso** - Cuando el trabajo esté listo
5. **Cancelar** - Si hay problema

---

## Base de Datos

### Tablas principales

- `users` - Administradores
- `pdf_files` - Archivos subidos
- `print_jobs` - Trabajos de impresión
- `payments` - Registros de pago
- `transactions` - Historial de transacciones

---

## WhatsApp (Opcional)

Para activar integración con WhatsApp más adelante:

1. Instala [Evolution API](https://docs.evolution-api.com/)
2. Configura en `.env`:
   ```
   EVOLUTION_API_BASE_URL=http://127.0.0.1:8080
   EVOLUTION_API_KEY=tu_key_aqui
   EVOLUTION_INSTANCE=kiosko
   EVOLUTION_WHATSAPP_NUMBER=+your_number
   ```
3. Configura webhook: `/webhook/evolution`

---

## Troubleshooting

### Error: "SQLSTATE[HY000]: General error"
```bash
php artisan migrate:reset
php artisan migrate --seed
```

### Error: "File not found" al descargar PDF
```bash
php artisan storage:link
```

### Error: "Unauthorized" en admin
```bash
php artisan cache:clear
```

---

**¡Sistema listo para usar!** 🎉
