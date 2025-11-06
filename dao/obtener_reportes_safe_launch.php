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
        // MODIFICADO: Usar LIKE para buscar líneas principales (ej. "XNF" y que agarre "XNF - SPORT")
        $where_clauses[] = "d.Linea LIKE ?";
        $params[] = $linea . '%'; // Se añade el %
        $types .= "s";
    }
    $where_sql = implode(' AND ', $where_clauses);

    // --- 1. Consulta para Gráfico de Defectos por Tipo (Top 15) ---
    $sql_tipo = "SELECT cd.Descripcion, COUNT(d.IdSafeLaunch) as Total
                 FROM Safe_Launch d
                 JOIN Catalogo_Defectos cd ON d.CodigoDefecto = cd.IdDefecto
                 WHERE $where_sql
                 GROUP BY cd.Descripcion
                 ORDER BY Total DESC
                 LIMIT 15"; // Limitado para que la gráfica sea legible
    $stmt_tipo = $conex->prepare($sql_tipo);
    $stmt_tipo->bind_param($types, ...$params);
    $stmt_tipo->execute();
    $result_tipo = $stmt_tipo->get_result();
    $defectosPorTipo = $result_tipo->fetch_all(MYSQLI_ASSOC);
    $stmt_tipo->close();

    // --- 2. Consulta para Gráfico de Registros por Día (MODIFICADO) ---
    // (Cambiado de Mes a Día para que sea más útil)
    $sql_dia = "SELECT DATE_FORMAT(d.Fecha, '%Y-%m-%d') as Dia, COUNT(d.IdSafeLaunch) as Total
                FROM Safe_Launch d
                WHERE $where_sql
                GROUP BY Dia
                ORDER BY Dia ASC";
    $stmt_dia = $conex->prepare($sql_dia);
    $stmt_dia->bind_param($types, ...$params);
    $stmt_dia->execute();
    $result_dia = $stmt_dia->get_result();
    $defectosPorDia = $result_dia->fetch_all(MYSQLI_ASSOC);
    $stmt_dia->close();

    // --- 3. Consulta para la Tabla de Registros (MODIFICADA) ---
    // (Añadido 'Serial', cambiado 'Status' por 'Estado', y usando 'IdSafeLaunch')
    $sql_tabla = "SELECT d.IdSafeLaunch, d.Nomina, d.NumeroParte, d.Serial, d.Estacion, d.CodigoDefecto, 
                         cd.Descripcion as DescripcionDefecto, DATE_FORMAT(d.Fecha, '%Y-%m-%d %H:%i') as Fecha, 
                         d.Estado, d.Linea, d.Comentarios
                  FROM Safe_Launch d
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
        'defectosPorDia' => $defectosPorDia, // Enviando datos por día
        'tablaRegistros' => $tablaRegistros
    ];

    $conex->close();

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Error en el servidor: ' . $e->getMessage();
}

echo json_encode($response);
?>
