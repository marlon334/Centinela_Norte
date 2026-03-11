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
$city = strip_tags(trim($_POST['city'] ?? 'No especificada'));
$message_content = strip_tags(trim($_POST['message'] ?? 'Proceso de Reclutamiento'));

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
if (empty($name) || empty($email)) {
    $response['message'] = 'Por favor, complete todos los campos requeridos.';
    echo json_encode($response);
    exit;
}

// --- FILE UPLOAD LOGIC ---
$upload_dir = 'uploads_reclutamiento/'; // Carpetas se guardarán aquí
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Sanitize name for folder name
$folder_name = preg_replace('/[^a-zA-Z0-0\s]/', '', $name);
$folder_name = str_replace(' ', '_', $folder_name) . '_' . date('Ymd_His');
$target_path = $upload_dir . $folder_name . '/';

$uploaded_files_count = 0;
$total_uploaded_size = 0;
$max_size = 10 * 1024 * 1024; // 10MB

if (isset($_FILES['documents'])) {
    // Check total size first
    foreach ($_FILES['documents']['size'] as $size) {
        $total_uploaded_size += $size;
    }

    if ($total_uploaded_size > $max_size) {
        $response['message'] = 'El tamaño total de los archivos excede los 10MB.';
        echo json_encode($response);
        exit;
    }

    if (!is_dir($target_path)) {
        mkdir($target_path, 0755, true);
    }

    foreach ($_FILES['documents']['tmp_name'] as $key => $tmp_name) {
        // En PHP 8.1+ podemos obtener el full_path original si se desea, 
        // pero para mantener simplicidad y cumplir el requisito de "carpeta con nombre del usuario", 
        // guardamos todos los archivos en el nivel principal del usuario.
        $file_name = basename($_FILES['documents']['name'][$key]);
        $file_name = preg_replace('/[^a-zA-Z0-0\._-]/', '', $file_name);
        
        if (move_uploaded_file($tmp_name, $target_path . $file_name)) {
            $uploaded_files_count++;
        }
    }
}

if ($uploaded_files_count === 0 && isset($_FILES['documents'])) {
    $response['message'] = 'No se pudieron procesar los archivos. Verifique el formato.';
    echo json_encode($response);
    exit;
}
// --- END FILE UPLOAD LOGIC ---

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'Formato de correo electrónico no válido.';
    echo json_encode($response);
    exit;
}

// Prepare Email
$email_subject = $subject_prefix . $subject_type;
$email_body = "Se ha recibido una nueva solicitud de RECLUTAMIENTO.\n\n";
$email_body .= "--------------------------------------------------\n";
$email_body .= "Nombre Completo: $name\n";
$email_body .= "Email: $email\n";
$email_body .= "Ciudad: $city\n";
$email_body .= "Postulación para: $subject_type\n";
$email_body .= "--------------------------------------------------\n";
$email_body .= "DOCUMENTACIÓN:\n";
$email_body .= "Carpeta de archivos: $folder_name\n";
$email_body .= "Archivos subidos: $uploaded_files_count\n";
$email_body .= "Ubicación en servidor: $target_path\n";
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
    $response['message'] = 'Solicitud y documentos recibidos correctamente.';
} else {
    $response['message'] = 'Error al enviar la notificación. Los archivos se guardaron pero el correo falló.';
}

header('Content-Type: application/json');
echo json_encode($response);
exit;
