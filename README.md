# BNN Laravel - Docker Read/Write MySQL

Proyecto Laravel completamente contenerizado con Docker, Nginx, PHP-FPM
y dos instancias MySQL configuradas para separar operaciones de
escritura y lectura mediante replicación.

## Arquitectura

``` text
Navegador
   |
   v
Nginx :8080
   |
   v
Laravel / PHP-FPM
   |
   +-- SELECT -----------------> mysql-read:3306
   |
   +-- INSERT / UPDATE / DELETE -> mysql-write:3306
                                      |
                                      | GTID replication
                                      v
                                  mysql-read
```

Los servicios principales son:

-   `app`: Laravel sobre PHP-FPM.
-   `nginx`: servidor web.
-   `mysql-write`: servidor MySQL principal para escritura.
-   `mysql-read`: réplica utilizada para lectura.
-   `mysql-replica-init`: contenedor de una sola ejecución que configura
    automáticamente la replicación.
-   `node` (si está habilitado en `docker-compose.yml`): Vite/Node para
    los assets del frontend.

## Requisitos

Para ejecutar el proyecto no es necesario instalar PHP, Composer, MySQL
ni Node.js localmente.

Solo necesitas:

-   Git
-   Docker Desktop
-   Docker Compose, incluido actualmente con Docker Desktop

En Windows se recomienda ejecutar los comandos desde PowerShell o la
terminal integrada de VS Code.

## Instalación

### 1. Clonar el repositorio

``` bash
git clone <URL_DEL_REPOSITORIO>
cd <NOMBRE_DEL_PROYECTO>
```

### 2. Crear el archivo de entorno

Si el repositorio no incluye `.env`:

``` bash
cp .env.example .env
```

En PowerShell también puedes usar:

``` powershell
Copy-Item .env.example .env
```

La configuración de base de datos debe apuntar a los nombres de los
servicios Docker:

``` env
DB_CONNECTION=mysql

DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=secret

DB_WRITE_HOST=mysql-write
DB_WRITE_PORT=3306

DB_READ_HOST=mysql-read
DB_READ_PORT=3306

DB_STICKY=false
```

No uses `127.0.0.1` como host de MySQL desde Laravel, ya que PHP también
se ejecuta dentro de Docker.

### 3. Construir y levantar el proyecto

``` bash
docker compose up -d --build
```

Este comando construye las imágenes necesarias y levanta los servicios
en segundo plano.

Durante una instalación limpia se ejecuta automáticamente el siguiente
flujo:

``` text
mysql-write
     |
     v
mysql-read
     |
     v
mysql-replica-init
     |
     v
Laravel
     |
     +-- migraciones
     |
     v
PHP-FPM
     |
     v
Nginx
```

El servicio `mysql-replica-init` configura automáticamente `mysql-read`
para replicar los cambios provenientes de `mysql-write`.

El entrypoint de Laravel ejecuta las migraciones pendientes antes de
iniciar PHP-FPM.

### 4. Generar APP_KEY

Si el proyecto acaba de ser clonado y `.env` no contiene una `APP_KEY`:

``` bash
docker compose exec app php artisan key:generate
```

### 5. Acceder a la aplicación

Abre:

``` text
http://localhost:8080
```

## Verificar los contenedores

Ejecuta:

``` bash
docker compose ps -a
```

El resultado esperado es conceptualmente:

``` text
bnn-app                  Up
laravel-nginx            Up
mysql-write              Up (healthy)
mysql-read               Up (healthy)
mysql-replica-init       Exited (0)
```

`mysql-replica-init` en estado `Exited (0)` es correcto. Este contenedor
solo configura la replicación y después finaliza.

## Verificar la replicación

Puedes consultar el estado directamente desde Docker:

``` bash
docker compose exec mysql-read mysql -uroot -proot -e "SHOW REPLICA STATUS\G"
```

Los valores importantes son:

