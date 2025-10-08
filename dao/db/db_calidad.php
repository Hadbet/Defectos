<?php
// db/db_calidad.php

// Clase para la conexión a la base de datos de Calidad.
// Utiliza los datos de conexión que proporcionaste.
class LocalConector {
    private $host = "127.0.0.1:3306";
    private $usuario = "u909553968_calidadUser";
    private $clave = "Grammer2025";
    private $db = "u909553968_Calidad";
    public $conexion;

    // Método para establecer y devolver la conexión a la base de datos.
    public function conectar() {
        $this->conexion = mysqli_connect($this->host, $this->usuario, $this->clave, $this->db);
        if ($this->conexion->connect_error) {
            die("Conexión fallida: " . $this->conexion->connect_error);
        }
        // Asegura que la conexión maneje caracteres UTF-8
        $this->conexion->set_charset("utf8");
        return $this->conexion;
    }
}
?>
