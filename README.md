Laravel Project Setup Guide

Este documento proporciona instrucciones detalladas para configurar y ejecutar un proyecto Laravel con los requisitos especificados.

Requisitos

Asegúrate de tener instalados los siguientes requisitos antes de comenzar:

PHP >= 8.2

Composer

MySQL

Node.js y npm

Supervisor (para ejecutar procesos en segundo plano)

Redis (opcional, pero recomendado para optimización de colas y caché)

Instalación del Proyecto

1. Clonar el Repositorio

git clone <URL_DEL_REPOSITORIO>
cd <NOMBRE_DEL_PROYECTO>

2. Instalar Dependencias

composer install
npm install

3. Configurar Variables de Entorno

Renombrar el archivo .env.example a .env y configurar las siguientes variables:

IMS_API_URL=
IMS_USERNAME=
IMS_PASSWORD=
SHOPIFY_STORE=
SHOPIFY_ACCESS_TOKEN=

Configura también la conexión a la base de datos en .env:

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel_db
DB_USERNAME=root
DB_PASSWORD=

4. Generar la Clave de Aplicación

php artisan key:generate

5. Migrar la Base de Datos y Ejecutar Seeders

php artisan migrate --seed

6. Crear un Usuario de Prueba con Tinker

php artisan tinker

Dentro de Tinker, ejecutar:

use App\Models\User;
User::create([
    'name' => 'Admin',
    'email' => 'admin@example.com',
    'password' => bcrypt('password')
]);

Configuración de Supervisor

Supervisor es necesario para ejecutar el job SyncProductsJob cada tres minutos.

1. Instalar Supervisor (si no está instalado)

sudo apt update && sudo apt install supervisor

2. Crear un Archivo de Configuración para Laravel

sudo nano /etc/supervisor/conf.d/laravel-worker.conf

Agregar el siguiente contenido:

[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /ruta/al/proyecto/artisan queue:work --tries=3
autostart=true
autorestart=true
numprocs=1
user=www-data
redirect_stderr=true
stdout_logfile=/ruta/al/proyecto/storage/logs/worker.log

3. Recargar la Configuración de Supervisor

sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*

4. Verificar el Estado de Supervisor

sudo supervisorctl status

Configurar el Job en Laravel

crontab -e

Agregar la siguiente línea al final del archivo:

* * * * * php /ruta/al/proyecto/artisan schedule:run >> /dev/null 2>&1

Ejecutar en Producción

Para correr la aplicación en producción, se recomienda usar un servidor web como Nginx o Apache, configurando un Virtual Host para servir Laravel desde /public.

También se recomienda habilitar caché y optimizaciones en producción:

php artisan config:cache
php artisan route:cache
php artisan view:cache

