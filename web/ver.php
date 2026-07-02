<?php
require_once 'db.php';
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_id = $_POST['usuario_id'] ?? 0;
    $comentario = $_POST['comentario'] ?? '';
    $calificacion = $_POST['calificacion'] ?? 0;

    if ($usuario_id && $comentario && $calificacion) {
        $stmt = $pdo->prepare("INSERT INTO resenas (usuario_id, videojuego_id, calificacion, comentario) VALUES (?, ?, ?, ?)");
        $stmt->execute([$usuario_id, $id, $calificacion, $comentario]);

        $stmt = $pdo->prepare("UPDATE videojuegos SET calificacion_promedio = (SELECT AVG(calificacion) FROM resenas WHERE videojuego_id = ?) WHERE id = ?");
        $stmt->execute([$id, $id]);

        header("Location: ver.php?id=$id");
        exit;
    } else {
        $error = 'Todos los campos son obligatorios.';
    }
}

$stmt = $pdo->query("SELECT * FROM usuarios ORDER BY nombre ASC");
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-danger">Inicio</a></li>
            <li class="breadcrumb-item active text-light"><?php echo htmlspecialchars($juego['titulo']); ?></li>
        </ol>
    </nav>

    <div class="card bg-black border-danger shadow mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <h2 class="text-danger"><?php echo htmlspecialchars($juego['titulo']); ?></h2>
                    <p>
                        <span class="badge bg-danger fs-6"><?php echo htmlspecialchars($juego['genero']); ?></span>
                        <span class="badge bg-secondary fs-6 ms-1"><?php echo htmlspecialchars($juego['plataforma']); ?></span>
                    </p>
                    <p class="text-light mt-3"><?php echo nl2br(htmlspecialchars($juego['descripcion'] ?? 'Sin descripcion.')); ?></p>
                </div>
                <div class="col-md-4 text-center">
                    <div class="bg-game rounded p-3">
                        <h1 class="text-danger display-1">
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
            <h4 class="text-danger mb-3"><i class="bi bi-chat-dots"></i> Reseñas (<?php echo count($resenas); ?>)</h4>
            <?php if (count($resenas) > 0): ?>
                <?php foreach ($resenas as $resena): ?>
                    <div class="card bg-black border-secondary mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <h6 class="text-danger">
                                    <i class="bi bi-person-circle"></i>
                                    <?php echo htmlspecialchars($resena['usuario_nombre']); ?>
                                </h6>
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
                <div class="alert alert-dark border-secondary text-center">
                    <i class="bi bi-chat-square-text fs-1"></i>
                    <p class="mt-2">No hay reseñas. ¡Se el primero en opinar!</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <div class="card bg-black border-danger shadow">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="bi bi-pencil-square"></i> Escribir Reseña</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-warning"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if (count($usuarios) === 0): ?>
                        <div class="alert alert-dark border-warning text-center">
                            <p><a href="registrar_usuario.php" class="text-warning">Registra un usuario</a> para dejar una reseña.</p>
                        </div>
                    <?php else: ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label text-light">Usuario</label>
                                <select name="usuario_id" class="form-select bg-dark text-light border-secondary" required>
                                    <option value="">Selecciona tu usuario</option>
                                    <?php foreach ($usuarios as $u): ?>
                                        <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['nombre']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-light">Calificacion</label>
                                <div class="d-flex gap-1" id="star-rating">
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <input type="radio" name="calificacion" value="<?php echo $i; ?>" id="star<?php echo $i; ?>" class="d-none" required>
                                        <label for="star<?php echo $i; ?>" class="fs-4 text-secondary" style="cursor:pointer;" onmouseover="this.style.color='#ffc107'" onmouseout="this.style.color='#6c757d'" onclick="setStars(<?php echo $i; ?>)">
                                            <i class="bi bi-star-fill"></i>
                                        </label>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-light">Comentario</label>
                                <textarea name="comentario" class="form-control bg-dark text-light border-secondary" rows="3" required placeholder="Escribe tu opinion..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-danger w-100">
                                <i class="bi bi-send"></i> Enviar Reseña
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function setStars(rating) {
    document.querySelectorAll('#star-rating label').forEach((label, index) => {
        label.style.color = index < rating ? '#ffc107' : '#6c757d';
    });
}

document.querySelectorAll('#star-rating label').forEach(label => {
    label.addEventListener('click', function() {
        const rating = this.getAttribute('for').replace('star', '');
        document.querySelector(`#star${rating}`).checked = true;
        document.querySelectorAll('#star-rating label').forEach((l, i) => {
            l.style.color = i < rating ? '#ffc107' : '#6c757d';
        });
    });
});
</script>

<?php include 'footer.php'; ?>
