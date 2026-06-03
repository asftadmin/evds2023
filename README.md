# EVDS 2023 - Gestion de Personal

Aplicacion PHP para gestion de personal, evaluaciones, permisos, empleados, asistencia, reportes y generacion de documentos PDF.

El proyecto esta organizado con controladores, modelos y vistas PHP tradicionales, usando AdminLTE, jQuery, Bootstrap, PostgreSQL y dependencias instaladas por Composer.

## Requisitos

- PHP 7.4 o superior.
- Apache, preferiblemente con XAMPP.
- PostgreSQL con extension `pdo_pgsql` habilitada en PHP.
- Composer.
- Navegador moderno.

## Instalacion local

1. Clonar o copiar el proyecto en el directorio web de XAMPP:

```powershell
C:\xampp\htdocs\evds2023
```

2. Instalar dependencias PHP:

```powershell
composer install
```

3. Configurar la conexion a base de datos en:

```text
config/conexion.php
```

Actualizar los valores de host, base de datos, usuario y contrasena segun el entorno local o servidor.

4. Configurar la URL base del sistema:

```text
config/conexion.php
config/config.js
```

Para ambiente local normalmente debe apuntar a:

```text
http://localhost/evds2023/
```

5. Iniciar Apache y PostgreSQL.

6. Abrir en el navegador:

```text
http://localhost/evds2023/
```

## Estructura principal

```text
config/      Configuracion general, conexion y helpers.
controller/  Controladores PHP por modulo.
models/      Modelos de acceso a datos.
public/      Assets, plugins, CSS, imagenes y librerias frontend.
view/        Vistas del sistema y modulos funcionales.
view/PDF/    Plantillas PDF generadas con TCPDF.
vendor/      Dependencias instaladas por Composer.
```

## Modulos destacados

- `view/MntEmpleados/`: historia laboral y gestion de empleados.
- `view/MntEmpleadosJ/`: busqueda e inactivacion de empleados.
- `view/MntPermisosp/`: gestion de permisos.
- `view/MntBioPro/` y `view/MntBiotime/`: integraciones de asistencia.
- `view/MntSiesa/`: consultas relacionadas con empleados Siesa.
- `view/PDF/`: documentos PDF institucionales.

## Generacion de PDF

Los documentos PDF usan TCPDF desde:

```text
public/tcpfd/tcpdf.php
```

Las vistas PDF se ubican en:

```text
view/PDF/
```

Los controladores preparan las variables necesarias y luego cargan la vista PDF correspondiente.

## Notas de desarrollo

- Revisar `git status` antes de modificar archivos; puede haber cambios locales de otros usuarios.
- No versionar credenciales reales ni datos sensibles.
- Mantener los cambios acotados al modulo afectado.
- Validar sintaxis PHP despues de modificar controladores o vistas:

```powershell
php -l controller\archivo.php
php -l view\PDF\archivo.php
```

- Validar JavaScript cuando aplique:

```powershell
node --check view\Modulo\archivo.js
```

## Dependencias

Actualmente Composer instala:

```text
phpmailer/phpmailer
```

Para actualizar dependencias:

```powershell
composer update
```

## Acceso

El ingreso principal esta en:

```text
index.php
```

El login usa el modelo de usuarios y las credenciales registradas en la base de datos.
