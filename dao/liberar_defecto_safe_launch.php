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
    $conex->begin_transaction();

    $id = $input['id'];
    $nuevoStatus = $input['status'];
    $comentario = isset($input['comentario']) && !empty($input['comentario']) ? $input['comentario'] : NULL;

    // --- BITÁCORA: 1. Obtener datos antiguos ---
    $stmt_select = $conex->prepare("SELECT Estado, Linea, Serial FROM Safe_Launch WHERE IdSafeLaunch = ?");
    $stmt_select->bind_param("i", $id);
    $stmt_select->execute();
    $result = $stmt_select->get_result();
    $oldData = $result->fetch_assoc();
    $stmt_select->close();

    if (!$oldData) {
        throw new Exception("No se encontró el registro con ID $id.");
    }

    $estadoAnterior = $oldData['Estado'];

    // --- 2. Actualizar el registro ---
    $stmt = $conex->prepare("UPDATE Safe_Launch SET Estado = ?, Comentarios = ? WHERE IdSafeLaunch = ?");
    $stmt->bind_param("isi", $nuevoStatus, $comentario, $id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        // --- BITÁCORA: 3. Insertar en bitácora ---
        $Object = new DateTime();
        $Object->setTimezone(new DateTimeZone('America/Denver'));
        $DateAndTime = $Object->format("Y-m-d H:i:s");

        $stmt_bitacora = $conex->prepare("INSERT INTO BitacoraSafeLaunch 
            (IdSafeLaunch, Fecha, Accion, CampoModificado, ValorAnterior, Linea, Serial) 
            VALUES (?, ?, 'Disposicion', 'Estado', ?, ?, ?)");
        $stmt_bitacora->bind_param("isiss", $id, $DateAndTime, $estadoAnterior, $oldData['Linea'], $oldData['Serial']);
        $stmt_bitacora->execute();
        $stmt_bitacora->close();

        $response['success'] = true;
        $response['message'] = 'La disposición del registro ha sido guardada.';
    } else {
        $response['message'] = 'No se realizaron cambios. El registro podría no existir o ya tener estos datos.';
    }

    $stmt->close();
    $conex->commit();
    $conex->close();

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Error en el servidor: ' . $e->getMessage();
    if (isset($conex) && $conex->ping()) {
        $conex->rollback();
    }
}

echo json_encode($response);
?>
