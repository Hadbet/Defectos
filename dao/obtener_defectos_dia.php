<?php
header('Content-Type: application/json');
include_once('../db/db_calidad.php');

$linea = isset($_GET['linea']) ? $_GET['linea'] : '';
if (empty($linea)) {
    echo json_encode(['success' => false, 'message' => 'Línea no especificada.']);
    exit;
}

$response = ['success' => false, 'data' => []];

$Object = new DateTime();
$Object->setTimezone(new DateTimeZone('America/Denver')); // Considera usar 'America/Mexico_City' si aplica
$DateAndTime = $Object->format("Y-m-d H:i:s");
$format = $Object->format("Y-m-d");

try {
    $con = new LocalConector();
    $conex = $con->conectar();
    $stmt = $conex->prepare("SELECT IdDefecto, TIME(Fecha) as Hora, Nomina, NumeroParte, Estacion, CodigoDefecto, Estado FROM Defectos WHERE DATE(Fecha) = ? AND Linea = ? ORDER BY Fecha DESC");
    $stmt->bind_param("ss",$format,$linea);
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

