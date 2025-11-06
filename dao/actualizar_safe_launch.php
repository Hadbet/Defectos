<?php
header('Content-Type: application/json');
include_once('db/db_calidad.php');

$response = ['success' => false, 'message' => ''];
$input = json_decode(file_get_contents('php://input'), true);

// Ahora también recibimos 'estado' y 'serial'
if (empty($input) || !isset($input['id'], $input['numeroParte'], $input['estacion'], $input['serial'], $input['estado'])) {
    $response['message'] = 'Datos incompletos (ID, Parte, Estación, Serial y Estado son requeridos).';
    echo json_encode($response);
    exit;
}

try {
    $con = new LocalConector();
    $conex = $con->conectar();
    $conex->begin_transaction();

    $id = $input['id'];
    $Object = new DateTime();
    $Object->setTimezone(new DateTimeZone('America/Denver'));
    $DateAndTime = $Object->format("Y-m-d H:i:s");

    // --- BITÁCORA: 1. Obtener datos antiguos ---
    $stmt_select = $conex->prepare("SELECT NumeroParte, Estacion, Serial, Estado, Linea FROM Safe_Launch WHERE IdSafeLaunch = ?");
    $stmt_select->bind_param("i", $id);
    $stmt_select->execute();
    $result = $stmt_select->get_result();
    $oldData = $result->fetch_assoc();
    $stmt_select->close();

    if (!$oldData) {
        throw new Exception("No se encontró el registro con ID $id.");
    }

    // --- 2. Actualizar el registro ---
    $stmt = $conex->prepare("UPDATE Safe_Launch SET NumeroParte = ?, Estacion = ?, Serial = ?, Estado = ? WHERE IdSafeLaunch = ?");
    $stmt->bind_param("sssii", $input['numeroParte'], $input['estacion'], $input['serial'], $input['estado'], $id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $response['success'] = true;
        $response['message'] = 'Registro actualizado correctamente.';

        // --- BITÁCORA: 3. Insertar cambios en bitácora ---
        $stmt_bitacora = $conex->prepare("INSERT INTO BitacoraSafeLaunch (IdSafeLaunch, Fecha, Accion, CampoModificado, ValorAnterior, Linea, Serial) VALUES (?, ?, ?, ?, ?, ?, ?)");

        // Registrar cambio de NumeroParte
        if ($oldData['NumeroParte'] != $input['numeroParte']) {
            $accion = "Actualizacion";
            $campo = "NumeroParte";
            $valorAnterior = $oldData['NumeroParte'];
            $stmt_bitacora->bind_param("issssss", $id, $DateAndTime, $accion, $campo, $valorAnterior, $oldData['Linea'], $oldData['Serial']);
            $stmt_bitacora->execute();
        }
        // Registrar cambio de Estacion
        if ($oldData['Estacion'] != $input['estacion']) {
            $accion = "Actualizacion";
            $campo = "Estacion";
            $valorAnterior = $oldData['Estacion'];
            $stmt_bitacora->bind_param("issssss", $id, $DateAndTime, $accion, $campo, $valorAnterior, $oldData['Linea'], $oldData['Serial']);
            $stmt_bitacora->execute();
        }
        // Registrar cambio de Serial
        if ($oldData['Serial'] != $input['serial']) {
            $accion = "Actualizacion";
            $campo = "Serial";
            $valorAnterior = $oldData['Serial'];
            $stmt_bitacora->bind_param("issssss", $id, $DateAndTime, $accion, $campo, $valorAnterior, $oldData['Linea'], $oldData['Serial']);
            $stmt_bitacora->execute();
        }
        // Registrar cambio de Estado
        if ($oldData['Estado'] != $input['estado']) {
            $accion = "Actualizacion";
            $campo = "Estado";
            $valorAnterior = $oldData['Estado'];
            $stmt_bitacora->bind_param("issssss", $id, $DateAndTime, $accion, $campo, $valorAnterior, $oldData['Linea'], $oldData['Serial']);
            $stmt_bitacora->execute();
        }
        $stmt_bitacora->close();

    } else {
        $response['message'] = 'No se realizaron cambios o el registro no existe.';
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
