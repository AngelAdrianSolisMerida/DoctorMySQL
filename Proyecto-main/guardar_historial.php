<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    exit(json_encode(['status' => 'error', 'message' => 'Acceso no permitido']));
}

if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo'] != 'Doctor') {
    exit(json_encode(['status' => 'error', 'message' => 'No autorizado']));
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['id_paciente']) || empty($data['diagnostico']) || empty($data['tratamiento'])) {
        exit(json_encode(['status' => 'error', 'message' => 'Todos los campos son obligatorios']));
    }

    $stmtCheck = $conexion->prepare("SELECT 1 FROM Usuario WHERE id_usuario = ? AND tipo = 'Paciente'");
    $stmtCheck->execute([$data['id_paciente']]);
    if (!$stmtCheck->fetch()) {
        exit(json_encode(['status' => 'error', 'message' => 'El paciente no existe']));
    }

    $sql = "INSERT INTO HistorialMedico (id_paciente, id_doctor, diagnostico, tratamiento) 
            VALUES (?, ?, ?, ?)";
    
    $stmt = $conexion->prepare($sql);
    $success = $stmt->execute([
        $data['id_paciente'],
        $_SESSION['id_usuario'],
        $data['diagnostico'],
        $data['tratamiento']
    ]);

    if ($success) {
        echo json_encode(['status' => 'success', 'message' => 'Nota médica guardada']);
    } else {
        throw new Exception("Error al ejecutar la consulta");
    }

} catch (PDOException $e) {
    error_log("Error en guardar_historial: " . $e->getMessage());
    exit(json_encode([
        'status' => 'error', 
        'message' => 'Error de base de datos',
        'error_detail' => $e->getMessage()
    ]));
} catch (Exception $e) {
    exit(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
}
?>