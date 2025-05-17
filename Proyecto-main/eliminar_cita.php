<?php
session_start();
include 'db.php';

ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método no permitido", 405);
    }

    if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo'] != 'Doctor') {
        throw new Exception("Acceso no autorizado", 401);
    }

    $input = file_get_contents('php://input');
    parse_str($input, $data);
    
    if (!isset($data['id_cita'])) {
        throw new Exception("ID de cita no proporcionado", 400);
    }

    $id_cita = filter_var($data['id_cita'], FILTER_VALIDATE_INT);
    if ($id_cita === false || $id_cita <= 0) {
        throw new Exception("ID de cita inválido", 400);
    }

    $id_doctor = $_SESSION['id_usuario'];

    $stmt = $conexion->prepare("SELECT id_cita FROM Citas WHERE id_cita = :id AND id_doctor = :doctor");
    $stmt->bindParam(':id', $id_cita, PDO::PARAM_INT);
    $stmt->bindParam(':doctor', $id_doctor, PDO::PARAM_INT);
    $stmt->execute();

    if (!$stmt->fetch()) {
        throw new Exception("No tienes permiso para eliminar esta cita", 403);
    }

    $stmt = $conexion->prepare("DELETE FROM Citas WHERE id_cita = :id");
    $stmt->bindParam(':id', $id_cita, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Cita eliminada correctamente',
            'id_cita' => $id_cita
        ]);
    } else {
        throw new Exception("Error al eliminar la cita", 500);
    }

} catch (PDOException $e) {
    error_log("PDO Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error de base de datos'
    ]);
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    http_response_code($e->getCode() ?: 400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>