``` text
Replica_IO_Running: Yes
Replica_SQL_Running: Yes
Last_SQL_Errno: 0
```

Si ambos procesos aparecen como `Yes`, la réplica está funcionando
correctamente.

## Conexiones de lectura y escritura en Laravel

Laravel utiliza una única conexión lógica `mysql`, pero tiene hosts
diferentes para lectura y escritura.

Ejemplo de configuración en `config/database.php`:

``` php
'mysql' => [
    'driver' => 'mysql',

    'read' => [
        'host' => [
            env('DB_READ_HOST', 'mysql-read'),
        ],
        'port' => env('DB_READ_PORT', '3306'),
    ],

    'write' => [
        'host' => [
            env('DB_WRITE_HOST', 'mysql-write'),
        ],
        'port' => env('DB_WRITE_PORT', '3306'),
    ],

    'sticky' => env('DB_STICKY', false),

    'database' => env('DB_DATABASE', 'laravel'),
    'username' => env('DB_USERNAME', 'laravel'),
    'password' => env('DB_PASSWORD', 'secret'),

    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'prefix_indexes' => true,
    'strict' => true,
],
```

Laravel enviará automáticamente:

``` text
SELECT                     -> mysql-read
INSERT / UPDATE / DELETE   -> mysql-write
```

No es necesario seleccionar manualmente la conexión desde Eloquent o
Query Builder.

## Comprobar las conexiones desde Laravel

Abre Tinker:

``` bash
docker compose exec app php artisan tinker
```

Para comprobar el servidor utilizado para lectura:

``` php
DB::connection()
    ->getReadPdo()
    ->query('SELECT @@server_id')
    ->fetchColumn();
```

Debe devolver:

``` text
2
```

Para comprobar escritura:

``` php
DB::connection()
    ->getPdo()
    ->query('SELECT @@server_id')
    ->fetchColumn();
```

Debe devolver:

``` text
1
```

Por tanto:

``` text
server_id 1 = mysql-write
server_id 2 = mysql-read
```

## Migraciones

Las migraciones se ejecutan automáticamente al iniciar el contenedor de
Laravel.

También pueden ejecutarse manualmente:

``` bash
docker compose exec app php artisan migrate
```

Para consultar su estado:

``` bash
docker compose exec app php artisan migrate:status
```

## Comandos Artisan

Como PHP está dentro de Docker, los comandos Artisan deben ejecutarse a
través del contenedor `app`.

Ejemplos:

``` bash
docker compose exec app php artisan optimize:clear
```

``` bash
docker compose exec app php artisan make:model Product -m
```

``` bash
docker compose exec app php artisan make:controller ProductController
```

``` bash
docker compose exec app php artisan tinker
```

## Composer

Composer también se encuentra dentro del contenedor:

``` bash
docker compose exec app composer install
```

Para instalar un paquete:

``` bash
docker compose exec app composer require vendor/package
```

## Desarrollo

El código fuente está montado dentro del contenedor mediante un bind
mount:

``` yaml
volumes:
  - .:/var/www/html
```

Esto significa que puedes editar normalmente el proyecto desde Windows
utilizando VS Code.

Los cambios en archivos PHP, Blade, rutas, controladores, modelos, etc.
aparecen inmediatamente dentro del contenedor.

No necesitas reconstruir Docker después de cada cambio de código.

## Vite y frontend

El Laravel Starter Kit utiliza Vite para generar los assets CSS y
JavaScript.

Si el proyecto utiliza un servicio `node` en Docker para desarrollo,
puede configurarse de forma similar a:

``` yaml
node:
  image: node:22-alpine
  working_dir: /var/www/html

  volumes:
    - .:/var/www/html

  ports:
    - "5173:5173"

  command: sh -c "npm install && npm run build"

  networks:
    - laravel-network
```

Para Desarrollo:

``` bash

docker compose run --rm --service-ports node npm run dev -- --host 0.0.0.0
```

Laravel necesita que exista:

``` text
public/build/manifest.json
```

