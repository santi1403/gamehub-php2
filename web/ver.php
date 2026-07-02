<?php
require_once 'db.php';
require_once 'api_config.php';
include 'header.php';

$id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT * FROM videojuegos WHERE id = ?");
$stmt->execute([$id]);
$juego = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$juego) {
    echo '<div class="container"><div class="alert alert-danger">Videojuego no encontrado.</div></div>';
    include 'footer.php';
    exit;
}

$stmt = $pdo->prepare("SELECT r.*, u.nombre as usuario_nombre FROM resenas r JOIN usuarios u ON r.usuario_id = u.id WHERE r.videojuego_id = ? ORDER BY r.fecha DESC");
$stmt->execute([$id]);
$resenas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['usuario_id'])) {
        $error = 'Debes registrarte para dejar una reseña.';
    } else {
        $comentario = trim($_POST['comentario'] ?? '');
        $calificacion = trim($_POST['calificacion'] ?? '');

        if ($comentario === '') {
            $error = 'El comentario es obligatorio.';
        } elseif (strlen($comentario) < 5 || strlen($comentario) > 500) {
            $error = 'El comentario debe tener entre 5 y 500 caracteres.';
        } elseif ($calificacion === '') {
            $error = 'Debes seleccionar una calificacion.';
        } elseif (!ctype_digit($calificacion) || $calificacion < 1 || $calificacion > 5) {
            $error = 'Calificacion no valida.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO resenas (usuario_id, videojuego_id, calificacion, comentario) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_SESSION['usuario_id'], $id, $calificacion, $comentario]);

            $stmt = $pdo->prepare("UPDATE videojuegos SET calificacion_promedio = (SELECT AVG(calificacion) FROM resenas WHERE videojuego_id = ?) WHERE id = ?");
            $stmt->execute([$id, $id]);

            $stmt = $pdo->prepare("SELECT r.*, u.nombre as usuario_nombre FROM resenas r JOIN usuarios u ON r.usuario_id = u.id WHERE r.videojuego_id = ? ORDER BY r.fecha DESC");
            $stmt->execute([$id]);
            $resenas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("SELECT * FROM videojuegos WHERE id = ?");
            $stmt->execute([$id]);
            $juego = $stmt->fetch(PDO::FETCH_ASSOC);

            $ch = curl_init($api_analitica_url . '/api/calificar');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'id_videojuego' => (int) $id,
                'calificacion' => (int) $calificacion,
                'comentario' => $comentario,
                'id_usuario' => (int) $_SESSION['usuario_id'],
                'nombre_usuario' => $_SESSION['usuario_nombre']
            ]));
            curl_exec($ch);
            curl_close($ch);

            $exito = 'Reseña publicada correctamente.';
        }
    }
}
?>

<div class="container">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-info">Inicio</a></li>
            <li class="breadcrumb-item active text-light"><?php echo htmlspecialchars($juego['titulo']); ?></li>
        </ol>
    </nav>

    <div class="card bg-dark text-light border-blue shadow mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <h2 class="text-info"><?php echo htmlspecialchars($juego['titulo']); ?></h2>
                    <p>
                        <span class="badge bg-primary fs-6"><?php echo htmlspecialchars($juego['genero']); ?></span>
                        <span class="badge bg-secondary fs-6 ms-1"><?php echo htmlspecialchars($juego['plataforma']); ?></span>
                    </p>
                    <?php if (!empty($juego['precio']) && $juego['precio'] > 0): ?>
                        <p class="text-success fs-5"><strong>$<?php echo number_format($juego['precio'], 2); ?></strong></p>
                    <?php endif; ?>
                    <?php if (!empty($juego['anio_lanzamiento'])): ?>
                        <p class="text-muted">Lanzamiento: <?php echo $juego['anio_lanzamiento']; ?></p>
                    <?php endif; ?>
                    <p class="text-light mt-3"><?php echo nl2br(htmlspecialchars($juego['descripcion'] ?? 'Sin descripcion.')); ?></p>
                </div>
                <div class="col-md-4 text-center">
                    <div class="bg-game rounded p-3">
                        <h1 class="text-light display-1">
                            <?php echo $juego['calificacion_promedio'] > 0 ? number_format($juego['calificacion_promedio'], 1) : '-'; ?>
                        </h1>
                        <p class="text-light">Calificacion Promedio</p>
                        <?php if ($juego['calificacion_promedio'] > 0): ?>
                            <div class="estrellas fs-4">
                                <?php
                                $estrellas = round($juego['calificacion_promedio']);
                                for ($i = 1; $i <= 5; $i++) {
                                    echo $i <= $estrellas ? '<i class="bi bi-star-fill"></i> ' : '<i class="bi bi-star"></i> ';
                                }
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <h4 class="text-info mb-3"><i class="bi bi-chat-dots"></i> Resenas (<?php echo count($resenas); ?>)</h4>
            <?php if (count($resenas) > 0): ?>
                <?php foreach ($resenas as $resena): ?>
                    <div class="card bg-dark text-light border-secondary mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <h6 class="text-info"><i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($resena['usuario_nombre']); ?></h6>
                                <div class="estrellas">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="bi <?php echo $i <= $resena['calificacion'] ? 'bi-star-fill' : 'bi-star'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <p class="text-light mt-2"><?php echo htmlspecialchars($resena['comentario']); ?></p>
                            <small class="text-muted"><?php echo $resena['fecha']; ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-dark border-blue text-center py-4">
                    <i class="bi bi-chat-square-text fs-1 text-info"></i>
                    <p class="mt-2">No hay resenas. Se el primero en opinar.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <div class="card bg-dark text-light border-blue shadow">
                <div class="card-header py-3" style="background: linear-gradient(90deg, #003087 0%, #0070CC 100%);">
                    <h5 class="mb-0"><i class="bi bi-pencil-square"></i> Escribir Resena</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($exito)): ?>
                        <div class="alert alert-success d-flex align-items-center">
                            <i class="bi bi-check-circle-fill fs-4 me-2"></i>
                            <div><?php echo $exito; ?></div>
                        </div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?></div>
                    <?php endif; ?>

                    <?php if (!isset($_SESSION['usuario_id'])): ?>
                        <div class="alert alert-dark border-warning text-center py-3">
                            <i class="bi bi-person-lock fs-1 text-warning"></i>
                            <p class="mt-2 mb-2">Debes registrarte para dejar una reseña.</p>
                            <a href="registrar_usuario.php" class="btn btn-warning btn-sm">
                                <i class="bi bi-person-plus"></i> Registrarse ahora
                            </a>
                        </div>
                    <?php else: ?>
                        <p class="text-light"><i class="bi bi-person-check text-info"></i> Publicando como: <strong><?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></strong></p>
                        <form method="POST">
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
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-info fw-bold">Comentario</label>
                                <textarea name="comentario" class="form-control bg-dark text-light border-secondary" rows="3" minlength="5" maxlength="500" required placeholder="Escribe tu opinion..."></textarea>
                                <small class="text-muted float-end"><span id="contador">0</span>/500</small>
                            </div>
                            <button type="submit" class="btn btn-primary-play w-100">
                                <i class="bi bi-send"></i> Enviar Resena
                            </button>
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
