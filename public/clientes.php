<?php
require '../includes/auth_check.php';
require '../includes/db_connection.php';

$db = new Database();
$conn = $db->connect();

// Crear cliente
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['crear'])) {
    $nombre = $_POST['nombre'];
    $direccion = $_POST['direccion'];
    $telefono = $_POST['telefono'];
    $email = $_POST['email'];
    $rfc = $_POST['rfc'];

    $stmt = $conn->prepare("INSERT INTO clientes (nombre, direccion, telefono, email, rfc) VALUES (:nombre, :direccion, :telefono, :email, :rfc)");
    $stmt->bindParam(':nombre', $nombre);
    $stmt->bindParam(':direccion', $direccion);
    $stmt->bindParam(':telefono', $telefono);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':rfc', $rfc);
    $stmt->execute();
}

// Eliminar cliente
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    $stmt = $conn->prepare("DELETE FROM clientes WHERE id_cliente = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
}

// Obtener lista de clientes
$stmt = $conn->query("SELECT * FROM clientes ORDER BY nombre");
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Clientes - Sistema Contable</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="container">
        <div style="margin-bottom: 20px; text-align: left;">
            <a href="index.php" style="text-decoration: none; color: #333; font-weight: bold; font-size: 16px;">← Volver al Sistema Contable</a>
        </div>
        <h1>Gestión de Clientes</h1>
        
        <!-- Formulario para agregar cliente -->
        <form method="POST" action="clientes.php">
            <h2>Agregar Nuevo Cliente</h2>
            <div>
                <label>Nombre:</label>
                <input type="text" name="nombre" required>
            </div>
            <div>
                <label>Dirección:</label>
                <input type="text" name="direccion">
            </div>
            <div>
                <label>Teléfono:</label>
                <input type="text" name="telefono">
            </div>
            <div>
                <label>Email:</label>
                <input type="email" name="email">
            </div>
            <div>
                <label>RFC:</label>
                <input type="text" name="rfc">
            </div>
            <button type="submit" name="crear">Guardar Cliente</button>
        </form>

        <!-- Lista de clientes -->
        <h2>Lista de Clientes</h2>
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Teléfono</th>
                    <th>Email</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clientes as $cliente): ?>
                <tr>
                    <td><?php echo htmlspecialchars($cliente['nombre']); ?></td>
                    <td><?php echo htmlspecialchars($cliente['telefono']); ?></td>
                    <td><?php echo htmlspecialchars($cliente['email']); ?></td>
                    <td>
                        <a href="editar_cliente.php?id=<?php echo $cliente['id_cliente']; ?>">Editar</a>
                        <a href="clientes.php?eliminar=<?php echo $cliente['id_cliente']; ?>" onclick="return confirm('¿Eliminar este cliente?')">Eliminar</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
