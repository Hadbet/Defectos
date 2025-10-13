<?php
header('Content-Type: application/json');
include_once('db/db_calidad.php');

$response = ['success' => false, 'message' => ''];
$input = json_decode(file_get_contents('php://input'), true);

if (empty($input) || !isset($input['id'])) {
    $response['message'] = 'ID no proporcionado.';
    echo json_encode($response);
    exit;
}

try {
    $con = new LocalConector();
    $conex = $con->conectar();
    $stmt = $conex->prepare("UPDATE Defectos SET Status = 1 WHERE IdDefecto = ?");
    $stmt->bind_param("i", $input['id']);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $response['success'] = true;
        $response['message'] = 'El registro ha sido liberado.';
    } else {
        $response['message'] = 'El registro ya estaba liberado o no existe.';
    }
    $stmt->close();
    $conex->close();
} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
?>
