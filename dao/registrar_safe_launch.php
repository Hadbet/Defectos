<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../../../Mailer/Phpmailer/Exception.php';
require __DIR__ . '/../../../Mailer/Phpmailer/PHPMailer.php';
require __DIR__ . '/../../../Mailer/Phpmailer/SMTP.php';

header('Content-Type: application/json');
include_once('db/db_calidad.php');

// ... (La función enviarNotificacionTelegram permanece igual) ...
/*
function enviarNotificacionTelegram($codigoDefecto, $numeroParte, $estacion, $totalReportes)
{
      // --- CONFIGURACIÓN DE TU BOT ---
  $botToken = "8390315231:AAGm87Y0iAdVw6dhSTJ3jHIuOchFQA4z8rA"; // Token del ejemplo
  $chatId = "-4789336900";   // Chat ID del ejemplo

  // --- CONSTRUCCIÓN DEL MENSAJE CON MARKDOWN ---
  $mensaje = "*🚨 ¡ALERTA DE DEFECTO RECURRENTE! 🚨*\n\n";
  $mensaje.= "Se ha detectado una alta recurrencia en un defecto de producción. Se requiere atención inmediata.\n\n";
  $mensaje.= "*Detalles del Defecto:*\n";
  $mensaje.= "---------------------------------------\n";
  $mensaje.= "*Código:* `".htmlspecialchars($codigoDefecto). "`\n";
  $mensaje.= "*No. Parte:* ".htmlspecialchars($numeroParte). "\n";
  $mensaje.= "*Estación:* ".htmlspecialchars($estacion). "\n";
  $mensaje.= "*Total de reportes hoy:* *".htmlspecialchars($totalReportes). "*\n";

  $mensajeCodificado = urlencode($mensaje);
  $url = "https://api.telegram.org/bot{$botToken}/sendMessage?chat_id={$chatId}&text={$mensajeCodificado}&parse_mode=Markdown";
  @file_get_contents($url);
}*/


$response = ['success' => false, 'message' => ''];
$input = json_decode(file_get_contents('php://input'), true);

