<?php
// Muestra todos los errores de PHP (útil para depurar, se debe quitar en producción)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- Integración de PHPMailer ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Ruta corregida: Desde /dao/, subimos 3 niveles (../../../) para llegar a public_html
require __DIR__ . '/../../../Mailer/Phpmailer/Exception.php';
require __DIR__ . '/../../../Mailer/Phpmailer/PHPMailer.php';
require __DIR__ . '/../../../Mailer/Phpmailer/SMTP.php';
// --- Fin de la Integración ---

header('Content-Type: application/json');
// Ruta corregida: Desde /dao/, subimos 1 nivel (../) para encontrar la carpeta /db/
include_once('db/db_calidad.php');

function enviarNotificacionTelegram($codigoDefecto, $numeroParte, $estacion, $totalReportes) {
    // --- CONFIGURACIÓN DE TU BOT ---
    $botToken = "8390315231:AAGm87Y0iAdVw6dhSTJ3jHIuOchFQA4z8rA"; // Token del ejemplo
    $chatId = "-4789336900";       // Chat ID del ejemplo

    // --- CONSTRUCCIÓN DEL MENSAJE CON MARKDOWN ---
    // Usamos asteriscos para negritas y ` para texto monoespaciado
    $mensaje = "*🚨 ¡ALERTA DE DEFECTO RECURRENTE! 🚨*\n\n";
    $mensaje .= "Se ha detectado una alta recurrencia en un defecto de producción. Se requiere atención inmediata.\n\n";
    $mensaje .= "*Detalles del Defecto:*\n";
    $mensaje .= "---------------------------------------\n";
    $mensaje .= "*Código:* `" . htmlspecialchars($codigoDefecto) . "`\n";
    $mensaje .= "*No. Parte:* " . htmlspecialchars($numeroParte) . "\n";
    $mensaje .= "*Estación:* " . htmlspecialchars($estacion) . "\n";
    $mensaje .= "*Total de reportes hoy:* *" . htmlspecialchars($totalReportes) . "*\n";

    $mensajeCodificado = urlencode($mensaje);

    // --- ENVÍO DE LA NOTIFICACIÓN ---
    // Cambiamos 'parse_mode' a 'Markdown' para que interprete los asteriscos
    $url = "https://api.telegram.org/bot{$botToken}/sendMessage?chat_id={$chatId}&text={$mensajeCodificado}&parse_mode=Markdown";

    @file_get_contents($url);
}

$response = ['success' => false, 'message' => ''];
$input = json_decode(file_get_contents('php://input'), true);

if (empty($input) || !isset($input['nomina'], $input['numeroParte'], $input['estacion']) || empty($input['codigoDefecto'])) {
    $response['message'] = 'Datos incompletos para el registro. Asegúrate de seleccionar un defecto.';
    echo json_encode($response);
    exit;
}

