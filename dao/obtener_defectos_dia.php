<?php
// dao/obtener_defectos_dia.php
header('Content-Type: application/json');
include_once('db/db_calidad.php');

$response = ['success' => false, 'data' => [], 'message' => ''];

try {
    $con = new LocalConector();
    $conex = $con->conectar();

    // Consulta para obtener los defectos registrados en la fecha actual (CURDATE)
    $stmt = $conex->prepare(
        "SELECT IdDefecto, Nomina, NumeroParte, Estacion, CodigoDefecto, DATE_FORMAT(Fecha, '%H:%i:%s') as Hora 
         FROM Defectos 
         WHERE DATE(Fecha) = CURDATE() 
         ORDER BY Fecha DESC"
    );

    $stmt->execute();
    $result = $stmt->get_result();

    $defectos = [];
    while ($row = $result->fetch_assoc()) {
        $defectos[] = $row;
    }

    $response['success'] = true;
    $response['data'] = $defectos;

    $stmt->close();
    $conex->close();

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Error al consultar los datos: ' . $e->getMessage();
}

echo json_encode($response);
?>
