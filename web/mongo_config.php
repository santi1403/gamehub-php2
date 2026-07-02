<?php
require_once __DIR__ . '/vendor/autoload.php';

$mongo_uri = getenv('MONGO_URI') ?: 'mongodb://localhost:27017';

try {
    $mongo_client = new MongoDB\Client($mongo_uri);
    $mongo_db = $mongo_client->selectDatabase('gamehub_analitica');
    $mongo_collection = $mongo_db->selectCollection('reportes_resenas');
} catch (Exception $e) {
    $mongo_client = null;
    $mongo_collection = null;
}
