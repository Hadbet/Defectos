<?php
header('Content-Type: application/json');
include_once('db/db_calidad.php');

$linea = isset($_GET['linea']) ? $_GET['linea'] : '';
if (empty($linea)) {
    echo json_encode(['success' => false, 'message' => 'Línea no especificada.']);
    exit;
}

$response = ['success' => false, 'data' => []];

$Object = new DateTime();
$Object->setTimezone(new DateTimeZone('America/Denver'));
$format = $Object->format("Y-m-d");

try {
    $con = new LocalConector();
    $conex = $con->conectar();

    $query = "SELECT 
            NumeroParte, 
            Estacion, 
            Serial,
            Linea,
            SUM(CASE WHEN Estado = 2 THEN 1 ELSE 0 END) AS Cantidad_OK,
            SUM(CASE WHEN Estado = 1 THEN 1 ELSE 0 END) AS Cantidad_Retrabajo,
            SUM(CASE WHEN Estado = 3 THEN 1 ELSE 0 END) AS Cantidad_Scrap,
            SUM(CASE WHEN Estado = 0 THEN 1 ELSE 0 END) AS Cantidad_Pendientes,
            COUNT(DISTINCT CASE WHEN Estado = 1 OR Estado = 2 THEN Serial ELSE NULL END) AS Total_Piezas_Retrabajadas
          FROM Safe_Launch
          WHERE DATE(Fecha) = ? AND Linea = ?
          GROUP BY NumeroParte, Estacion, Serial, Linea
          ORDER BY NumeroParte, Estacion";

    $stmt = $conex->prepare($query);
    $stmt->bind_param("ss", $format, $linea);
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
    $response['message'] = $e->getMessage();
}
echo json_encode($response);
?>
