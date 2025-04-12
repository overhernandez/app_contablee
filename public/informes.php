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

// Obtener parámetros de fecha
$fecha = $_GET['fecha'] ?? date('Y-m-d');
$tipo_reporte = $_GET['tipo'] ?? 'diario';

// Calcular rangos de fecha según tipo de reporte
if ($tipo_reporte == 'mensual') {
    $fecha_inicio = date('Y-m-01', strtotime($fecha));
    $fecha_fin = date('Y-m-t', strtotime($fecha));
} else {
    $fecha_inicio = $fecha_fin = $fecha;
}

// Obtener total ingresos
$stmt = $conn->prepare("SELECT SUM(monto) as total FROM ingresos WHERE fecha_ingreso BETWEEN ? AND ?");
$stmt->execute([$fecha_inicio, $fecha_fin]);
$total_ingresos = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Obtener total egresos
$stmt = $conn->prepare("SELECT SUM(monto) as total FROM egresos WHERE fecha_egreso BETWEEN ? AND ?");
$stmt->execute([$fecha_inicio, $fecha_fin]);
$total_egresos = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Calcular balance
$balance = $total_ingresos - $total_egresos;

// Obtener listado de transacciones
$transacciones = [];
$stmt = $conn->prepare("
    (SELECT 'ingreso' as tipo, id_ingreso as id, descripcion, monto, fecha_ingreso as fecha 
     FROM ingresos WHERE fecha_ingreso BETWEEN ? AND ?)
    UNION ALL
    (SELECT 'egreso' as tipo, id_egreso as id, descripcion, monto, fecha_egreso as fecha 
     FROM egresos WHERE fecha_egreso BETWEEN ? AND ?)
    ORDER BY fecha DESC
");
$stmt->execute([$fecha_inicio, $fecha_fin, $fecha_inicio, $fecha_fin]);
$transacciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informes Contables</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .resumen { background: #f8f9fa; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .positivo { color: green; }
        .negativo { color: red; }
    </style>
</head>
<body>
    <div class="container">
        <div style="margin-bottom: 20px; text-align: left;">
            <a href="index.php" style="text-decoration: none; color: #333; font-weight: bold; font-size: 16px;">← Volver al Sistema Contable</a>
        </div>
        <h1>Informes Contables</h1>
        
        <form method="GET" action="">
            <div class="form-group">
                <label>Tipo de Reporte:</label>
                <select name="tipo" onchange="this.form.submit()">
                    <option value="diario" <?= $tipo_reporte == 'diario' ? 'selected' : '' ?>>Diario</option>
                    <option value="mensual" <?= $tipo_reporte == 'mensual' ? 'selected' : '' ?>>Mensual</option>
                </select>
            </div>
            <div class="form-group">
                <label>Fecha:</label>
                <input type="date" name="fecha" value="<?= $fecha ?>" 
                       onchange="this.form.submit()"
                       <?= $tipo_reporte == 'mensual' ? 'data-month="true"' : '' ?>>
            </div>
        </form>

        <div class="resumen">
            <h2>Resumen <?= $tipo_reporte == 'diario' ? 'del día' : 'mensual' ?></h2>
            <p><strong>Total Ingresos:</strong> <?= number_format($total_ingresos, 2) ?></p>
            <p><strong>Total Egresos:</strong> <?= number_format($total_egresos, 2) ?></p>
            <p><strong>Balance:</strong> 
                <span class="<?= $balance >= 0 ? 'positivo' : 'negativo' ?>">
                    <?= number_format($balance, 2) ?>
                </span>
            </p>
        </div>

        <h2>Detalle de Transacciones</h2>
        <table>
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Descripción</th>
                    <th>Monto</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transacciones as $trans): ?>
                <tr>
                    <td><?= ucfirst($trans['tipo']) ?></td>
                    <td><?= $trans['descripcion'] ?></td>
                    <td><?= number_format($trans['monto'], 2) ?></td>
                    <td><?= date('d/m/Y', strtotime($trans['fecha'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
        // Cambiar a selector de mes cuando es reporte mensual
        document.querySelector('input[type="date"][data-month="true"]').type = 'month';
    </script>
</body>
</html>
