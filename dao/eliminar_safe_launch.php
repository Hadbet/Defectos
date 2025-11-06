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
    $conex->begin_transaction();

    $id = $input['id'];

    // --- BITÁCORA: 1. Obtener datos antiguos ---
    $stmt_select = $conex->prepare("SELECT NumeroParte, Estacion, Estado, Linea, Serial FROM Safe_Launch WHERE IdSafeLaunch = ?");
    $stmt_select->bind_param("i", $id);
    $stmt_select->execute();
    $result = $stmt_select->get_result();
    $oldData = $result->fetch_assoc();
    $stmt_select->close();

    if (!$oldData) {
        throw new Exception("No se encontró el registro con ID $id.");
    }

    // --- 2. Eliminar el registro ---
    $stmt = $conex->prepare("DELETE FROM Safe_Launch WHERE IdSafeLaunch = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        // --- BITÁCORA: 3. Insertar en bitácora ---
        $Object = new DateTime();
        $Object->setTimezone(new DateTimeZone('America/Denver'));
        $DateAndTime = $Object->format("Y-m-d H:i:s");

        $stmt_bitacora = $conex->prepare("INSERT INTO BitacoraSafeLaunch 
            (IdSafeLaunch, Fecha, Accion, CampoModificado, ValorAnterior, Linea, Serial) 
            VALUES (?, ?, 'Eliminacion', 'TODO', ?, ?, ?)");

        // Guardamos un JSON de los datos eliminados en 'ValorAnterior'
        $datosEliminados = json_encode($oldData);
        $stmt_bitacora->bind_param("issss", $id, $DateAndTime, $datosEliminados, $oldData['Linea'], $oldData['Serial']);
        $stmt_bitacora->execute();
        $stmt_bitacora->close();

        $response['success'] = true;
        $response['message'] = 'El registro ha sido eliminado.';
    } else {
        $response['message'] = 'No se encontró el registro para eliminar.';
    }

    $stmt->close();
    $conex->commit();
    $conex->close();
} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Error: ' . $e->getMessage();
    if (isset($conex) && $conex->ping()) {
        $conex->rollback();
    }
}

echo json_encode($response);
?>
