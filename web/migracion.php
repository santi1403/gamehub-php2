<?php
require_once 'db.php';

$sql = "
    ALTER TABLE videojuegos ADD COLUMN IF NOT EXISTS precio DECIMAL(10,2) DEFAULT 0;
    ALTER TABLE videojuegos ADD COLUMN IF NOT EXISTS anio_lanzamiento INTEGER DEFAULT NULL;
    ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS edad INTEGER DEFAULT NULL;
    ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS telefono VARCHAR(20) DEFAULT NULL;
";

try {
    $pdo->exec($sql);
    echo "Migracion completada. Nuevos campos agregados correctamente.";
} catch (PDOException $e) {
    echo "Error en migracion: " . $e->getMessage();
}
