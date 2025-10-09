<?php
// Muestra todos los errores de PHP (útil para depurar, se debe quitar en producción)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- Integración de PHPMailer ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../../../Mailer/Phpmailer/Exception.php';
require __DIR__ . '/../../../Mailer/Phpmailer/PHPMailer.php';
require __DIR__ . '/../../../Mailer/Phpmailer/SMTP.php';
// --- Fin de la Integración ---

header('Content-Type: application/json');
include_once('db/db_calidad.php');

// ===================================================================
// === FUNCIÓN PARA ENVIAR NOTIFICACIÓN A TELEGRAM ===
// ===================================================================
function enviarNotificacionTelegram($codigoDefecto, $numeroParte, $estacion, $totalReportes) {
    // --- CONFIGURACIÓN DE TU BOT ---
    // Reemplaza estos valores con los de tu bot
    $botToken = "8390315231:AAGm87Y0iAdVw6dhSTJ3jHIuOchFQA4z8rA"; // El token que te dio BotFather
    $chatId = "-4789336900";       // El ID de tu grupo (empieza con -)

    // --- CONSTRUCCIÓN DEL MENSAJE ---
    // Usamos etiquetas HTML sencillas para darle formato al texto
    $mensaje = "<b>🚨 ¡ALERTA DE DEFECTO RECURRENTE! 🚨</b>\n\n";
    $mensaje .= "Se ha detectado una alta recurrencia en un defecto de producción. Se requiere atención inmediata.\n\n";
    $mensaje .= "<b>Detalles del Defecto:</b>\n";
    $mensaje .= "---------------------------------------\n";
    $mensaje .= "<b>Código:</b> " . htmlspecialchars($codigoDefecto) . "\n";
    $mensaje .= "<b>No. Parte:</b> " . htmlspecialchars($numeroParte) . "\n";
    $mensaje .= "<b>Estación:</b> " . htmlspecialchars($estacion) . "\n";
    $mensaje .= "<b>Total de reportes hoy:</b> " . htmlspecialchars($totalReportes) . "\n";

    // Codificamos el mensaje para que sea seguro en una URL
    $mensajeCodificado = urlencode($mensaje);

    // --- ENVÍO DE LA NOTIFICACIÓN ---
    $url = "https://api.telegram.org/bot{$botToken}/sendMessage?chat_id={$chatId}&text={$mensajeCodificado}&parse_mode=HTML";

    // Usamos file_get_contents para enviar la petición (simple y efectivo)
    // @ suprime errores si la API falla, para no romper el JSON de respuesta
    @file_get_contents($url);
}

// ===================================================================
// === LÓGICA PRINCIPAL DEL SCRIPT ===
// ===================================================================

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

    $conex->begin_transaction();

    $stmt = $conex->prepare("INSERT INTO Defectos (Nomina, NumeroParte, Estacion, CodigoDefecto, Fecha) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssi", $input['nomina'], $input['numeroParte'], $input['estacion'], $input['codigoDefecto']);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $response['success'] = true;
        $response['message'] = 'Defecto registrado exitosamente.';

        $codigoDefecto = $input['codigoDefecto'];

        $countStmt = $conex->prepare("SELECT COUNT(IdDefecto) as count FROM Defectos WHERE CodigoDefecto = ? AND DATE(Fecha) = CURDATE()");
        $countStmt->bind_param("i", $codigoDefecto);
        $countStmt->execute();
        $result = $countStmt->get_result();
        $countRow = $result->fetch_assoc();
        $countStmt->close();

        if ($countRow && $countRow['count'] >= 3) {

            // --- Llamada a las funciones de notificación ---

            // 1. Enviar Correo Electrónico (ya existente)
            // (Aquí iría el código de PHPMailer que ya tienes)

            // 2. Enviar Notificación por Telegram
            // Para deshabilitar el envío por Telegram, simplemente comenta la siguiente línea:
            enviarNotificacionTelegram($codigoDefecto, $input['numeroParte'], $input['estacion'], $countRow['count']);

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

