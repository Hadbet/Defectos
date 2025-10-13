<?php
header('Content-Type: application/json');
include_once('db/db_calidad.php');

// Activar reporte de errores para depuración
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$response = ['success' => false, 'message' => '', 'data' => []];

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
    $where_clauses = ["d.Fecha BETWEEN ? AND ?"];
    $params = [$fecha_inicio, $fecha_fin];
    $types = "ss";

    if ($linea !== 'todas') {
        $where_clauses[] = "d.Linea = ?";
        $params[] = $linea;
        $types .= "s";
    }
    $where_sql = implode(' AND ', $where_clauses);

    // --- 1. Consulta para Gráfico de Defectos por Tipo ---
    $sql_tipo = "SELECT cd.Descripcion, COUNT(d.IdDefecto) as Total
                 FROM Defectos d
                 JOIN Catalogo_Defectos cd ON d.CodigoDefecto = cd.IdDefecto
                 WHERE $where_sql
                 GROUP BY cd.Descripcion
                 ORDER BY Total DESC";
    $stmt_tipo = $conex->prepare($sql_tipo);
    $stmt_tipo->bind_param($types, ...$params);
    $stmt_tipo->execute();
    $result_tipo = $stmt_tipo->get_result();
    $defectosPorTipo = $result_tipo->fetch_all(MYSQLI_ASSOC);
    $stmt_tipo->close();

    // --- 2. Consulta para Gráfico de Defectos por Mes ---
    $sql_mes = "SELECT DATE_FORMAT(d.Fecha, '%Y-%m') as Mes, COUNT(d.IdDefecto) as Total
                FROM Defectos d
                WHERE $where_sql
                GROUP BY Mes
                ORDER BY Mes ASC";
    $stmt_mes = $conex->prepare($sql_mes);
    $stmt_mes->bind_param($types, ...$params);
    $stmt_mes->execute();
    $result_mes = $stmt_mes->get_result();
    $defectosPorMes = $result_mes->fetch_all(MYSQLI_ASSOC);
    $stmt_mes->close();

    // --- 3. Consulta para la Tabla de Registros ---
    $sql_tabla = "SELECT d.IdDefecto, d.Nomina, d.NumeroParte, d.Estacion, d.CodigoDefecto, 
                         cd.Descripcion as DescripcionDefecto, DATE_FORMAT(d.Fecha, '%Y-%m-%d %H:%i') as Fecha, d.Estado, d.Linea, d.Comentarios
                  FROM Defectos d
                  JOIN Catalogo_Defectos cd ON d.CodigoDefecto = cd.IdDefecto
                  WHERE $where_sql
                  ORDER BY d.Fecha DESC";
    $stmt_tabla = $conex->prepare($sql_tabla);
    $stmt_tabla->bind_param($types, ...$params);
    $stmt_tabla->execute();
    $result_tabla = $stmt_tabla->get_result();
    $tablaRegistros = $result_tabla->fetch_all(MYSQLI_ASSOC);
    $stmt_tabla->close();

    $response['success'] = true;
    $response['data'] = [
        'defectosPorTipo' => $defectosPorTipo,
        'defectosPorMes' => $defectosPorMes,
        'tablaRegistros' => $tablaRegistros
    ];

    $conex->close();

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Error en el servidor: ' . $e->getMessage();
}

echo json_encode($response);
?>
