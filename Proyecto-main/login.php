<?php
session_start();
require 'db.php';

header('Content-Type: application/json');
$response = ["status" => "error", "message" => "", "redirect" => ""];

if (!isset($_POST['g-recaptcha-response'])) {
    $response["message"] = "Por favor, completa el reCAPTCHA.";
    echo json_encode($response);
    exit;
}

$recaptcha_secret = '6Lcycw0rAAAAAKP6UpXrOeSkwKYoFe0yfDti2tN8';
$recaptcha_response = $_POST['g-recaptcha-response'];
$ip = $_SERVER['REMOTE_ADDR'];

$url = 'https://www.google.com/recaptcha/api/siteverify';
$data = array(
    'secret' => $recaptcha_secret,
    'response' => $recaptcha_response,
    'remoteip' => $ip
);

$options = array(
    'http' => array(
        'method' => 'POST',
        'header' => 'Content-type: application/x-www-form-urlencoded',
        'content' => http_build_query($data)
    )
);

$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);
$response_data = json_decode($result);

if (!$response_data->success) {
    $response["message"] = "La verificación reCAPTCHA falló. Intenta nuevamente.";
    echo json_encode($response);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $contrasena = trim($_POST["contrasena"]);

    try {
        $sql = "SELECT id_usuario, nombre, contrasena, tipo FROM usuario WHERE email = :email";
        $stmt = $conexion->prepare($sql);
        $stmt->bindValue(':email', $email);
        $stmt->execute();
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            if (password_verify($contrasena, $usuario["contrasena"])) {
                $_SESSION["id_usuario"] = $usuario["id_usuario"];
                $_SESSION["nombre"] = $usuario["nombre"];
                $_SESSION["tipo"] = $usuario["tipo"];
                $_SESSION["email"] = $email;

                $response["status"] = "success";
                $response["message"] = "Inicio de sesión exitoso";

                $response["redirect"] = match($usuario["tipo"]) {
                    "Paciente" => "View_paciente.php",
                    "Doctor" => "View_doctor.php",
                    default => "index.html",
                };
            } else {
                $response["message"] = "Contraseña incorrecta";
            }
        } else {
            $response["message"] = "Usuario no encontrado. Verifica tu email";
        }
    } catch (PDOException $e) {
        error_log("Error MySQL en login: " . $e->getMessage());
        $response["message"] = "Error en el sistema. Por favor intenta más tarde.";
    }
} else {
    $response["message"] = "Método no permitido";
}

ob_clean();
echo json_encode($response);
exit;
?>