#!/bin/bash

# Sistema de Kiosko de Impresiones - Script de Instalación para Ubuntu Server
# Este script automatiza la instalación completa del sistema

set -e

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}================================${NC}"
echo -e "${GREEN}Sistema de Kiosko de Impresiones${NC}"
echo -e "${GREEN}Script de Instalación${NC}"
echo -e "${GREEN}================================${NC}"
echo ""

# Variables de configuración
PROJECT_PATH="/var/www/kiosko-impresiones"
DOMAIN="impresiones.local"
DB_NAME="kiosko_db"
DB_USER="kiosko_user"
DB_PASSWORD=$(openssl rand -base64 12)
ADMIN_EMAIL="admin@kiosko.com"
ADMIN_PASSWORD="password"

echo -e "${YELLOW}Iniciando instalación...${NC}"
echo ""

# 1. Actualizar sistema
echo -e "${YELLOW}1. Actualizando sistema...${NC}"
sudo apt-get update -qq
sudo apt-get upgrade -y -qq
echo -e "${GREEN}✓ Sistema actualizado${NC}"
echo ""

# 2. Instalar dependencias del sistema
echo -e "${YELLOW}2. Instalando dependencias del sistema...${NC}"
sudo apt-get install -y -qq curl gnupg2 ca-certificates apt-transport-https software-properties-common
sudo apt-get install -y -qq git zip unzip
echo -e "${GREEN}✓ Dependencias instaladas${NC}"
echo ""

# 3. Instalar PHP 8.2 y extensiones
echo -e "${YELLOW}3. Instalando PHP 8.2 y extensiones necesarias...${NC}"
sudo add-apt-repository ppa:ondrej/php -y -qq
sudo apt-get update -qq
sudo apt-get install -y -qq php8.2 php8.2-cli php8.2-fpm php8.2-mysql php8.2-mbstring \
    php8.2-xml php8.2-curl php8.2-gd php8.2-zip php8.2-bcmath php8.2-intl
echo -e "${GREEN}✓ PHP 8.2 instalado${NC}"
echo ""

# 4. Instalar Composer
echo -e "${YELLOW}4. Instalando Composer...${NC}"
if ! command -v composer &> /dev/null; then
    curl -sS https://getcomposer.org/installer -o composer-setup.php
    sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer
    rm composer-setup.php
    echo -e "${GREEN}✓ Composer instalado${NC}"
else
    echo -e "${GREEN}✓ Composer ya instalado${NC}"
fi
echo ""

# 5. Instalar Node.js y npm
echo -e "${YELLOW}5. Instalando Node.js y npm...${NC}"
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y -qq nodejs
echo -e "${GREEN}✓ Node.js instalado${NC}"
echo ""

# 6. Instalar MySQL
echo -e "${YELLOW}6. Instalando MySQL...${NC}"
sudo apt-get install -y -qq mysql-server
echo -e "${GREEN}✓ MySQL instalado${NC}"
echo ""

# 7. Instalar Nginx
echo -e "${YELLOW}7. Instalando Nginx...${NC}"
sudo apt-get install -y -qq nginx
echo -e "${GREEN}✓ Nginx instalado${NC}"
echo ""

# 8. Crear base de datos
echo -e "${YELLOW}8. Configurando base de datos...${NC}"
sudo mysql -e "CREATE DATABASE IF NOT EXISTS $DB_NAME;"
sudo mysql -e "CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASSWORD';"
sudo mysql -e "GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"
echo -e "${GREEN}✓ Base de datos creada${NC}"
echo ""

# 9. Clonar o copiar proyecto
echo -e "${YELLOW}9. Preparando proyecto...${NC}"
sudo mkdir -p $PROJECT_PATH
cd $PROJECT_PATH
echo -e "${GREEN}✓ Directorio de proyecto preparado${NC}"
echo ""

# 10. Instalar dependencias del proyecto
echo -e "${YELLOW}10. Instalando dependencias del proyecto...${NC}"
sudo composer install --no-interaction --optimize-autoloader
sudo npm install
echo -e "${GREEN}✓ Dependencias instaladas${NC}"
echo ""

