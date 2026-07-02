<?php
// Configuracion de base de datos usando variables de entorno
$host     = getenv('DB_HOST') ?: 'localhost';
$dbname   = getenv('DB_NAME') ?: 'gamehub';
$usuario  = getenv('DB_USER') ?: 'postgres';
$password = getenv('DB_PASSWORD') ?: 'postgres';

try {
    // Intentar conexion usando PDO con PostgreSQL
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $usuario, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Manejo de error limpio
    die('Error de conexion: ' . $e->getMessage());
}
