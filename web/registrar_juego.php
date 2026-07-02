<?php
require_once 'db.php';
include 'header.php';

$errores = [];
$valores = ['titulo' => '', 'genero' => '', 'plataforma' => '', 'descripcion' => '', 'precio' => '', 'anio_lanzamiento' => ''];

$generos_permitidos = ['Accion', 'Aventura', 'RPG', 'Deportes', 'Estrategia', 'Simulacion', 'Terror', 'Plataformas'];
$plataformas_permitidas = ['PC', 'PlayStation 5', 'PlayStation 4', 'Xbox Series X/S', 'Nintendo Switch', 'Movil'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $genero = trim($_POST['genero'] ?? '');
    $plataforma = trim($_POST['plataforma'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio = trim($_POST['precio'] ?? '');
    $anio = trim($_POST['anio_lanzamiento'] ?? '');

    $valores['titulo'] = htmlspecialchars($titulo);
    $valores['genero'] = $genero;
    $valores['plataforma'] = $plataforma;
    $valores['descripcion'] = htmlspecialchars($descripcion);
    $valores['precio'] = $precio;
    $valores['anio_lanzamiento'] = $anio;

    if ($titulo === '') {
        $errores['titulo'] = 'El titulo es obligatorio.';
    } elseif (strlen($titulo) < 2) {
        $errores['titulo'] = 'El titulo debe tener al menos 2 caracteres.';
    } elseif (strlen($titulo) > 200) {
        $errores['titulo'] = 'El titulo no puede exceder los 200 caracteres.';
    }

    if ($genero === '') {
        $errores['genero'] = 'Debes seleccionar un genero.';
    } elseif (!in_array($genero, $generos_permitidos)) {
        $errores['genero'] = 'Genero no valido.';
    }

    if ($plataforma === '') {
        $errores['plataforma'] = 'Debes seleccionar una plataforma.';
    } elseif (!in_array($plataforma, $plataformas_permitidas)) {
        $errores['plataforma'] = 'Plataforma no valida.';
    }

    if ($precio === '') {
        $errores['precio'] = 'El precio es obligatorio.';
    } elseif (!is_numeric($precio) || $precio < 0) {
        $errores['precio'] = 'El precio debe ser un numero positivo.';
    } elseif ($precio > 9999.99) {
        $errores['precio'] = 'El precio no puede superar $9,999.99.';
    }

    if ($anio === '') {
        $errores['anio_lanzamiento'] = 'El año de lanzamiento es obligatorio.';
    } elseif (!ctype_digit($anio)) {
        $errores['anio_lanzamiento'] = 'El año debe ser un numero entero.';
    } else {
        $anio_int = (int) $anio;
        if ($anio_int < 1970 || $anio_int > 2030) {
            $errores['anio_lanzamiento'] = 'El año debe estar entre 1970 y 2030.';
        }
    }

    if ($descripcion !== '' && strlen($descripcion) > 1000) {
        $errores['descripcion'] = 'La descripcion no puede exceder los 1000 caracteres.';
    }

    if (empty($errores)) {
        $stmt = $pdo->prepare("INSERT INTO videojuegos (titulo, genero, plataforma, descripcion, precio, anio_lanzamiento) VALUES (:titulo, :genero, :plataforma, :descripcion, :precio, :anio)");
        try {
            $stmt->execute([
                ':titulo' => $titulo,
                ':genero' => $genero,
                ':plataforma' => $plataforma,
                ':descripcion' => $descripcion,
                ':precio' => $precio,
                ':anio' => $anio_int
            ]);
            $exito = 'Videojuego "' . htmlspecialchars($titulo) . '" registrado exitosamente.';
            $valores = ['titulo' => '', 'genero' => '', 'plataforma' => '', 'descripcion' => '', 'precio' => '', 'anio_lanzamiento' => ''];
        } catch (PDOException $e) {
            $errores['db'] = 'Error al guardar en la base de datos.';
        }
    }
}
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card bg-dark text-light shadow-lg border-blue">
                <div class="card-header py-3" style="background: linear-gradient(90deg, #003087 0%, #0070CC 100%);">
                    <h4 class="mb-0"><i class="bi bi-plus-circle"></i> Registrar Nuevo Videojuego</h4>
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
                            <div>Corrige los errores antes de guardar.</div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" novalidate>
                        <div class="mb-3">
                            <label class="form-label text-info fw-bold"><i class="bi bi-tag"></i> Titulo</label>
                            <input type="text" name="titulo"
                                   class="form-control bg-dark text-light <?php echo isset($errores['titulo']) ? 'border-danger is-invalid' : 'border-secondary'; ?>"
                                   value="<?php echo $valores['titulo']; ?>"
                                   minlength="2" maxlength="200" required placeholder="Ej: The Legend of Zelda">
                            <?php if (isset($errores['titulo'])): ?>
                                <div class="invalid-feedback d-block"><i class="bi bi-x-circle"></i> <?php echo $errores['titulo']; ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-info fw-bold"><i class="bi bi-folder"></i> Genero</label>
                            <select name="genero" class="form-select bg-dark text-light <?php echo isset($errores['genero']) ? 'border-danger is-invalid' : 'border-secondary'; ?>" required>
                                <option value="">Selecciona un genero</option>
                                <?php foreach ($generos_permitidos as $g): ?>
                                    <option value="<?php echo $g; ?>" <?php echo $valores['genero'] === $g ? 'selected' : ''; ?>><?php echo $g; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errores['genero'])): ?>
                                <div class="invalid-feedback d-block"><i class="bi bi-x-circle"></i> <?php echo $errores['genero']; ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-info fw-bold"><i class="bi bi-laptop"></i> Plataforma</label>
                            <select name="plataforma" class="form-select bg-dark text-light <?php echo isset($errores['plataforma']) ? 'border-danger is-invalid' : 'border-secondary'; ?>" required>
                                <option value="">Selecciona una plataforma</option>
                                <?php foreach ($plataformas_permitidas as $p): ?>
                                    <option value="<?php echo $p; ?>" <?php echo $valores['plataforma'] === $p ? 'selected' : ''; ?>><?php echo $p; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errores['plataforma'])): ?>
                                <div class="invalid-feedback d-block"><i class="bi bi-x-circle"></i> <?php echo $errores['plataforma']; ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-info fw-bold"><i class="bi bi-currency-dollar"></i> Precio (USD)</label>
                                <input type="number" name="precio"
                                       class="form-control bg-dark text-light <?php echo isset($errores['precio']) ? 'border-danger is-invalid' : 'border-secondary'; ?>"
                                       value="<?php echo $valores['precio']; ?>"
                                       min="0" max="9999.99" step="0.01" required placeholder="Ej: 59.99">
                                <?php if (isset($errores['precio'])): ?>
                                    <div class="invalid-feedback d-block"><i class="bi bi-x-circle"></i> <?php echo $errores['precio']; ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-info fw-bold"><i class="bi bi-calendar"></i> Año de Lanzamiento</label>
                                <input type="number" name="anio_lanzamiento"
                                       class="form-control bg-dark text-light <?php echo isset($errores['anio_lanzamiento']) ? 'border-danger is-invalid' : 'border-secondary'; ?>"
                                       value="<?php echo $valores['anio_lanzamiento']; ?>"
                                       min="1970" max="2030" required placeholder="Ej: 2024">
                                <?php if (isset($errores['anio_lanzamiento'])): ?>
                                    <div class="invalid-feedback d-block"><i class="bi bi-x-circle"></i> <?php echo $errores['anio_lanzamiento']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-info fw-bold"><i class="bi bi-pencil"></i> Descripcion</label>
                            <textarea name="descripcion"
                                      class="form-control bg-dark text-light <?php echo isset($errores['descripcion']) ? 'border-danger is-invalid' : 'border-secondary'; ?>"
                                      rows="4" maxlength="1000"
                                      placeholder="Describe brevemente el videojuego..."><?php echo $valores['descripcion']; ?></textarea>
                            <small class="text-muted float-end"><span id="contador"><?php echo strlen($valores['descripcion']); ?></span>/1000</small>
                            <?php if (isset($errores['descripcion'])): ?>
                                <div class="invalid-feedback d-block"><i class="bi bi-x-circle"></i> <?php echo $errores['descripcion']; ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary-play btn-lg">
                                <i class="bi bi-save"></i> Guardar Videojuego
                            </button>
                            <a href="index.php" class="btn btn-outline-light"><i class="bi bi-arrow-left"></i> Volver</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const textarea = document.querySelector('textarea[name="descripcion"]');
const contador = document.getElementById('contador');
if (textarea) {
    textarea.addEventListener('input', function() {
        contador.textContent = this.value.length;
    });
}
</script>

<?php include 'footer.php'; ?>