# 11. Configurar archivo .env
echo -e "${YELLOW}11. Configurando archivo .env...${NC}"
sudo cp .env.example .env
sudo sed -i "s/DB_DATABASE=laravel/DB_DATABASE=$DB_NAME/" .env
sudo sed -i "s/DB_USERNAME=root/DB_USERNAME=$DB_USER/" .env
sudo sed -i "s/DB_PASSWORD=/DB_PASSWORD=$DB_PASSWORD/" .env
sudo php artisan key:generate
echo -e "${GREEN}✓ Archivo .env configurado${NC}"
echo ""

# 12. Ejecutar migraciones
echo -e "${YELLOW}12. Ejecutando migraciones de base de datos...${NC}"
sudo php artisan migrate --force
echo -e "${GREEN}✓ Migraciones completadas${NC}"
echo ""

# 13. Crear usuario administrador
echo -e "${YELLOW}13. Creando usuario administrador...${NC}"
sudo php artisan db:seed
echo -e "${GREEN}✓ Usuario administrador creado${NC}"
echo ""

# 14. Compilar assets
echo -e "${YELLOW}14. Compilando assets del proyecto...${NC}"
sudo npm run build
echo -e "${GREEN}✓ Assets compilados${NC}"
echo ""

# 15. Configurar permisos
echo -e "${YELLOW}15. Configurando permisos...${NC}"
sudo chown -R www-data:www-data $PROJECT_PATH
sudo chmod -R 755 $PROJECT_PATH
sudo chmod -R 777 $PROJECT_PATH/storage
sudo chmod -R 777 $PROJECT_PATH/bootstrap/cache
echo -e "${GREEN}✓ Permisos configurados${NC}"
echo ""

# 16. Configurar Nginx
echo -e "${YELLOW}16. Configurando Nginx...${NC}"
sudo tee /etc/nginx/sites-available/kiosko > /dev/null <<EOF
server {
    listen 80;
    server_name $DOMAIN;
    root $PROJECT_PATH/public;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    index index.php index.html index.htm;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

sudo ln -sf /etc/nginx/sites-available/kiosko /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl restart nginx
echo -e "${GREEN}✓ Nginx configurado${NC}"
echo ""

# 17. Configurar servicios
echo -e "${YELLOW}17. Configurando servicios de Laravel...${NC}"
sudo systemctl start mysql
sudo systemctl start php8.2-fpm
sudo systemctl start nginx
sudo systemctl enable mysql
sudo systemctl enable php8.2-fpm
sudo systemctl enable nginx
echo -e "${GREEN}✓ Servicios iniciados${NC}"
echo ""

# 18. Crear archivos de logs
echo -e "${YELLOW}18. Configurando logs...${NC}"
sudo touch $PROJECT_PATH/storage/logs/laravel.log
sudo chown www-data:www-data $PROJECT_PATH/storage/logs/laravel.log
echo -e "${GREEN}✓ Logs configurados${NC}"
echo ""

echo -e "${GREEN}================================${NC}"
echo -e "${GREEN}¡INSTALACIÓN COMPLETADA!${NC}"
echo -e "${GREEN}================================${NC}"
echo ""
echo -e "${YELLOW}Información de acceso:${NC}"
echo "URL: http://$DOMAIN"
echo "Email Admin: $ADMIN_EMAIL"
echo "Contraseña Admin: $ADMIN_PASSWORD"
echo ""
echo -e "${YELLOW}Credenciales de Base de Datos:${NC}"
echo "Base de Datos: $DB_NAME"
echo "Usuario: $DB_USER"
echo "Contraseña: $DB_PASSWORD"
echo ""
echo -e "${YELLOW}Próximos pasos:${NC}"
echo "1. Configurar el dominio en tu servidor DNS"
echo "2. Actualizar la URL de la aplicación en config/app.php"
echo "3. Configurar SSL/HTTPS (recomendado con Let's Encrypt)"
echo "4. Cambiar la contraseña del administrador después del primer acceso"
echo ""
echo -e "${YELLOW}Para configurar SSL con Let's Encrypt:${NC}"
echo "sudo apt-get install -y certbot python3-certbot-nginx"
echo "sudo certbot --nginx -d $DOMAIN"
echo ""