if (empty($input) || !isset($input['nomina'], $input['numeroParte'], $input['estacion'], $input['serial']) || empty($input['codigoDefecto'])) {
    $response['message'] = 'Datos incompletos para el registro. Asegúrate de llenar todos los campos y seleccionar un defecto.';
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
    $Object->setTimezone(new DateTimeZone('America/Denver'));
    $DateAndTime = $Object->format("Y-m-d H:i:s");
    $format = $Object->format("Y-m-d");

    // --- NUEVA LÓGICA DE ESTADO ---
    $codigoDefecto = $input['codigoDefecto'];
    $estado = 0; // Estado 0 (Defecto) por defecto

    if ($codigoDefecto == '1') { // Retrabajo
        $estado = 1;
    } elseif ($codigoDefecto == '2') { // OK
        $estado = 2;
    } elseif ($codigoDefecto == '3') { // Scrapt
        $estado = 3;
    }

    $conex->begin_transaction();

    // Insertar el nuevo defecto con el estado calculado
    // El campo 'Estado' ahora se llama 'Estado' (antes era 0 fijo)
    // El campo 'Serial' ha sido añadido
    $stmt = $conex->prepare("INSERT INTO Safe_Launch (Linea, Nomina, NumeroParte, Estacion, CodigoDefecto, Fecha, Estado, Serial) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssissi", $input['linea'], $input['nomina'], $input['numeroParte'], $input['estacion'], $codigoDefecto, $DateAndTime, $estado, $input['serial']);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $nuevoId = $conex->insert_id; // Obtenemos el ID del registro insertado
        $response['success'] = true;
        $response['message'] = 'Registro guardado exitosamente.';

        // --- NUEVO: Registro en Bitácora ---
        // (Opcional, pero buena práctica registrar la creación)
        $stmt_bitacora = $conex->prepare("INSERT INTO BitacoraSafeLaunch (IdSafeLaunch, Fecha, Accion, CampoModificado, ValorAnterior, Linea, Serial) VALUES (?, ?, 'Creacion', 'N/A', 'N/A', ?, ?)");
        $stmt_bitacora->bind_param("isss", $nuevoId, $DateAndTime, $input['linea'], $input['serial']);
        $stmt_bitacora->execute();
        $stmt_bitacora->close();


        // --- Lógica de Notificación (sin cambios) ---
        // Contar cuántas veces se ha repetido el mismo defecto hoy
        $countStmt = $conex->prepare("SELECT COUNT(IdSafeLaunch) as count FROM Safe_Launch WHERE CodigoDefecto = ? AND DATE(Fecha) = ? AND Linea = ?");
        $countStmt->bind_param("iss", $codigoDefecto, $format,$input['linea']);
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
                $mail->CharSet = 'UTF-8';
                $mail->Subject = "Alerta de Defecto Recurrente: " . $codigoDefecto;
                // --- INICIO DE CORRECCIÓN ---
                // Se corrigió el HTML del correo. Se reemplazaron las comillas dobles escapadas (\")
                // por comillas simples (') para los atributos HTML (style, class).
                // Esto evita que el correo se muestre como texto plano.
                $mail->Body = "
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
                <body style='font-family: \"Inter\", sans-serif; background-color: #f1f5f9; padding: 20px; margin: 0;'>
                 <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0;'>
                  <div style='background-color: #D32F2F; color: white; padding: 20px; text-align: center;' class='blinking-alert'>
                   <h1 style='margin: 0; font-size: 28px; font-weight: bold;'>¡ALERTA!</h1>
                  </div>
                  <div style='padding: 30px;'>
                   <h2 style='color: #1e293b; font-size: 22px; margin-top: 0;'>Defecto de Producción Recurrente</h2>
                   <p style='color: #475569; line-height: 1.6;'>
                    Hola,
                   </p>
                   <p style='color: #475569; line-height: 1.6;'>
                    Se ha detectado una alta recurrencia en un defecto de producción. Por favor, tomar las acciones correspondientes de inmediato.
                   </p>
                   <div style='background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-top: 20px;'>
                    <h3 style='color: #1e293b; margin-top: 0; border-bottom: 2px solid #cbd5e1; padding-bottom: 10px;'>Detalles del Defecto</h3>
                    <ul style='list-style-type: none; padding: 0; margin: 0;'>
                     <li style='margin-bottom: 12px; color: #475569;'><strong>Código de Defecto:</strong> <span style='font-weight: bold; color: #D32F2F; font-family: monospace;'>{$codigoDefecto}</span></li>
                     <li style='margin-bottom: 12px; color: #475569;'><strong>Número de Parte:</strong> {$input['numeroParte']}</li>
                     <li style='margin-bottom: 12px; color: #475569;'><strong>Estación:</strong> {$input['estacion']}</li>
                     <li style='margin-bottom: 12px; color: #475569;'><strong>Serial:</strong> {$input['serial']}</li>
                     <li style='margin-bottom: 12px; color: #475569;'><strong>Fecha:</strong> ".date('Y-m-d')."</li>
                     <li style='color: #475569;'><strong>Total de reportes hoy:</strong> <span style='background-color: #D32F2F; color: white; padding: 3px 8px; border-radius: 5px; font-weight: bold;'>{$countRow['count']}</span></li>
                    </ul>
                   </div>
                  </div>
                  <div style='background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 20px; text-align: center;'>
                   <p style='font-size: 0.8em; color: #64748b; margin: 0;'>
                    Este es un correo generado automáticamente por el Sistema de Registro de Defectos de Calidad.
                   </p>
                   <p style='font-size: 0.8em; color: #64748b; margin: 5px 0 0;'>
                    Grammer Automotive &copy; " . date('Y') . "
                   </p>
                  </div>
                 </div>
                </body>
                </html> ";
                // --- FIN DE CORRECCIÓN ---

                $mail->send();
                // enviarNotificacionTelegram($codigoDefecto, $input['numeroParte'], $input['estacion'], $countRow['count']);
            } catch (Exception $e) {
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
    if (isset($conex) && $conex->ping()) {
        $conex->rollback();
    }
}

echo json_encode($response);
?>

