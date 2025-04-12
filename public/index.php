<?php require '../includes/auth_check.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Contable</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="container">
        <h1>Sistema Contable</h1>
        <nav>
            <?php if(!isset($_SESSION['user_id'])): ?>
                <a href="login.php">Login</a>
            <?php else: ?>
                <a href="clientes.php">Clientes</a>
                <a href="ingresos.php">Ingresos</a>
                <a href="egresos.php">Egresos</a>
                <a href="informes.php">Informes</a>
            <?php endif; ?>
        </nav>
    </div>
    <script src="../js/scripts.js"></script>
</body>
</html>
