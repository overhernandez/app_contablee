<?php
session_start();
require '../includes/db_connection.php';

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Verificar permisos
if ($_SESSION['role'] != 'admin') {
    die('Acceso no autorizado');
}

$db = new Database();
$conn = $db->connect();

// Obtener listas necesarias
$clientes = $conn->query("SELECT id_cliente, nombre FROM clientes")->fetchAll();
$motivos = $conn->query("SELECT id_motivo_egreso, descripcion FROM motivos_egreso")->fetchAll();

// Procesar nuevo egreso
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_cliente = $_POST['id_cliente'];
    $id_motivo_egreso = $_POST['id_motivo_egreso'];
    $descripcion = $_POST['descripcion'];
    $monto = $_POST['monto'];
    $fecha = $_POST['fecha'];
    $beneficiario = $_POST['beneficiario'];

    try {
        $stmt = $conn->prepare("INSERT INTO egresos (id_cliente, id_motivo_egreso, descripcion, monto, fecha_egreso, beneficiario) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$id_cliente, $id_motivo_egreso, $descripcion, $monto, $fecha, $beneficiario]);
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error al registrar el egreso: " . $e->getMessage();
    }
}

// Obtener lista de egresos
try {
    $stmt = $conn->query("
        SELECT e.*, c.nombre as cliente, m.descripcion as motivo 
        FROM egresos e
        LEFT JOIN clientes c ON e.id_cliente = c.id_cliente
        LEFT JOIN motivos_egreso m ON e.id_motivo_egreso = m.id_motivo_egreso
        ORDER BY e.fecha_egreso DESC
    ");
    $egresos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Si falla el JOIN, obtener solo los egresos
    $stmt = $conn->query("SELECT * FROM egresos ORDER BY fecha_egreso DESC");
    $egresos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Egresos</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="container">
        <div style="margin-bottom: 20px; text-align: left;">
            <a href="index.php" style="text-decoration: none; color: #333; font-weight: bold; font-size: 16px;">← Volver al Sistema Contable</a>
        </div>
        <h1>Egresos</h1>
        
        <div class="form-container">
            <h2>Registrar Nuevo Egreso</h2>
            <form method="POST" action="">
                <div>
                    <label>Cliente:</label>
                    <select name="id_cliente" required>
                        <option value="">Seleccione un cliente</option>
                        <?php foreach ($clientes as $cliente): ?>
                        <option value="<?= $cliente['id_cliente'] ?>"><?= $cliente['nombre'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Motivo:</label>
                    <select name="id_motivo_egreso" required>
                        <option value="">Seleccione un motivo</option>
                        <?php foreach ($motivos as $motivo): ?>
                        <option value="<?= $motivo['id_motivo_egreso'] ?>"><?= $motivo['descripcion'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Descripción:</label>
                    <input type="text" name="descripcion" required>
                </div>
                <div>
                    <label>Monto:</label>
                    <input type="number" step="0.01" name="monto" required>
                </div>
                <div>
                    <label>Beneficiario:</label>
                    <input type="text" name="beneficiario" required>
                </div>
                <div>
                    <label>Fecha:</label>
                    <input type="date" name="fecha" required value="<?= date('Y-m-d') ?>">
                </div>
                <button type="submit">Registrar</button>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cliente</th>
                    <th>Motivo</th>
                    <th>Descripción</th>
                    <th>Monto</th>
                    <th>Beneficiario</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($egresos as $egreso): ?>
                <tr>
                    <td><?= $egreso['id_egreso'] ?></td>
                    <td><?= $egreso['cliente'] ?? 'N/A' ?></td>
                    <td><?= $egreso['motivo'] ?? 'N/A' ?></td>
                    <td><?= $egreso['descripcion'] ?></td>
                    <td><?= number_format($egreso['monto'], 2) ?></td>
                    <td><?= $egreso['beneficiario'] ?></td>
                    <td><?= date('d/m/Y', strtotime($egreso['fecha_egreso'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
