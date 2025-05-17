<?php
session_start();
header('Content-Type: application/json');
include 'db.php';

if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo'] != 'Paciente') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$id_paciente = $_SESSION['id_usuario'];
$id_doctor = $_POST['id_doctor'] ?? null;

if (!$id_doctor) {
    echo json_encode(['success' => false, 'message' => 'Médico no especificado']);
    exit;
}

try {
    $sql_horarios = "SELECT dia, hora_inicio, hora_fin FROM HorarioDoctor WHERE id_doctor = :id_doctor";
    $stmt_horarios = $conexion->prepare($sql_horarios);
    $stmt_horarios->execute(['id_doctor' => $id_doctor]);
    $horarios = $stmt_horarios->fetchAll(PDO::FETCH_ASSOC);

    if (empty($horarios)) {
        echo json_encode(['success' => false, 'message' => 'El doctor no tiene horarios asignados.']);
        exit;
    }

    $citaEncontrada = false;
    $fechaActual = new DateTime();
    $fechaLimite = (clone $fechaActual)->modify('+14 days');

    while ($fechaActual <= $fechaLimite && !$citaEncontrada) {
        $diaSemana = $fechaActual->format('l');
        $diaSemanaTraducido = match ($diaSemana) {
            'Monday' => 'Lunes',
            'Tuesday' => 'Martes',
            'Wednesday' => 'Miércoles',
            'Thursday' => 'Jueves',
            'Friday' => 'Viernes',
            'Saturday' => 'Sábado',
            'Sunday' => 'Domingo',
        };

        foreach ($horarios as $horario) {
            if ($horario['dia'] != $diaSemanaTraducido) continue;

            $horaInicio = new DateTime($horario['hora_inicio']);
            $horaFin = new DateTime($horario['hora_fin']);

            while ($horaInicio < $horaFin) {
                $fechaHoraCita = new DateTime($fechaActual->format('Y-m-d') . ' ' . $horaInicio->format('H:i:s'));

                $stmt_cita = $conexion->prepare("SELECT COUNT(*) FROM Citas WHERE id_doctor = :id_doctor AND fecha_cita = :fecha_cita");
                $stmt_cita->execute([
                    'id_doctor' => $id_doctor,
                    'fecha_cita' => $fechaHoraCita->format('Y-m-d H:i:s')
                ]);

                if ($stmt_cita->fetchColumn() == 0) {
                    $stmt_insert = $conexion->prepare("INSERT INTO Citas (id_paciente, id_doctor, fecha_cita) VALUES (:id_paciente, :id_doctor, :fecha_cita)");
                    $stmt_insert->execute([
                        'id_paciente' => $id_paciente,
                        'id_doctor' => $id_doctor,
                        'fecha_cita' => $fechaHoraCita->format('Y-m-d H:i:s')
                    ]);

                    echo json_encode([
                        'success' => true,
                        'message' => 'Cita agendada',
                        'fecha_cita' => $fechaHoraCita->format('Y-m-d H:i:s')
                    ]);
                    $citaEncontrada = true;
                    break 2;
                }

                $horaInicio->modify('+30 minutes');
            }
        }

        $fechaActual->modify('+1 day');
    }

    if (!$citaEncontrada) {
        echo json_encode(['success' => false, 'message' => 'No hay citas disponibles en los próximos días.']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>