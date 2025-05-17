<?php
session_start();
include 'db.php';

// Verificar autenticación
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo'] != 'Doctor') {
    header("Location: login.php");
    exit();
}

$id_doctor = $_SESSION['id_usuario'];

// Obtener datos del doctor y sus citas
try {
    // Obtener nombre del doctor
    $sql_doctor = "SELECT nombre FROM Usuario WHERE id_usuario = :id_doctor";
    $stmt_doctor = $conexion->prepare($sql_doctor);
    $stmt_doctor->bindParam(':id_doctor', $id_doctor, PDO::PARAM_INT);
    $stmt_doctor->execute();
    $doctor = $stmt_doctor->fetch(PDO::FETCH_ASSOC);

    if (!$doctor) {
        throw new Exception("No se encontró información del doctor");
    }

    // Obtener las citas del doctor
    $sql_citas = "SELECT C.id_cita, U.nombre AS paciente, C.fecha_cita, C.estado 
                 FROM Citas C 
                 JOIN Usuario U ON C.id_paciente = U.id_usuario 
                 WHERE C.id_doctor = :id_doctor 
                 ORDER BY C.fecha_cita";
    
    $stmt_citas = $conexion->prepare($sql_citas);
    $stmt_citas->bindParam(':id_doctor', $id_doctor, PDO::PARAM_INT);
    $stmt_citas->execute();
    $citas = $stmt_citas->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error de base de datos: " . $e->getMessage());
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Citas - Portal del Doctor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .fade-out {
            animation: fadeOut 0.5s ease-out forwards;
        }
        @keyframes fadeOut {
            to { opacity: 0; height: 0; padding-top: 0; padding-bottom: 0; margin-bottom: 0; }
        }
    </style>
</head>

<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-md">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-2">
                <i class="fas fa-user-md text-blue-500 text-2xl"></i>
                <h1 class="text-xl font-bold text-gray-800">Dr. <?php echo htmlspecialchars($doctor['nombre']); ?></h1>
            </div>
            <nav class="hidden md:flex space-x-6">
                <a href="View_doctor.php" class="text-gray-600 hover:text-blue-500">Inicio</a>
                <a href="View_horario.php" class="text-gray-600 hover:text-blue-500">Agendar</a>
                <a href="View_citas.php" class="text-blue-500 font-medium">Citas</a>
                <a href="View_notas.php" class="text-gray-600 hover:text-blue-500">Notas</a>
                <form action="cerrar.php" method="post">
                    <button type="submit" class="bg-red-500 hover:bg-red-600 text-white font-medium py-2 px-4 rounded-lg transition duration-300 flex items-center space-x-2">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Cerrar Sesión</span>
                    </button>
                </form>
            </nav>
            <button class="md:hidden text-gray-600">
                <i class="fas fa-bars text-xl"></i>
            </button>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Mis Citas Médicas</h2>

            <!-- Appointments Table -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID Cita</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paciente</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha y Hora</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($citas)): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                        No tienes citas programadas.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($citas as $cita): ?>
                                    <tr id="row_<?= $cita['id_cita'] ?>">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= $cita['id_cita'] ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= $cita['paciente'] ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= date('d/m/Y H:i', strtotime($cita['fecha_cita'])) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <select id="estado_<?= $cita['id_cita'] ?>" 
                                                    onchange="cambiarEstado(<?= $cita['id_cita'] ?>)" 
                                                    class="px-2 py-1 border rounded-md focus:ring-blue-500"
                                                    data-old-value="<?= $cita['estado'] ?>">
                                                <option value="Pendiente" <?= $cita['estado'] == 'Pendiente' ? 'selected' : '' ?>>Pendiente</option>
                                                <option value="Confirmada" <?= $cita['estado'] == 'Confirmada' ? 'selected' : '' ?>>Confirmada</option>
                                                <option value="Cancelada" <?= $cita['estado'] == 'Cancelada' ? 'selected' : '' ?>>Cancelada</option>
                                                <option value="Completada" <?= $cita['estado'] == 'Completada' ? 'selected' : '' ?>>Completada</option>
                                            </select>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <button onclick="confirmarEliminacion(<?= $cita['id_cita'] ?>)" 
                                                    class="px-3 py-1 bg-red-500 text-white rounded-md hover:bg-red-600 transition-colors">
                                                <i class="fas fa-trash-alt mr-1"></i> Eliminar
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal de Confirmación (centrado) -->
    <div id="confirmModal" class="fixed inset-0 flex items-center justify-center z-50 hidden">
        <div class="absolute inset-0 bg-black bg-opacity-50"></div>
        <div class="bg-white rounded-lg p-6 max-w-sm w-full mx-4 relative z-10">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                    <i class="fas fa-exclamation text-red-600"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mt-3">Confirmar eliminación</h3>
                <div class="mt-2">
                    <p class="text-sm text-gray-500" id="confirmMessage"></p>
                </div>
                <div class="mt-4 flex justify-center space-x-3">
                    <button onclick="document.getElementById('confirmModal').classList.add('hidden')" 
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                        Cancelar
                    </button>
                    <button onclick="procesarEliminacion()" 
                            class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600">
                        Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal (también centrado) -->
    <div id="successModal" class="fixed inset-0 flex items-center justify-center z-50 hidden">
        <div class="absolute inset-0 bg-black bg-opacity-50"></div>
        <div class="bg-white rounded-lg p-6 max-w-sm w-full mx-4 relative z-10">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                    <i class="fas fa-check text-green-600"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mt-3">¡Éxito!</h3>
                <div class="mt-2">
                    <p class="text-sm text-gray-500" id="successMessage"></p>
                </div>
                <div class="mt-4">
                    <button onclick="document.getElementById('successModal').classList.add('hidden')" 
                            class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                        Aceptar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Variable global para almacenar la cita a eliminar
    let citaAEliminar = null;

    // Función para confirmar eliminación
    function confirmarEliminacion(id_cita) {
        citaAEliminar = id_cita;
        document.getElementById('confirmMessage').textContent = `¿Estás seguro de eliminar la cita #${id_cita}?`;
        document.getElementById('confirmModal').classList.remove('hidden');
    }

    // Función para procesar eliminación
    async function procesarEliminacion() {
        if (!citaAEliminar) return;
        
        document.getElementById('confirmModal').classList.add('hidden');
        
        try {
            const response = await fetch('eliminar_cita.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id_cita=${citaAEliminar}`
            });

            // Verificar si la respuesta es JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                throw new Error(`El servidor respondió con formato incorrecto: ${text.substring(0, 50)}...`);
            }

            const data = await response.json();
            
            // Verificar estado HTTP
            if (!response.ok) {
                throw new Error(data.message || `Error ${response.status}`);
            }

            // Procesar respuesta
            if (data.status === 'success') {
                // Animación para eliminar fila
                const row = document.getElementById(`row_${citaAEliminar}`);
                if (row) {
                    row.classList.add('fade-out');
                    setTimeout(() => row.remove(), 500);
                }
                mostrarExito(data.message || 'Cita eliminada correctamente');
            } else {
                throw new Error(data.message || 'Error al eliminar');
            }
        } catch (error) {
            console.error('Error:', error);
            mostrarError(error.message);
        } finally {
            citaAEliminar = null;
        }
    }

    // Función para cambiar estado
    async function cambiarEstado(id_cita) {
        const select = document.getElementById(`estado_${id_cita}`);
        const nuevoEstado = select.value;
        const oldEstado = select.dataset.oldValue;
        
        try {
            const response = await fetch('actualizar_estado.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id_cita=${id_cita}&estado=${encodeURIComponent(nuevoEstado)}`
            });

            // Verificar si la respuesta es JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                throw new Error(`El servidor respondió con formato incorrecto: ${text.substring(0, 50)}...`);
            }

            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.message || `Error ${response.status}`);
            }

            if (data.status === 'success') {
                select.dataset.oldValue = nuevoEstado;
                mostrarExito('Estado actualizado correctamente');
            } else {
                throw new Error(data.message || 'Error al actualizar');
            }
        } catch (error) {
            console.error('Error:', error);
            select.value = oldEstado;
            mostrarError(error.message);
        }
    }

    // Función para mostrar mensaje de éxito
    function mostrarExito(mensaje) {
        document.getElementById('successMessage').textContent = mensaje;
        document.getElementById('successModal').classList.remove('hidden');
    }

    // Función para mostrar error
    function mostrarError(mensaje) {
        alert(`Error: ${mensaje}`);
    }
    </script>
</body>
</html>