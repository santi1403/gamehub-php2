<?php
require_once 'db.php';
include 'header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'] ?? '';
    $email = $_POST['email'] ?? '';

    if ($nombre && $email) {
        try {
            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email) VALUES (:nombre, :email)");
            $stmt->execute([':nombre' => $nombre, ':email' => $email]);
            $exito = 'Usuario registrado exitosamente.';
        } catch (PDOException $e) {
            if ($e->getCode() == 23505) {
                $error = 'El email ya esta registrado.';
            } else {
                $error = 'Error al registrar: ' . $e->getMessage();
            }
        }
    } else {
        $error = 'Todos los campos son obligatorios.';
    }
}
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-5">
            <div class="card bg-black border-danger shadow">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0"><i class="bi bi-person-plus"></i> Registrar Usuario</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($exito)): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle"></i> <?php echo $exito; ?>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($error)): ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label text-danger fw-bold">Nombre Completo</label>
                            <input type="text" name="nombre" class="form-control bg-dark text-light border-secondary" required placeholder="Ej: Juan Perez">
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-danger fw-bold">Correo Electronico</label>
                            <input type="email" name="email" class="form-control bg-dark text-light border-secondary" required placeholder="Ej: juan@email.com">
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-danger btn-lg">
                                <i class="bi bi-person-check"></i> Registrarse
                            </button>
                            <a href="index.php" class="btn btn-outline-light">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
