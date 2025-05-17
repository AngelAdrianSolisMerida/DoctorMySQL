<?php
$host = 'localhost'; // Cambiar según tu configuración
$usuario = 'root';   // Cambiar según tu configuración
$clave = '';         // Cambiar según tu configuración
$base_de_datos = 'sistema_medico';

try {
    $conexion = new PDO("mysql:host=$host;dbname=$base_de_datos", $usuario, $clave);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conexion->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $conexion->exec("SET NAMES 'utf8'");
} catch (PDOException $e) {
    error_log('['.date('Y-m-d H:i:s').'] Error de conexión: ' . $e->getMessage());
    die(json_encode([
        'success' => false,
        'message' => 'Error de conexión a la base de datos',
        'details' => 'Verifique los logs para más información'
    ]));
}
?>