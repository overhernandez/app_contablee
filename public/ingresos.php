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

// Procesar nuevo ingreso con manejo de errores
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $id_cliente = $_POST['id_cliente'];
        $id_motivo_ingreso = $_POST['id_motivo_ingreso'];
        $monto = floatval($_POST['monto']);
        $fecha_ingreso = $_POST['fecha_ingreso'];
        $descripcion = htmlspecialchars($_POST['descripcion']);

        // Validar datos
        if(empty($id_cliente) || empty($id_motivo_ingreso) || empty($monto) || empty($fecha_ingreso)) {
            throw new Exception("Todos los campos son requeridos");
        }

        $stmt = $conn->prepare("INSERT INTO ingresos (id_cliente, id_motivo_ingreso, monto, fecha_ingreso, descripcion) VALUES (?, ?, ?, ?, ?)");
        if(!$stmt->execute([$id_cliente, $id_motivo_ingreso, $monto, $fecha_ingreso, $descripcion])) {
            throw new Exception("Error al registrar el ingreso");
        }
        
        $_SESSION['success'] = "Ingreso registrado correctamente";
        header("Location: ingresos.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

// Obtener lista de ingresos con manejo de errores
try {
    $stmt = $conn->query("
        SELECT 
            i.id_ingreso,
            c.nombre as cliente,
            m.descripcion as motivo,
            i.monto,
            i.fecha_ingreso,
            i.descripcion,
            i.fecha_registro
        FROM ingresos i
        JOIN clientes c ON i.id_cliente = c.id_cliente
    JOIN motivos_ingreso m ON i.id_motivo_ingreso = m.id_motivo_ingreso
        ORDER BY i.fecha_ingreso DESC
    ");
    $ingresos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Error al cargar los ingresos: " . $e->getMessage();
    $ingresos = [];
}

// Obtener lista de clientes
$clientes = $conn->query("SELECT id_cliente, nombre FROM clientes")->fetchAll();

// Obtener lista de motivos
$motivos = $conn->query("SELECT id_motivo_ingreso as id_motivo, descripcion FROM motivos_ingreso")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Ingresos</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .alert {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .error {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
        }
        .success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
        }
    </style>
</head>
<body>
    <div class="container">
        <div style="margin-bottom: 20px; text-align: left;">
            <a href="index.php" style="text-decoration: none; color: #333; font-weight: bold; font-size: 16px;">← Volver al Sistema Contable</a>
        </div>
        <h1>Ingresos</h1>
        
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert error"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <div class="form-container">
            <h2>Registrar Nuevo Ingreso</h2>
            <form method="POST" action="">
                <div>
                    <label>Cliente:</label>
                    <select name="id_cliente" required>
                        <?php foreach ($clientes as $cliente): ?>
                        <option value="<?= $cliente['id_cliente'] ?>"><?= $cliente['nombre'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Motivo:</label>
                    <select name="id_motivo_ingreso" required>
                        <?php foreach ($motivos as $motivo): ?>
                        <option value="<?= $motivo['id_motivo'] ?>"><?= $motivo['descripcion'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Monto:</label>
                    <input type="number" step="0.01" name="monto" required>
                </div>
                <div>
                    <label>Fecha Ingreso:</label>
                    <input type="date" name="fecha_ingreso" required value="<?= date('Y-m-d') ?>">
                </div>
                <div>
                    <label>Descripción:</label>
                    <input type="text" name="descripcion" required>
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
                    <th>Monto</th>
                    <th>Fecha Ingreso</th>
                    <th>Descripción</th>
                    <th>Fecha Registro</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ingresos as $ingreso): ?>
                <tr>
                    <td><?= $ingreso['id_ingreso'] ?></td>
                    <td><?= $ingreso['cliente'] ?></td>
                    <td><?= $ingreso['motivo'] ?></td>
                    <td><?= number_format($ingreso['monto'], 2) ?></td>
                    <td><?= date('d/m/Y', strtotime($ingreso['fecha_ingreso'])) ?></td>
                    <td><?= $ingreso['descripcion'] ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($ingreso['fecha_registro'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
