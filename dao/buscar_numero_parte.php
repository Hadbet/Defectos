<?php
header('Content-Type: application/json');
include_once('db/db_Empleado.php');

$cstNo = isset($_GET['cstNo']) ? $_GET['cstNo'] : '';

if (empty($cstNo)) {
    echo json_encode(['success' => false, 'message' => 'CstNo no especificado.']);
    exit;
}

$response = ['success' => false, 'data' => null];

try {
    $con = new LocalConector();
    $conex = $con->conectar();

    $query = "SELECT GrammerNo, Descripcion, CstNo, CstDescripcion, TpoParte, NivelIng, 
              FEmpaque, FPallet, OC, Proveedor, CantidadEtqCaja, CantidadEtqPallet, Linea, CstCve 
              FROM Master 
              WHERE CstNo = ?";

    $stmt = $conex->prepare($query);
    $stmt->bind_param("s", $cstNo);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $response['success'] = true;
        $response['data'] = $row;
    } else {
        $response['message'] = 'No se encontró ningún registro con ese CstNo.';
    }

    $stmt->close();
    $conex->close();
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>