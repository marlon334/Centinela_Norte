<?php
/**
 * Centinela del Norte - Contact Form Handler
 * Verifies reCAPTCHA and sends email notification.
 */

// Allow only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit('Método no permitido');
}

// Response structure
$response = [
    'success' => false,
    'message' => ''
];

// Configuration
$secret_key = '6Lcd_oYsAAAAAGKfDFZLHZgGHdVvQp14BfBjapKY'; // Updated Secret Key
$to_email = 'soporte@centineladelnorte.com.ec';
$subject_prefix = 'Nuevo mensaje desde contacto: ';

// Input data
$captcha_response = $_POST['g-recaptcha-response'] ?? '';
$name = strip_tags(trim($_POST['name'] ?? ''));
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$subject_type = strip_tags(trim($_POST['subject'] ?? 'Consulta General'));
$message_content = strip_tags(trim($_POST['message'] ?? ''));

// Validate Captcha
if (empty($captcha_response)) {
    $response['message'] = 'Por favor, complete el CAPTCHA.';
    echo json_encode($response);
    exit;
}

// Verify with Google using cURL (as in original snippet)
$url = 'https://www.google.com/recaptcha/api/siteverify';
$data = [
    'secret'   => $secret_key,
    'response' => $captcha_response,
    'remoteip' => $_SERVER['REMOTE_ADDR']
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$verify_result = curl_exec($ch);

if (curl_errno($ch)) {
    $response['message'] = 'Error de conexión con el servicio de verificación.';
    echo json_encode($response);
    curl_close($ch);
    exit;
}
curl_close($ch);

$verify_json = json_decode($verify_result);

if (!$verify_json->success) {
    $response['message'] = 'Fallo en la verificación del CAPTCHA. Intente de nuevo.';
    echo json_encode($response);
    exit;
}

// Basic validation for other fields
if (empty($name) || empty($email) || empty($message_content)) {
    $response['message'] = 'Por favor, complete todos los campos requeridos.';
    echo json_encode($response);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'Formato de correo electrónico no válido.';
    echo json_encode($response);
    exit;
}

// Prepare Email
$email_subject = $subject_prefix . $subject_type;
$email_body = "Se ha recibido un nuevo mensaje desde el formulario de contacto táctico de Centinela del Norte.\n\n";
$email_body .= "--------------------------------------------------\n";
$email_body .= "Nombre: $name\n";
$email_body .= "Email: $email\n";
$email_body .= "Requerimiento: $subject_type\n";
$email_body .= "--------------------------------------------------\n\n";
$email_body .= "Mensaje:\n$message_content\n\n";
$email_body .= "--------------------------------------------------\n";
$email_body .= "IP del Remitente: " . $_SERVER['REMOTE_ADDR'] . "\n";
$email_body .= "Fecha: " . date('Y-m-d H:i:s') . "\n";

// Headers
$headers = "From: no-reply@centineladelnorte.com.ec\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

// Send Email
if (mail($to_email, $email_subject, $email_body, $headers)) {
    $response['success'] = true;
    $response['message'] = 'Mensaje enviado correctamente.';
} else {
    $response['message'] = 'Error al enviar el correo. Por favor, intente más tarde o contáctenos vía WhatsApp.';
}

header('Content-Type: application/json');
echo json_encode($response);
exit;
