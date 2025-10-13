<?php
header('Content-Type: application/json');
include_once('db/db_calidad.php');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$response = ['success' => false, 'message' => ''];
$input = json_decode(file_get_contents('php://input'), true);

if (empty($input) || !isset($input['id'], $input['status'])) {
    $response['message'] = 'Datos incompletos (ID y Status son requeridos).';
    echo json_encode($response);
    exit;
}

try {
    $con = new LocalConector();
    $conex = $con->conectar();

    // El comentario es opcional, si no viene, se guarda como NULL.
    $comentario = isset($input['comentario']) && !empty($input['comentario']) ? $input['comentario'] : NULL;

    $stmt = $conex->prepare("UPDATE Defectos SET Estado = ?, Comentarios = ? WHERE IdDefecto = ?");
    $stmt->bind_param("isi", $input['status'], $comentario, $input['id']);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $response['success'] = true;
        $response['message'] = 'La disposición del registro ha sido guardada.';
    } else {
        $response['message'] = 'No se realizaron cambios. El registro podría no existir o ya tener estos datos.';
    }
    $stmt->close();
    $conex->close();

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Error en el servidor: ' . $e->getMessage();
}

echo json_encode($response);
?>