cuando se utilizan assets compilados mediante `@vite`.

## Logs

Logs de Laravel/PHP:

``` bash
docker compose logs app
```

Seguir los logs en tiempo real:

``` bash
docker compose logs -f app
```

Logs de Nginx:

``` bash
docker compose logs nginx
```

Logs del primary:

``` bash
docker compose logs mysql-write
```

Logs de la réplica:

``` bash
docker compose logs mysql-read
```

Logs de inicialización de la réplica:

``` bash
docker compose logs mysql-replica-init
```

## Detener el proyecto

Para detener y eliminar los contenedores:

``` bash
docker compose down
```

Los datos de MySQL se conservan porque utilizan volúmenes persistentes.

Para volver a levantar el proyecto:

``` bash
docker compose up -d
```

## Reiniciar completamente el entorno

> **Advertencia:** este comando elimina también los volúmenes de MySQL
> y, por tanto, todos los datos almacenados.

``` bash
docker compose down -v
```

Después puedes reconstruir todo desde cero:

``` bash
docker compose up -d --build
```

Esto es útil para comprobar que el proyecto puede instalarse
correctamente desde una máquina limpia.

## Reconstruir imágenes

Cuando solo modificas código Laravel no necesitas reconstruir las
imágenes.

Debes utilizar `--build` cuando cambies elementos como:

-   `Dockerfile`
-   extensiones PHP
-   paquetes del sistema operativo
-   scripts de inicialización
-   configuración que forme parte de la imagen

``` bash
docker compose up -d --build
```

## Persistencia de datos

Los datos de MySQL se almacenan en los volúmenes:

``` yaml
volumes:
  mysql_write_data:
  mysql_read_data:
```

Por ello un:

``` bash
docker compose down
```

no elimina la base de datos.

En cambio:

``` bash
docker compose down -v
```

sí elimina los datos.

## Flujo de base de datos

La aplicación nunca necesita seleccionar manualmente qué servidor
utilizar:

``` text
                   Laravel
                      |
             +--------+--------+
             |                 |
           SELECT            WRITE
             |                 |
             v                 v
        mysql-read        mysql-write
             ^                 |
             |                 |
             +---- replica ----+
```

Las escrituras se realizan en el primary y MySQL las replica hacia
`mysql-read`.

## Solución de problemas

### ViteManifestNotFoundException

Si aparece:

``` text
Illuminate\Foundation\ViteManifestNotFoundException
```

comprueba:

``` bash
docker compose exec app ls public/build/manifest.json
```

Si no existe, compila los assets:

``` bash
docker compose run --rm node npm run build
```

### La réplica no está ejecutándose

Comprueba:

``` bash
docker compose exec mysql-read mysql -uroot -proot -e "SHOW REPLICA STATUS\G"
```

Revisa especialmente:

``` text
Replica_IO_Running
Replica_SQL_Running
Last_SQL_Errno
Last_SQL_Error
```

Para inspeccionar errores de los workers:

``` sql
SELECT
    WORKER_ID,
    SERVICE_STATE,
    LAST_ERROR_NUMBER,
    LAST_ERROR_MESSAGE
FROM performance_schema.replication_applier_status_by_worker
WHERE LAST_ERROR_NUMBER <> 0;
```

### Limpiar cachés de Laravel

``` bash
docker compose exec app php artisan optimize:clear
```

## Notas

-   `mysql-write` es la fuente de verdad de los datos.
-   `mysql-read` no debe inicializarse independientemente con
    `MYSQL_DATABASE`, `MYSQL_USER` o `MYSQL_PASSWORD`; esos cambios
    deben llegar mediante replicación.
-   El usuario de replicación se crea en `mysql-write`.
-   `mysql-replica-init` configura automáticamente el source del reader.
-   Las migraciones deben ejecutarse contra la conexión de escritura.
-   En un entorno productivo real, las credenciales mostradas en este
    README deben reemplazarse por secretos seguros.
