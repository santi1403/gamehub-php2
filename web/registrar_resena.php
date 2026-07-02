<?php
require_once 'db.php';
include 'header.php';

$errores = [];
$valores = ['usuario_id' => '', 'videojuego_id' => '', 'calificacion' => '', 'comentario' => ''];

$stmt = $pdo->query("SELECT id, nombre FROM usuarios ORDER BY nombre ASC");
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT id, titulo FROM videojuegos ORDER BY titulo ASC");
$videojuegos = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_id = trim($_POST['usuario_id'] ?? '');
    $videojuego_id = trim($_POST['videojuego_id'] ?? '');
    $calificacion = trim($_POST['calificacion'] ?? '');
    $comentario = trim($_POST['comentario'] ?? '');

    $valores['usuario_id'] = $usuario_id;
    $valores['videojuego_id'] = $videojuego_id;
    $valores['calificacion'] = $calificacion;
    $valores['comentario'] = htmlspecialchars($comentario);

    if ($usuario_id === '') {
        $errores['usuario_id'] = 'Debes seleccionar un usuario.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
        $stmt->execute([$usuario_id]);
        if (!$stmt->fetch()) {
            $errores['usuario_id'] = 'El usuario seleccionado no existe.';
        }
    }

    if ($videojuego_id === '') {
        $errores['videojuego_id'] = 'Debes seleccionar un videojuego.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM videojuegos WHERE id = ?");
        $stmt->execute([$videojuego_id]);
        if (!$stmt->fetch()) {
            $errores['videojuego_id'] = 'El videojuego seleccionado no existe.';
        }
    }

    if ($calificacion === '') {
        $errores['calificacion'] = 'La calificacion es obligatoria.';
    } elseif (!ctype_digit($calificacion)) {
        $errores['calificacion'] = 'La calificacion debe ser un numero entero.';
    } else {
        $cal = (int) $calificacion;
        if ($cal < 1 || $cal > 5) {
            $errores['calificacion'] = 'La calificacion debe estar entre 1 y 5.';
        }
    }

    if ($comentario === '') {
        $errores['comentario'] = 'El comentario es obligatorio.';
    } elseif (strlen($comentario) < 5) {
        $errores['comentario'] = 'El comentario debe tener al menos 5 caracteres.';
    } elseif (strlen($comentario) > 500) {
        $errores['comentario'] = 'El comentario no puede exceder los 500 caracteres.';
    }

    if (empty($errores)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO resenas (usuario_id, videojuego_id, calificacion, comentario) VALUES (?, ?, ?, ?)");
            $stmt->execute([$usuario_id, $videojuego_id, $cal, $comentario]);

            $stmt = $pdo->prepare("UPDATE videojuegos SET calificacion_promedio = (SELECT AVG(calificacion) FROM resenas WHERE videojuego_id = ?) WHERE id = ?");
            $stmt->execute([$videojuego_id, $videojuego_id]);

            $stmt = $pdo->prepare("SELECT titulo FROM videojuegos WHERE id = ?");
            $stmt->execute([$videojuego_id]);
            $juego = $stmt->fetch();

            $exito = 'Reseña para "' . htmlspecialchars($juego['titulo']) . '" registrada exitosamente.';
            $valores = ['usuario_id' => '', 'videojuego_id' => '', 'calificacion' => '', 'comentario' => ''];
        } catch (PDOException $e) {
            $errores['db'] = 'Error al guardar la reseña. Intentalo de nuevo.';
        }
    }
}
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card bg-black border-danger shadow-lg">
                <div class="card-header bg-danger text-white py-3">
                    <h4 class="mb-0"><i class="bi bi-pencil-square"></i> Escribir Reseña</h4>
                </div>
                <div class="card-body p-4">
                    <?php if (isset($exito)): ?>
                        <div class="alert alert-success d-flex align-items-center" role="alert">
                            <i class="bi bi-check-circle-fill fs-4 me-2"></i>
                            <div><?php echo $exito; ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($errores['db'])): ?>
                        <div class="alert alert-danger d-flex align-items-center" role="alert">
                            <i class="bi bi-exclamation-triangle-fill fs-4 me-2"></i>
                            <div><?php echo $errores['db']; ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errores) && !isset($exito)): ?>
                        <div class="alert alert-warning d-flex align-items-center" role="alert">
                            <i class="bi bi-exclamation-circle-fill fs-4 me-2"></i>
                            <div>Corrige los errores antes de enviar la reseña.</div>
                        </div>
                    <?php endif; ?>

                    <?php if (count($usuarios) === 0): ?>
                        <div class="alert alert-dark border-warning text-center py-4" role="alert">
                            <i class="bi bi-person-plus fs-1 text-warning"></i>
                            <p class="mt-2 mb-2">No hay usuarios registrados.</p>
                            <a href="registrar_usuario.php" class="btn btn-warning btn-sm">
                                <i class="bi bi-person-plus"></i> Registrar Usuario
                            </a>
                        </div>
                    <?php elseif (count($videojuegos) === 0): ?>
                        <div class="alert alert-dark border-warning text-center py-4" role="alert">
                            <i class="bi bi-controller fs-1 text-warning"></i>
                            <p class="mt-2 mb-2">No hay videojuegos registrados.</p>
                            <a href="registrar_juego.php" class="btn btn-warning btn-sm">
                                <i class="bi bi-plus-circle"></i> Registrar Videojuego
                            </a>
                        </div>
                    <?php else: ?>
                        <form method="POST" novalidate>
                            <div class="mb-3">
                                <label class="form-label text-danger fw-bold">
                                    <i class="bi bi-person"></i> Usuario
                                </label>
                                <select name="usuario_id"
                                        class="form-select bg-dark text-light <?php echo isset($errores['usuario_id']) ? 'border-danger is-invalid' : 'border-secondary'; ?>"
                                        required>
                                    <option value="">Selecciona tu usuario</option>
                                    <?php foreach ($usuarios as $u): ?>
                                        <option value="<?php echo $u['id']; ?>" <?php echo $valores['usuario_id'] == $u['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($u['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errores['usuario_id'])): ?>
                                    <div class="invalid-feedback d-block">
                                        <i class="bi bi-x-circle"></i> <?php echo $errores['usuario_id']; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-danger fw-bold">
                                    <i class="bi bi-controller"></i> Videojuego
                                </label>
                                <select name="videojuego_id"
                                        class="form-select bg-dark text-light <?php echo isset($errores['videojuego_id']) ? 'border-danger is-invalid' : 'border-secondary'; ?>"
                                        required>
                                    <option value="">Selecciona un videojuego</option>
                                    <?php foreach ($videojuegos as $v): ?>
                                        <option value="<?php echo $v['id']; ?>" <?php echo $valores['videojuego_id'] == $v['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($v['titulo']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errores['videojuego_id'])): ?>
                                    <div class="invalid-feedback d-block">
                                        <i class="bi bi-x-circle"></i> <?php echo $errores['videojuego_id']; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-danger fw-bold">
                                    <i class="bi bi-star-fill text-warning"></i> Calificacion (1 - 5)
                                </label>
                                <input type="number" name="calificacion"
                                       class="form-control bg-dark text-light <?php echo isset($errores['calificacion']) ? 'border-danger is-invalid' : 'border-secondary'; ?>"
                                       value="<?php echo $valores['calificacion']; ?>"
                                       min="1" max="5" step="1"
                                       required placeholder="Del 1 al 5">
                                <?php if (isset($errores['calificacion'])): ?>
                                    <div class="invalid-feedback d-block">
                                        <i class="bi bi-x-circle"></i> <?php echo $errores['calificacion']; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-4">
                                <label class="form-label text-danger fw-bold">
                                    <i class="bi bi-chat-dots"></i> Comentario
                                </label>
                                <textarea name="comentario"
                                          class="form-control bg-dark text-light <?php echo isset($errores['comentario']) ? 'border-danger is-invalid' : 'border-secondary'; ?>"
                                          rows="4" minlength="5" maxlength="500"
                                          required placeholder="Escribe tu opinion sobre este videojuego..."><?php echo $valores['comentario']; ?></textarea>
                                <small class="text-muted float-end"><span id="contador"><?php echo strlen($valores['comentario']); ?></span>/500</small>
                                <?php if (isset($errores['comentario'])): ?>
                                    <div class="invalid-feedback d-block">
                                        <i class="bi bi-x-circle"></i> <?php echo $errores['comentario']; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-danger btn-lg">
                                    <i class="bi bi-send"></i> Enviar Reseña
                                </button>
                                <a href="index.php" class="btn btn-outline-light">
                                    <i class="bi bi-house"></i> Volver al Inicio
                                </a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const textarea = document.querySelector('textarea[name="comentario"]');
const contador = document.getElementById('contador');
if (textarea) {
    textarea.addEventListener('input', function() {
        contador.textContent = this.value.length;
    });
}
</script>

<?php include 'footer.php'; ?>
