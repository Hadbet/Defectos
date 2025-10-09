<?php
// dao/registrar_defecto.php

// --- Integración de PHPMailer ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Ruta corregida según la estructura de tu servidor.
// Desde /calidad/defectos/, subimos dos niveles (../../) hasta public_html,
// y luego bajamos a /Mailer/Phpmailer/
require __DIR__ . '/../../Mailer/Phpmailer/Exception.php';
require __DIR__ . '/../../Mailer/Phpmailer/PHPMailer.php';
require __DIR__ . '/../../Mailer/Phpmailer/SMTP.php';
// --- Fin de la Integración ---

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

        // --- Lógica de Notificación por Correo con PHPMailer ---
        $codigoDefecto = $input['codigoDefecto'];

        // Contar cuántas veces se ha repetido el mismo defecto hoy
        $countStmt = $conex->prepare("SELECT COUNT(IdDefecto) as count FROM Defectos WHERE CodigoDefecto = ? AND DATE(Fecha) = CURDATE()");
        $countStmt->bind_param("i", $codigoDefecto);
        $countStmt->execute();
        $result = $countStmt->get_result();
        $countRow = $result->fetch_assoc();

        // Si el defecto se ha registrado más de 3 veces, envía el correo.
        if ($countRow && $countRow['count'] > 3) {

            $mail = new PHPMailer(true);

            try {
                //Configuración del servidor SMTP
                $mail->isSMTP();
                $mail->Host = 'smtp.hostinger.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'kaizen.system@grammermx.com';
                $mail->Password = 'Grammer2024.';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port = 465;

                //Destinatarios
                $mail->setFrom('kaizen.system@grammermx.com', 'Sistema de Calidad Grammer');
                $mail->addAddress('hadbet.altamirano@grammer.com', 'Hadbet Altamirano');

                //Contenido del correo
                $mail->isHTML(true);
                $mail->Subject = utf8_decode("Alerta de Defecto Recurrente: " . $codigoDefecto);
                $mail->Body    = "
                <html>
                <head><title>Alerta de Calidad</title></head>
                <body style='font-family: sans-serif; color: #333;'>
                <h2 style='color: #003366;'>Alerta de Defecto Recurrente</h2>
                <p>Hola,</p>
                <p>Se ha detectado una recurrencia en un defecto de producción. Por favor, tomar las acciones correspondientes.</p>
                <ul style='list-style-type: none; padding: 0;'>
                    <li style='margin-bottom: 10px;'><strong>Código de Defecto:</strong> {$codigoDefecto}</li>
                    <li style='margin-bottom: 10px;'><strong>Número de Parte:</strong> {$input['numeroParte']}</li>
                    <li style='margin-bottom: 10px;'><strong>Estación:</strong> {$input['estacion']}</li>
                    <li style='margin-bottom: 10px;'><strong>Fecha:</strong> " . date('Y-m-d') . "</li>
                    <li style='margin-bottom: 10px;'><strong>Total de reportes hoy:</strong> {$countRow['count']}</li>
                </ul>
                <hr>
                <p style='font-size: 0.9em; color: #777;'>Este es un correo generado automáticamente por el Sistema de Registro de Defectos.</p>
                </body>
                </html>";

                $mail->send();
            } catch (Exception $e) {
                // No se detiene el script si el correo falla
            }
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