try {
    $con = new LocalConector();
    $conex = $con->conectar();
    if (!$conex) {
        throw new Exception("Error al conectar a la base de datos: " . mysqli_connect_error());
    }

    $Object = new DateTime();
    $Object->setTimezone(new DateTimeZone('America/Denver')); // Considera usar 'America/Mexico_City' si aplica
    $DateAndTime = $Object->format("Y-m-d H:i:s");
    $format = $Object->format("Y-m-d");

    $conex->begin_transaction();

    // Insertar el nuevo defecto
    $stmt = $conex->prepare("INSERT INTO Defectos (Linea, Nomina, NumeroParte, Estacion, CodigoDefecto, Fecha, Estado) VALUES (?, ?, ?, ?, ?, ?, 0)");
    $stmt->bind_param("ssssi", $input['linea'], $input['nomina'], $input['numeroParte'], $input['estacion'], $input['codigoDefecto'],$DateAndTime);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $response['success'] = true;
        $response['message'] = 'Defecto registrado exitosamente.';

        // --- Lógica de Notificación por Correo con PHPMailer ---
        $codigoDefecto = $input['codigoDefecto'];

        // Contar cuántas veces se ha repetido el mismo defecto hoy
        $countStmt = $conex->prepare("SELECT COUNT(IdDefecto) as count FROM Defectos WHERE CodigoDefecto = ? AND DATE(Fecha) = ?");
        $countStmt->bind_param("is", $codigoDefecto,$format);
        $countStmt->execute();
        $result = $countStmt->get_result();
        $countRow = $result->fetch_assoc();
        $countStmt->close();

        // Si el defecto se ha registrado 3 o más veces, envía el correo.
        if ($countRow && $countRow['count'] >= 3) {

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
                $mail->CharSet = 'UTF-8'; // Corrección para acentos y caracteres especiales
                $mail->Subject = "Alerta de Defecto Recurrente: " . $codigoDefecto;
                $mail->Body    = "
                <html>
                <head>
                <title>Alerta de Calidad</title>
                <style>
                    @keyframes blink {
                        0% { opacity: 1; }
                        50% { opacity: 0.4; }
                        100% { opacity: 1; }
                    }
                    .blinking-alert {
                        animation: blink 1.5s linear infinite;
                    }
                </style>
                </head>
                <body style=\"font-family: 'Inter', sans-serif; background-color: #f1f5f9; padding: 20px; margin: 0;\">
                    <div style=\"max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0;\">
                        <div style=\"background-color: #D32F2F; color: white; padding: 20px; text-align: center;\" class=\"blinking-alert\">
                            <h1 style=\"margin: 0; font-size: 28px; font-weight: bold;\">¡ALERTA!</h1>
                        </div>
                        <div style=\"padding: 30px;\">
                            <h2 style=\"color: #1e293b; font-size: 22px; margin-top: 0;\">Defecto de Producción Recurrente</h2>
                            <p style=\"color: #475569; line-height: 1.6;\">
                                Hola,
                            </p>
                            <p style=\"color: #475569; line-height: 1.6;\">
                                Se ha detectado una alta recurrencia en un defecto de producción. Por favor, tomar las acciones correspondientes de inmediato.
                            </p>
                            <div style=\"background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-top: 20px;\">
                                <h3 style=\"color: #1e293b; margin-top: 0; border-bottom: 2px solid #cbd5e1; padding-bottom: 10px;\">Detalles del Defecto</h3>
                                <ul style=\"list-style-type: none; padding: 0; margin: 0;\">
                                    <li style=\"margin-bottom: 12px; color: #475569;\"><strong>Código de Defecto:</strong> <span style=\"font-weight: bold; color: #D32F2F; font-family: monospace;\">{$codigoDefecto}</span></li>
                                    <li style=\"margin-bottom: 12px; color: #475569;\"><strong>Número de Parte:</strong> {$input['numeroParte']}</li>
                                    <li style=\"margin-bottom: 12px; color: #475569;\"><strong>Estación:</strong> {$input['estacion']}</li>
                                    <li style=\"margin-bottom: 12px; color: #475569;\"><strong>Fecha:</strong> " . date('Y-m-d') . "</li>
                                    <li style=\"color: #475569;\"><strong>Total de reportes hoy:</strong> <span style=\"background-color: #D32F2F; color: white; padding: 3px 8px; border-radius: 5px; font-weight: bold;\">{$countRow['count']}</span></li>
                                </ul>
                            </div>
                        </div>
                        <div style=\"background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 20px; text-align: center;\">
                            <p style=\"font-size: 0.8em; color: #64748b; margin: 0;\">
                                Este es un correo generado automáticamente por el Sistema de Registro de Defectos de Calidad.
                            </p>
                            <p style=\"font-size: 0.8em; color: #64748b; margin: 5px 0 0;\">
                                Grammer Automotive &copy; " . date('Y') . "
                            </p>
                        </div>
                    </div>
                </body>
                </html>";

                $mail->send();

                //enviarNotificacionTelegram($codigoDefecto, $input['numeroParte'], $input['estacion'], $countRow['count']);

            } catch (Exception $e) {
                // Si el correo falla, no detenemos el proceso, pero podemos registrar el error si es necesario.
                $response['mail_error'] = "El defecto se registró, pero el correo no pudo ser enviado. Error: {$mail->ErrorInfo}";
            }
        }
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
    // Asegurarse de que $conex existe antes de usarlo
    if (isset($conex) && $conex->ping()) {
        $conex->rollback();
    }
}

echo json_encode($response);
?>

