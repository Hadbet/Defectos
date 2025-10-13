<?php
header('Content-Type: application/json');
include_once('db/db_calidad.php');

$response = ['success' => false, 'message' => ''];
$input = json_decode(file_get_contents('php://input'), true);

if (empty($input) || !isset($input['id'], $input['numeroParte'], $input['estacion'])) {
    $response['message'] = 'Datos incompletos.';
    echo json_encode($response);
    exit;
}

try {
    $con = new LocalConector();
    $conex = $con->conectar();
    $stmt = $conex->prepare("UPDATE Defectos SET NumeroParte = ?, Estacion = ? WHERE IdDefecto = ?");
    $stmt->bind_param("ssi", $input['numeroParte'], $input['estacion'], $input['id']);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $response['success'] = true;
        $response['message'] = 'Registro actualizado correctamente.';
    } else {
        $response['message'] = 'No se realizaron cambios o el registro no existe.';
    }
    $stmt->close();
    $conex->close();
} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
?>
