<?php
require_once 'db.php';
include 'header.php';

$errores = [];
$valores = ['nombre' => '', 'email' => '', 'edad' => '', 'telefono' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $edad = trim($_POST['edad'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');

    $valores['nombre'] = htmlspecialchars($nombre);
    $valores['email'] = htmlspecialchars($email);
    $valores['edad'] = $edad;
    $valores['telefono'] = htmlspecialchars($telefono);

    if ($nombre === '') {
        $errores['nombre'] = 'El nombre es obligatorio.';
    } elseif (strlen($nombre) < 2 || strlen($nombre) > 100) {
        $errores['nombre'] = 'El nombre debe tener entre 2 y 100 caracteres.';
    }

    if ($email === '') {
        $errores['email'] = 'El correo es obligatorio.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores['email'] = 'El correo no tiene un formato valido.';
    }

    if ($edad === '') {
        $errores['edad'] = 'La edad es obligatoria.';
    } elseif (!ctype_digit($edad)) {
        $errores['edad'] = 'La edad debe ser un numero entero.';
    } else {
        $edad_int = (int) $edad;
        if ($edad_int < 10 || $edad_int > 120) {
            $errores['edad'] = 'La edad debe estar entre 10 y 120 años.';
        }
    }

    if ($telefono === '') {
        $errores['telefono'] = 'El telefono es obligatorio.';
    } elseif (strlen($telefono) < 7 || strlen($telefono) > 20) {
        $errores['telefono'] = 'El telefono debe tener entre 7 y 20 digitos.';
    }

    if (empty($errores)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, edad, telefono) VALUES (:nombre, :email, :edad, :telefono)");
            $stmt->execute([
                ':nombre' => $nombre,
                ':email' => $email,
                ':edad' => $edad_int,
                ':telefono' => $telefono
            ]);

            $_SESSION['usuario_id'] = $pdo->lastInsertId();
            $_SESSION['usuario_nombre'] = $nombre;

            $exito = 'Registro exitoso. Bienvenido, ' . htmlspecialchars($nombre) . '.';
            header('Refresh: 2; URL=index.php');
        } catch (PDOException $e) {
            if ($e->getCode() == 23505) {
                $errores['email'] = 'El correo ya esta registrado.';
            } else {
                $errores['db'] = 'Error SQL: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-5">
            <div class="card bg-dark text-light shadow-lg border-blue">
                <div class="card-header py-3" style="background: linear-gradient(90deg, #003087 0%, #0070CC 100%);">
                    <h4 class="mb-0"><i class="bi bi-person-plus"></i> Registrarse en GameHub</h4>
                </div>
                <div class="card-body p-4">
                    <?php if (isset($exito)): ?>
                        <div class="alert alert-success d-flex align-items-center">
                            <i class="bi bi-check-circle-fill fs-4 me-2"></i>
                            <div><?php echo $exito; ?> Redirigiendo al inicio...</div>
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
                            <div>Corrige los errores antes de continuar.</div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" novalidate>
                        <div class="mb-3">
                            <label class="form-label text-info fw-bold"><i class="bi bi-person"></i> Nombre Completo</label>
                            <input type="text" name="nombre"
                                   class="form-control bg-dark text-light <?php echo isset($errores['nombre']) ? 'border-danger is-invalid' : 'border-secondary'; ?>"
                                   value="<?php echo $valores['nombre']; ?>"
                                   minlength="2" maxlength="100" required placeholder="Ej: Juan Perez">
                            <?php if (isset($errores['nombre'])): ?>
                                <div class="invalid-feedback d-block"><i class="bi bi-x-circle"></i> <?php echo $errores['nombre']; ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-info fw-bold"><i class="bi bi-envelope"></i> Correo Electronico</label>
                            <input type="email" name="email"
                                   class="form-control bg-dark text-light <?php echo isset($errores['email']) ? 'border-danger is-invalid' : 'border-secondary'; ?>"
                                   value="<?php echo $valores['email']; ?>"
                                   required placeholder="Ej: juan@email.com">
                            <?php if (isset($errores['email'])): ?>
                                <div class="invalid-feedback d-block"><i class="bi bi-x-circle"></i> <?php echo $errores['email']; ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-info fw-bold"><i class="bi bi-calendar-heart"></i> Edad</label>
                                <input type="number" name="edad"
                                       class="form-control bg-dark text-light <?php echo isset($errores['edad']) ? 'border-danger is-invalid' : 'border-secondary'; ?>"
                                       value="<?php echo $valores['edad']; ?>"
                                       min="10" max="120" required placeholder="Ej: 25">
                                <?php if (isset($errores['edad'])): ?>
                                    <div class="invalid-feedback d-block"><i class="bi bi-x-circle"></i> <?php echo $errores['edad']; ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-info fw-bold"><i class="bi bi-telephone"></i> Telefono</label>
                                <input type="text" name="telefono"
                                       class="form-control bg-dark text-light <?php echo isset($errores['telefono']) ? 'border-danger is-invalid' : 'border-secondary'; ?>"
                                       value="<?php echo $valores['telefono']; ?>"
                                       minlength="7" maxlength="20" required placeholder="Ej: 5551234567">
                                <?php if (isset($errores['telefono'])): ?>
                                    <div class="invalid-feedback d-block"><i class="bi bi-x-circle"></i> <?php echo $errores['telefono']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary-play btn-lg">
                                <i class="bi bi-person-check"></i> Crear Cuenta
                            </button>
                            <a href="index.php" class="btn btn-outline-light"><i class="bi bi-house"></i> Volver</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
