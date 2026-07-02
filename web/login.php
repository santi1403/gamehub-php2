<?php
require_once 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email === '') {
        $error = 'Ingresa tu correo.';
    } else {
        $stmt = $pdo->prepare("SELECT id, nombre FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        if ($usuario) {
            session_start();
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];
            header('Location: index.php');
            exit;
        } else {
            $error = 'Correo no encontrado. Registrate primero.';
        }
    }
}

include 'header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-4">
            <div class="card bg-dark text-light shadow-lg border-blue">
                <div class="card-header py-3" style="background: linear-gradient(90deg, #003087 0%, #0070CC 100%);">
                    <h4 class="mb-0"><i class="bi bi-box-arrow-in-right"></i> Iniciar Sesion</h4>
                </div>
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label text-info fw-bold"><i class="bi bi-envelope"></i> Correo Electronico</label>
                            <input type="email" name="email" class="form-control bg-dark text-light border-secondary" required placeholder="Ej: juan@email.com">
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary-play btn-lg">
                                <i class="bi bi-box-arrow-in-right"></i> Entrar
                            </button>
                            <a href="registrar_usuario.php" class="btn btn-outline-light">Crear cuenta nueva</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
