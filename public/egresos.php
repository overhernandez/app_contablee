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

// Procesar nuevo egreso con manejo de errores
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $id_cliente = $_POST['id_cliente'];
        $monto = floatval($_POST['monto']);
        $fecha_egreso = $_POST['fecha_egreso'];
        $descripcion = htmlspecialchars($_POST['descripcion']);

        // Validar datos
        if(empty($id_cliente) || empty($monto) || empty($fecha_egreso)) {
            throw new Exception("Todos los campos son requeridos");
        }

        $stmt = $conn->prepare("INSERT INTO egresos (id_cliente, monto, fecha_egreso, descripcion) VALUES (?, ?, ?, ?)");
        if(!$stmt->execute([$id_cliente, $monto, $fecha_egreso, $descripcion])) {
            throw new Exception("Error al registrar el egreso");
        }
        
        $_SESSION['success'] = "Egreso registrado correctamente";
        header("Location: egresos.php");
        exit();
    } catch (PDOException $e) {
        if ($e->getCode() == 23000 && strpos($e->getMessage(), "Duplicate entry") !== false) {
            $_SESSION['error'] = "Error: Ya existe un egreso registrado para este cliente con la misma clave. Por favor, verifica la base de datos y elimina la restricción única sobre 'id_cliente' en la tabla 'egresos'.";
        } else {
            $_SESSION['error'] = $e->getMessage();
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

// Obtener lista de egresos con manejo de errores
try {
    $stmt = $conn->query("
        SELECT 
            e.id_egreso,
            c.nombre as cliente,
            e.monto,
            e.fecha_egreso,
            e.descripcion
        FROM egresos e
        JOIN clientes c ON e.id_cliente = c.id_cliente
        ORDER BY e.fecha_egreso DESC
    ");
    $egresos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $egresos = [];
}

// Obtener lista de clientes
$clientes = $conn->query("SELECT id_cliente, nombre FROM clientes")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Egresos</title>
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
        <h1>Egresos</h1>
        
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert error"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <div class="form-container">
            <h2>Registrar Nuevo Egreso</h2>
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
                    <label>Monto:</label>
                    <input type="number" step="0.01" name="monto" required>
                </div>
                <div>
                    <label>Fecha Egreso:</label>
                    <input type="date" name="fecha_egreso" required value="<?= date('Y-m-d') ?>">
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
                    <th>Monto</th>
                    <th>Fecha Egreso</th>
                    <th>Descripción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($egresos as $egreso): ?>
                <tr>
                    <td><?= $egreso['id_egreso'] ?></td>
                    <td><?= $egreso['cliente'] ?></td>
                    <td><?= number_format($egreso['monto'], 2) ?></td>
                    <td><?= date('d/m/Y', strtotime($egreso['fecha_egreso'])) ?></td>
                    <td><?= $egreso['descripcion'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
<?php // Cierre de PHP para evitar errores de fin de archivo ?>
