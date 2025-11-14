<?php
include_once('db/db_calidad.php');

try {
    $con = new LocalConector();
    $conex = $con->conectar();

    // Obtener fecha actual menos 1 día (para registros de OK y Scrap)
    $fechaLimiteOKScrap = date('Y-m-d', strtotime('-1 day'));

    // Eliminar solo registros OK (2) y Scrap (3) más antiguos que un día
    $stmt = $conex->prepare("DELETE FROM Safe_Launch WHERE Estado IN (2, 3) AND DATE(Fecha) < ?");
    $stmt->bind_param("s", $fechaLimiteOKScrap);
    $stmt->execute();

    $registrosEliminados = $stmt->affected_rows;
    $stmt->close();

    // Opcional: registra la limpieza en un log
    $logMsg = date('Y-m-d H:i:s') . " - Se eliminaron $registrosEliminados registros OK/Scrap antiguos.\n";
    file_put_contents("logs/limpieza_safe_launch.log", $logMsg, FILE_APPEND);

    $conex->close();
    echo "Limpieza completada: $registrosEliminados registros eliminados.";
} catch (Exception $e) {
    echo "Error en la limpieza: " . $e->getMessage();
    // Opcional: registra el error en un log
    $errorMsg = date('Y-m-d H:i:s') . " - ERROR: " . $e->getMessage() . "\n";
    file_put_contents("logs/limpieza_safe_launch_error.log", $errorMsg, FILE_APPEND);
}
?>
