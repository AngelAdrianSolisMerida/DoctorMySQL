<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $id_horario = $_GET['id'];
    $sql = "DELETE FROM HorarioDoctor WHERE id_horario = ?";
    $stmt = $conexion->prepare($sql);
    
    if ($stmt->execute([$id_horario])) {
        echo json_encode(['status' => 'success', 'message' => 'Horario eliminado correctamente.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al eliminar el horario.']);
    }
}
?>