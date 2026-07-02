<?php
require_once 'config.php';
include 'header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'] ?? '';
    $genero = $_POST['genero'] ?? '';
    $plataforma = $_POST['plataforma'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';

    if ($nombre && $genero && $plataforma) {
        $stmt = $pdo->prepare("INSERT INTO videojuegos (nombre, genero, plataforma, descripcion) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nombre, $genero, $plataforma, $descripcion]);
        $id_juego = $pdo->lastInsertId();
        $mensaje = "Videojuego agregado correctamente.";

        $api_url = $url_analitica . '/api/registrar';
        $datos = json_encode(['id' => $id_juego, 'nombre' => $nombre, 'genero' => $genero]);
        
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $datos);
        $respuesta = curl_exec($ch);
        curl_close($ch);
    } else {
        $error = "Todos los campos son obligatorios.";
    }
}
?>

<h2>Agregar Nuevo Videojuego</h2>

<?php if (isset($mensaje)): ?>
    <div class="mensaje exito"><?php echo $mensaje; ?></div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="mensaje error"><?php echo $error; ?></div>
<?php endif; ?>

<form method="POST" class="formulario">
    <label>Nombre del Videojuego:</label>
    <input type="text" name="nombre" required>

    <label>Género:</label>
    <select name="genero" required>
        <option value="">Selecciona un género</option>
        <option value="Acción">Acción</option>
        <option value="Aventura">Aventura</option>
        <option value="RPG">RPG</option>
        <option value="Deportes">Deportes</option>
        <option value="Estrategia">Estrategia</option>
        <option value="Simulación">Simulación</option>
        <option value="Terror">Terror</option>
    </select>

    <label>Plataforma:</label>
    <select name="plataforma" required>
        <option value="">Selecciona una plataforma</option>
        <option value="PC">PC</option>
        <option value="PlayStation 5">PlayStation 5</option>
        <option value="Xbox Series X">Xbox Series X</option>
        <option value="Nintendo Switch">Nintendo Switch</option>
    </select>

    <label>Descripción:</label>
    <textarea name="descripcion"></textarea>

    <button type="submit" class="btn">Guardar Videojuego</button>
</form>

<?php include 'footer.php'; ?>
