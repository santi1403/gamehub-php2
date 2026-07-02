<?php
$host = 'localhost';
$dbname = 'gamehub';
$usuario = 'postgres';
$password = 'TU_CONTRASEÑA';

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $usuario, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Error de conexión: ' . $e->getMessage());
}
