<?php
$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'gamehub';
$usuario = getenv('DB_USER') ?: 'postgres';
$password = getenv('DB_PASSWORD') ?: 'postgres';

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $usuario, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Error de conexion: ' . $e->getMessage());
}
