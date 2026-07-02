<?php
require_once 'db.php';
include 'header.php';
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-info"><i class="bi bi-grid-fill"></i> Videojuegos Registrados</h2>
        <a href="registrar_juego.php" class="btn btn-primary-play">
            <i class="bi bi-plus-lg"></i> Nuevo Juego
        </a>
    </div>

    <?php
    $stmt = $pdo->query("SELECT * FROM videojuegos ORDER BY fecha_registro DESC");
    $juegos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <?php if (count($juegos) > 0): ?>
        <div class="row g-4">
            <?php foreach ($juegos as $juego): ?>
                <div class="col-md-4 col-sm-6">
                    <div class="card bg-dark text-light h-100 shadow">
                        <div class="card-body">
                            <h5 class="card-title text-info">
                                <i class="bi bi-controller"></i>
                                <?php echo htmlspecialchars($juego['titulo']); ?>
                            </h5>
                            <p class="card-text">
                                <span class="badge bg-primary me-1"><?php echo htmlspecialchars($juego['genero']); ?></span>
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($juego['plataforma']); ?></span>
                            </p>
                            <?php if (!empty($juego['precio']) && $juego['precio'] > 0): ?>
                                <p class="text-success mb-1">$<?php echo number_format($juego['precio'], 2); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($juego['anio_lanzamiento'])): ?>
                                <p class="text-muted small mb-1">Lanzamiento: <?php echo $juego['anio_lanzamiento']; ?></p>
                            <?php endif; ?>
                            <?php if ($juego['calificacion_promedio'] > 0): ?>
                                <div class="estrellas mb-2">
                                    <?php
                                    $estrellas = round($juego['calificacion_promedio']);
                                    for ($i = 1; $i <= 5; $i++) {
                                        echo $i <= $estrellas ? '<i class="bi bi-star-fill"></i> ' : '<i class="bi bi-star"></i> ';
                                    }
                                    ?>
                                    <span class="text-light ms-1"><?php echo number_format($juego['calificacion_promedio'], 1); ?></span>
                                </div>
                            <?php else: ?>
                                <p class="text-muted small mb-2">Sin calificaciones</p>
                            <?php endif; ?>
                            <a href="ver.php?id=<?php echo $juego['id']; ?>" class="btn btn-outline-info btn-sm">
                                <i class="bi bi-eye"></i> Ver Detalles
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-dark border-blue text-light text-center py-5">
            <i class="bi bi-emoji-frown fs-1 text-info"></i>
            <p class="mt-2">No hay videojuegos registrados. Agrega el primero.</p>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
