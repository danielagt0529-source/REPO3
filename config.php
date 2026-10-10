<?php
declare(strict_types=1);

/*
 * Configuración de la base de datos.
 * Recomendación: en producción, define estas credenciales mediante variables de entorno
 * y no publiques este archivo en repositorios.
 */
$dbHost = getenv('DB_HOST') ?: 'mysql-danigt.alwaysdata.net';
$dbName = getenv('DB_NAME') ?: 'danigt_enviosgit';
$dbUser = getenv('DB_USER') ?: 'danigt';
$dbPass = getenv('DB_PASS') ?: 'clase1234';

$dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS envios (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            destinatario VARCHAR(150) NOT NULL,
            direccion VARCHAR(255) NOT NULL,
            descripcion TEXT NOT NULL,
            estado ENUM('Pendiente','En camino','Entregado') NOT NULL DEFAULT 'Pendiente',
            creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_envios_estado (estado),
            INDEX idx_envios_creado (creado_en)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
} catch (PDOException $e) {
    http_response_code(500);
    exit('No fue posible conectar con la base de datos. Verifica las credenciales y que la base de datos exista.');
}
