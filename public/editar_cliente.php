<?php
require '../includes/auth_check.php';
require '../includes/db_connection.php';

$db = new Database();
$conn = $db->connect();

// Obtener datos del cliente
$id = $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM clientes WHERE id_cliente = :id");
$stmt->bindParam(':id', $id);
$stmt->execute();
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

// Actualizar cliente
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'];
    $direccion = $_POST['direccion'];
    $telefono = $_POST['telefono'];
    $email = $_POST['email'];
    $rfc = $_POST['rfc'];

    $stmt = $conn->prepare("UPDATE clientes SET nombre = :nombre, direccion = :direccion, telefono = :telefono, email = :email, rfc = :rfc WHERE id_cliente = :id");
    $stmt->bindParam(':nombre', $nombre);
    $stmt->bindParam(':direccion', $direccion);
    $stmt->bindParam(':telefono', $telefono);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':rfc', $rfc);
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    header('Location: clientes.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Cliente - Sistema Contable</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="container">
        <h1>Editar Cliente</h1>
        
        <form method="POST" action="editar_cliente.php?id=<?php echo $id; ?>">
            <div>
                <label>Nombre:</label>
                <input type="text" name="nombre" value="<?php echo htmlspecialchars($cliente['nombre']); ?>" required>
            </div>
            <div>
                <label>Dirección:</label>
                <input type="text" name="direccion" value="<?php echo htmlspecialchars($cliente['direccion']); ?>">
            </div>
            <div>
                <label>Teléfono:</label>
                <input type="text" name="telefono" value="<?php echo htmlspecialchars($cliente['telefono']); ?>">
            </div>
            <div>
                <label>Email:</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($cliente['email']); ?>">
            </div>
            <div>
                <label>RFC:</label>
                <input type="text" name="rfc" value="<?php echo htmlspecialchars($cliente['rfc']); ?>">
            </div>
            <button type="submit">Actualizar Cliente</button>
            <a href="clientes.php">Cancelar</a>
        </form>
    </div>
</body>
</html>
