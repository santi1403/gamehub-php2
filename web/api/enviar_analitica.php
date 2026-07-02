<?php
require_once '../config.php';

header('Content-Type: application/json');

$datos = json_decode(file_get_contents('php://input'), true);

if ($datos) {
    $ch = curl_init($url_analitica . '/api/registrar');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($datos));
    $respuesta = curl_exec($ch);
    curl_close($ch);
    echo $respuesta;
} else {
    echo json_encode(['error' => 'No se recibieron datos']);
}
?>
