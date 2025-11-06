<?php
header('Content-Type: application/json');
include_once('db/db_calidad.php');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (empty($id)) {
    echo json_encode(['success' => false, 'message' => 'ID de registro no especificado.']);
    exit;
}

$response = ['success' => false, 'data' => []];

try {
    $con = new LocalConector();
    $conex = $con->conectar();

    // Asumí que la columna en BitacoraSafeLaunch que referencia a Safe_Launch se llama IdSafeLaunch
    $stmt = $conex->prepare("SELECT IdBitacoraSafeLaunch, Fecha, Accion, CampoModificado, ValorAnterior 
                             FROM BitacoraSafeLaunch 
                             WHERE IdSafeLaunch = ? 
                             ORDER BY Fecha DESC");
    $stmt->bind_param("i", $id);
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
