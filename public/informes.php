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

// Obtener lista de clientes para el selector
$clientes = $conn->query("SELECT id_cliente, nombre FROM clientes ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

// Obtener parámetros
$fecha = $_GET['fecha'] ?? date('Y-m-d');
$tipo_reporte = $_GET['tipo'] ?? 'diario';
$informe_tipo = $_GET['informe_tipo'] ?? 'general'; // nuevo parámetro para tipo de informe
$cliente_seleccionado = $_GET['cliente'] ?? null; // cliente seleccionado para informe personal

// Calcular rangos de fecha según tipo de reporte
if ($tipo_reporte == 'mensual') {
    $fecha_inicio = date('Y-m-01', strtotime($fecha));
    $fecha_fin = date('Y-m-t', strtotime($fecha));
} else {
    $fecha_inicio = $fecha_fin = $fecha;
}

$mensaje_error_filtro = '';
// Construir consulta con filtro por cliente si es posible
if ($informe_tipo === 'personal' && $cliente_seleccionado) {
    // Verificar si las tablas ingresos y egresos tienen columna id_cliente para filtrar
    $columnas_ingresos = $conn->query("SHOW COLUMNS FROM ingresos LIKE 'id_cliente'")->fetch();
    $columnas_egresos = $conn->query("SHOW COLUMNS FROM egresos LIKE 'id_cliente'")->fetch();

    if ($columnas_ingresos && $columnas_egresos) {
        // Filtrar por cliente
        $query = "
            SELECT 
                i.fecha_ingreso AS fecha,
                i.monto AS total_ingresos,
                0 AS total_egresos
            FROM ingresos i
            WHERE DATE(i.fecha_ingreso) = ? AND i.id_cliente = ?

            UNION ALL

            SELECT 
                e.fecha_egreso AS fecha,
                0 AS total_ingresos,
                e.monto AS total_egresos
            FROM egresos e
            WHERE DATE(e.fecha_egreso) = ? AND e.id_cliente = ?
        ";
        $params = [$fecha, $cliente_seleccionado, $fecha, $cliente_seleccionado];
    } else {
        // No se puede filtrar por cliente, mostrar mensaje
        $mensaje_error_filtro = "No es posible filtrar por persona debido a la estructura actual de la base de datos.";
        // Consulta sin filtro
        $query = "
            SELECT 
                i.fecha_ingreso AS fecha,
                i.monto AS total_ingresos,
                0 AS total_egresos
            FROM ingresos i
            WHERE DATE(i.fecha_ingreso) = ?

            UNION ALL

            SELECT 
                e.fecha_egreso AS fecha,
                0 AS total_ingresos,
                e.monto AS total_egresos
            FROM egresos e
            WHERE DATE(e.fecha_egreso) = ?
        ";
        $params = [$fecha, $fecha];
    }
} else {
    // Informe general sin filtro
    $query = "
        SELECT 
            i.fecha_ingreso AS fecha,
            i.monto AS total_ingresos,
            0 AS total_egresos
        FROM ingresos i
        WHERE DATE(i.fecha_ingreso) = ?

        UNION ALL

        SELECT 
            e.fecha_egreso AS fecha,
            0 AS total_ingresos,
            e.monto AS total_egresos
        FROM egresos e
        WHERE DATE(e.fecha_egreso) = ?
    ";
    $params = [$fecha, $fecha];
}

$stmt = $conn->prepare($query);
$stmt->execute($params);
$resumen = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular totales
$total_ingresos = 0;
$total_egresos = 0;
foreach ($resumen as $row) {
    $total_ingresos += $row['total_ingresos'];
    $total_egresos += $row['total_egresos'];
}
$diferencia = $total_ingresos - $total_egresos;
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
        .error { color: red; font-weight: bold; margin-bottom: 15px; }
    </style>
    <script>
        function toggleClienteSelector() {
            var informeTipo = document.getElementById('informe_tipo').value;
            var clienteSelector = document.getElementById('cliente_selector');
            if (informeTipo === 'personal') {
                clienteSelector.style.display = 'block';
            } else {
                clienteSelector.style.display = 'none';
            }
        }
        window.onload = function() {
            toggleClienteSelector();
        };
    </script>
</head>
<body>
    <div class="container">
        <div style="margin-bottom: 20px; text-align: left;">
            <a href="index.php" style="text-decoration: none; color: #333; font-weight: bold; font-size: 16px;">← Volver al Sistema Contable</a>
        </div>
        <h1>Informes Contables</h1>

        <?php if ($mensaje_error_filtro): ?>
            <div class="error"><?= htmlspecialchars($mensaje_error_filtro) ?></div>
        <?php endif; ?>
        
        <form method="GET" action="">
            <div class="form-group">
                <label>Tipo de Informe:</label>
                <select name="informe_tipo" id="informe_tipo" onchange="this.form.submit(); toggleClienteSelector();">
                    <option value="general" <?= $informe_tipo == 'general' ? 'selected' : '' ?>>Informe General</option>
                    <option value="personal" <?= $informe_tipo == 'personal' ? 'selected' : '' ?>>Informe Personal</option>
                </select>
            </div>
            <div class="form-group" id="cliente_selector" style="display:none;">
                <label>Seleccionar Persona:</label>
                <select name="cliente" onchange="this.form.submit()">
                    <option value="">-- Seleccione --</option>
                    <?php foreach ($clientes as $cliente): ?>
                        <option value="<?= $cliente['id_cliente'] ?>" <?= $cliente_seleccionado == $cliente['id_cliente'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cliente['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
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

        <h2>Resumen del <?= $tipo_reporte == 'mensual' ? 'mes' : 'día' ?> <?= date($tipo_reporte == 'mensual' ? 'm/Y' : 'd/m/Y', strtotime($fecha)) ?></h2>
        
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Total Ingresos</th>
                    <th>Total Egresos</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                foreach ($resumen as $row) {
                    $fecha_formateada = date('d/m/Y', strtotime($row['fecha']));
                    $total_ingresos = $row['total_ingresos'];
                    $total_egresos = $row['total_egresos'];
                ?>
                <tr>
                    <td><?= $fecha_formateada ?></td>
                    <td><?= number_format($total_ingresos, 2) ?></td>
                    <td><?= number_format($total_egresos, 2) ?></td>
                </tr>
                <?php } ?>
                <tr>
                    <td><strong>Total</strong></td>
                    <td><strong><?= number_format($total_ingresos, 2) ?></strong></td>
                    <td><strong><?= number_format($total_egresos, 2) ?></strong></td>
                </tr>
                <tr>
                    <td><strong>Diferencia</strong></td>
                    <td colspan="2"><strong><?= number_format($diferencia, 2) ?></strong></td>
                </tr>
            </tbody>
        </table>
    </div>

    <script>
        // Cambiar a selector de mes cuando es reporte mensual
        window.onload = function() {
            var dateInput = document.querySelector('input[type="date"][data-month="true"]');
            if (dateInput) {
                dateInput.type = 'month';
            }
        };
    </script>
</body>
</html>
<?php // Closing PHP tag to avoid accidental output ?>
