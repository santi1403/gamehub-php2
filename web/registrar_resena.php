<?php
require_once 'db.php';
include 'header.php';

$errores = [];
$valores = ['videojuego_id' => '', 'calificacion' => '', 'comentario' => ''];

$stmt = $pdo->query("SELECT id, titulo FROM videojuegos ORDER BY titulo ASC");
$videojuegos = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['usuario_id'])) {
        $errores['sesion'] = 'Debes registrarte para dejar una reseña.';
    } else {
        $videojuego_id = trim($_POST['videojuego_id'] ?? '');
        $calificacion = trim($_POST['calificacion'] ?? '');
        $comentario = trim($_POST['comentario'] ?? '');

        $valores['videojuego_id'] = $videojuego_id;
        $valores['calificacion'] = $calificacion;
        $valores['comentario'] = htmlspecialchars($comentario);

        if ($videojuego_id === '') {
            $errores['videojuego_id'] = 'Debes seleccionar un videojuego.';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM videojuegos WHERE id = ?");
            $stmt->execute([$videojuego_id]);
            if (!$stmt->fetch()) {
                $errores['videojuego_id'] = 'El videojuego no existe.';
            }
        }

        if ($calificacion === '') {
            $errores['calificacion'] = 'Debes seleccionar una calificacion.';
        } elseif (!ctype_digit($calificacion)) {
            $errores['calificacion'] = 'Calificacion no valida.';
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
                $stmt->execute([$_SESSION['usuario_id'], $videojuego_id, $cal, $comentario]);

                $stmt = $pdo->prepare("UPDATE videojuegos SET calificacion_promedio = (SELECT AVG(calificacion) FROM resenas WHERE videojuego_id = ?) WHERE id = ?");
                $stmt->execute([$videojuego_id, $videojuego_id]);

                $stmt = $pdo->prepare("SELECT titulo FROM videojuegos WHERE id = ?");
                $stmt->execute([$videojuego_id]);
                $juego = $stmt->fetch();

                $exito = 'Resena publicada correctamente.';
                $valores = ['videojuego_id' => '', 'calificacion' => '', 'comentario' => ''];
            } catch (PDOException $e) {
                $errores['db'] = 'Error al guardar la resena.';
            }
        }
    }
}
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card bg-dark text-light shadow-lg border-blue">
                <div class="card-header py-3" style="background: linear-gradient(90deg, #003087 0%, #0070CC 100%);">
                    <h4 class="mb-0"><i class="bi bi-pencil-square"></i> Escribir Resena</h4>
                </div>
                <div class="card-body p-4">
                    <?php if (isset($exito)): ?>
                        <div class="alert alert-success d-flex align-items-center">
                            <i class="bi bi-check-circle-fill fs-4 me-2"></i>
                            <div><?php echo $exito; ?></div>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($errores['db'])): ?>
                        <div class="alert alert-danger d-flex align-items-center">
                            <i class="bi bi-exclamation-triangle-fill fs-4 me-2"></i>
                            <div><?php echo $errores['db']; ?></div>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($errores) && !isset($exito)): ?>
                        <div class="alert alert-warning d-flex align-items-center">
                            <i class="bi bi-exclamation-circle-fill fs-4 me-2"></i>
                            <div>Corrige los errores antes de enviar.</div>
                        </div>
                    <?php endif; ?>

                    <?php if (!isset($_SESSION['usuario_id'])): ?>
                        <div class="alert alert-dark border-warning text-center py-4">
                            <i class="bi bi-person-lock fs-1 text-warning"></i>
                            <p class="mt-2 mb-2">Debes registrarte para dejar una resena.</p>
                            <a href="registrar_usuario.php" class="btn btn-warning btn-sm">
                                <i class="bi bi-person-plus"></i> Registrarse ahora
                            </a>
                        </div>
                    <?php elseif (count($videojuegos) === 0): ?>
                        <div class="alert alert-dark border-warning text-center py-4">
                            <i class="bi bi-controller fs-1 text-warning"></i>
                            <p class="mt-2 mb-2">No hay videojuegos registrados.</p>
                            <a href="registrar_juego.php" class="btn btn-warning btn-sm">
                                <i class="bi bi-plus-circle"></i> Registrar Videojuego
                            </a>
                        </div>
                    <?php else: ?>
                        <p class="text-light mb-3">
                            <i class="bi bi-person-check text-info"></i>
                            Publicando como: <strong><?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></strong>
                        </p>
                        <form method="POST" novalidate>
                            <div class="mb-3">
                                <label class="form-label text-info fw-bold"><i class="bi bi-controller"></i> Videojuego</label>
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
                                    <div class="invalid-feedback d-block"><i class="bi bi-x-circle"></i> <?php echo $errores['videojuego_id']; ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-info fw-bold">Calificacion</label>
                                <div class="star-rating">
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <input type="radio" name="calificacion" value="<?php echo $i; ?>" id="star<?php echo $i; ?>" required>
                                        <label for="star<?php echo $i; ?>" title="<?php echo $i; ?> estrellas">
                                            <i class="bi bi-star-fill"></i>
                                        </label>
                                    <?php endfor; ?>
                                </div>
                                <?php if (isset($errores['calificacion'])): ?>
                                    <div class="invalid-feedback d-block"><i class="bi bi-x-circle"></i> <?php echo $errores['calificacion']; ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-4">
                                <label class="form-label text-info fw-bold"><i class="bi bi-chat-dots"></i> Comentario</label>
                                <textarea name="comentario"
                                          class="form-control bg-dark text-light <?php echo isset($errores['comentario']) ? 'border-danger is-invalid' : 'border-secondary'; ?>"
                                          rows="4" minlength="5" maxlength="500"
                                          required placeholder="Escribe tu opinion..."><?php echo $valores['comentario']; ?></textarea>
                                <small class="text-muted float-end"><span id="contador"><?php echo strlen($valores['comentario']); ?></span>/500</small>
                                <?php if (isset($errores['comentario'])): ?>
                                    <div class="invalid-feedback d-block"><i class="bi bi-x-circle"></i> <?php echo $errores['comentario']; ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary-play btn-lg">
                                    <i class="bi bi-send"></i> Enviar Resena
                                </button>
                                <a href="index.php" class="btn btn-outline-light"><i class="bi bi-house"></i> Volver</a>
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
