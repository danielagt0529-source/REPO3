# Ruta Bonita — Gestión de envíos

Aplicación web responsive en PHP + MySQL para crear, consultar, editar y eliminar envíos.

## Incluye
- Diseño adaptable a móvil y escritorio.
- Panel con estadísticas de envíos pendientes, en camino y entregados.
- Crear, editar y eliminar registros.
- Búsqueda por destinatario, dirección o descripción y filtro por estado.
- Creación automática de la tabla `envios` si no existe.
- Consultas preparadas PDO, escape de HTML y token CSRF para formularios.

## Instalación
1. Sube `config.php`, `index.php` y `styles.css` a un servidor con PHP 8.1+ y la extensión PDO MySQL.
2. En `config.php`, configura `DB_HOST`, `DB_NAME`, `DB_USER` y `DB_PASS` mediante variables de entorno, o edita los valores por defecto.
3. Confirma que la base de datos exista y que el usuario tenga permisos para crear tablas.
4. Abre `index.php` en el navegador. La tabla se crea automáticamente al conectar por primera vez.

## Seguridad
No publiques credenciales reales en repositorios ni compartas el panel públicamente sin protección adicional. Si una contraseña real se compartió en un chat o repositorio, cámbiala desde el proveedor de base de datos. En este paquete `DB_PASS` queda como marcador de posición: reemplázalo por la contraseña correcta antes de usar la app.

## Campos
- Destinatario
- Dirección
- Descripción
- Estado: Pendiente, En camino, Entregado
- Fechas de creación y actualización
