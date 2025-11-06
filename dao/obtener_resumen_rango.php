<?php
header('Content-Type: application/json');
include_once('db/db_calidad.php');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$response = ['success' => false, 'data' => [], 'message' => ''];

// Validar y limpiar parámetros de entrada
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] . ' 00:00:00' : null;
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] . ' 23:59:59' : null;
$linea = isset($_GET['linea']) ? $_GET['linea'] : 'todas';

if (!$fecha_inicio || !$fecha_fin) {
    $response['message'] = 'El rango de fechas es obligatorio.';
    echo json_encode($response);
    exit;
}

try {
    $con = new LocalConector();
    $conex = $con->conectar();

    // --- Preparar cláusula WHERE dinámica ---
    $where_clauses = ["Fecha BETWEEN ? AND ?"];
    $params = [$fecha_inicio, $fecha_fin];
    $types = "ss";

    if ($linea !== 'todas') {
        $where_clauses[] = "Linea LIKE ?";
        $params[] = $linea . '%';
        $types .= "s";
    }
    $where_sql = implode(' AND ', $where_clauses);

    // --- Consulta de Resumen (igual a la de resumen_dia, pero con WHERE dinámico) ---
    $query = "SELECT 
                NumeroParte, 
                Estacion, 
                Serial,
                Linea,
                SUM(CASE WHEN Estado = 2 THEN 1 ELSE 0 END) AS Cantidad_OK,
                SUM(CASE WHEN Estado = 1 THEN 1 ELSE 0 END) AS Cantidad_Retrabajo,
                SUM(CASE WHEN Estado = 3 THEN 1 ELSE 0 END) AS Cantidad_Scrap,
                SUM(CASE WHEN Estado = 0 THEN 1 ELSE 0 END) AS Cantidad_Pendientes
              FROM Safe_Launch
              WHERE $where_sql
              GROUP BY NumeroParte, Estacion, Serial, Linea
              ORDER BY NumeroParte, Estacion";

    $stmt = $conex->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();

    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    $response['success'] = true;
    $response['data'] = $data;
    $stmt->close();
    $conex->close();
} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = $e->getMessage();
}
echo json_encode($response);
?>
