<?php
// dao/registrar_defecto.php
header('Content-Type: application/json');
include_once('db/db_calidad.php');

$response = ['success' => false, 'message' => ''];
$input = json_decode(file_get_contents('php://input'), true);

if (empty($input) || !isset($input['nomina'], $input['numeroParte'], $input['estacion'], $input['codigoDefecto'])) {
    $response['message'] = 'Datos incompletos para el registro.';
    echo json_encode($response);
    exit;
}

try {
    $con = new LocalConector();
    $conex = $con->conectar();

    $conex->begin_transaction();

    // Insertar el nuevo defecto
    $stmt = $conex->prepare("INSERT INTO Defectos (Nomina, NumeroParte, Estacion, CodigoDefecto, Fecha) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssi", $input['nomina'], $input['numeroParte'], $input['estacion'], $input['codigoDefecto']);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $response['success'] = true;
        $response['message'] = 'Defecto registrado exitosamente.';

        // --- Lógica de Notificación por Correo ---
        $codigoDefecto = $input['codigoDefecto'];

        // Contar cuántas veces se ha repetido el mismo defecto hoy
        $countStmt = $conex->prepare("SELECT COUNT(IdDefecto) as count FROM Defectos WHERE CodigoDefecto = ? AND DATE(Fecha) = CURDATE()");
        $countStmt->bind_param("i", $codigoDefecto);
        $countStmt->execute();
        $result = $countStmt->get_result();
        $countRow = $result->fetch_assoc();

        // Si el defecto se ha registrado exactamente 4 veces, envía el correo.
        // Esto asegura que el correo se envíe solo una vez cuando se alcanza el umbral.
        if ($countRow && $countRow['count'] > 3) {
            $to = "hadbet.altamirano@grammer.com";
            $subject = "Alerta de Defecto Recurrente: " . $codigoDefecto;
            $message = "
            <html>
            <head><title>Alerta de Calidad</title></head>
            <body>
            <p>Hola,</p>
            <p>Se ha detectado una recurrencia en un defecto de producción. Por favor, tomar las acciones correspondientes.</p>
            <ul>
                <li><strong>Código de Defecto:</strong> {$codigoDefecto}</li>
                <li><strong>Número de Parte:</strong> {$input['numeroParte']}</li>
                <li><strong>Estación:</strong> {$input['estacion']}</li>
                <li><strong>Fecha:</strong> " . date('Y-m-d') . "</li>
                <li><strong>Total de reportes hoy:</strong> {$countRow['count']}</li>
            </ul>
            <p>Este es un correo generado automáticamente por el Sistema de Registro de Defectos.</p>
            </body>
            </html>
            ";
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= 'From: <sistema.calidad@grammer.com>' . "\r\n";

            // Se intenta enviar el correo. El @ suprime errores en caso de que la configuración del servidor de correo falle.
            @mail($to, $subject, $message, $headers);
        }
        $countStmt->close();

    } else {
        $response['message'] = 'No se pudo registrar el defecto.';
        $conex->rollback();
    }

    $conex->commit();
    $stmt->close();
    $conex->close();

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Error en la base de datos: ' . $e->getMessage();
    if (isset($conex) && $conex->ping()) {
        $conex->rollback();
    }
}

echo json_encode($response);
?>
