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
        <form action="logout.php" method="post" style="margin-bottom: 20px;">
            <button type="submit">Logout</button>
        </form>
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
        
        <h2>Estado de las Cuentas</h2>
        <table>
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Cliente</th>
                    <th>Motivo</th>
                    <th>Monto</th>
                    <th>Fecha</th>
                    <th>Descripción</th>
                </tr>
            </thead>
            <tbody>
                <?php
                require '../includes/db_connection.php';
                $db = new Database();
                $conn = $db->connect();

                // Fetch income data
                $stmt = $conn->query("
                    SELECT 
                        'Ingreso' AS tipo,
                        c.nombre AS cliente,
                        m.descripcion AS motivo,
                        i.monto,
                        i.fecha_ingreso AS fecha,
                        i.descripcion
                    FROM ingresos i
                    JOIN clientes c ON i.id_cliente = c.id_cliente
                    JOIN motivos_ingreso m ON i.id_motivo_ingreso = m.id_motivo_ingreso
                ");
                $ingresos = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Fetch expense data
                $stmt = $conn->query("
                    SELECT 
                        'Egreso' AS tipo,
                        c.nombre AS cliente,
                        m.descripcion AS motivo,
                        e.monto,
                        e.fecha_egreso AS fecha,
                        e.descripcion
                    FROM egresos e
                    JOIN clientes c ON e.id_cliente = c.id_cliente
                    JOIN motivos_egreso m ON e.id_motivo_egreso = m.id_motivo_egreso
                ");
                $egresos = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Combine results
                $results = array_merge($ingresos, $egresos);

                foreach ($results as $row) {
                    echo "<tr>
                            <td>{$row['tipo']}</td>
                            <td>{$row['cliente']}</td>
                            <td>{$row['motivo']}</td>
                            <td>" . number_format($row['monto'], 2) . "</td>
                            <td>" . date('d/m/Y', strtotime($row['fecha'])) . "</td>
                            <td>{$row['descripcion']}</td>
                          </tr>";
                }
                ?>
            </tbody>
        </table>
        
      
    </div>
    <script src="../js/scripts.js"></script>
</body>
</html>
