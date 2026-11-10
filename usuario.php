<?php
require_once 'Database.php';

class Usuario {
    private $conn;
    private $table = "usuario";

    public $id_usuario;
    public $id_rol;
    public $id_departamento;
    public $usuario;
    public $contrasena;
    public $nombre_completo;
    public $cargo;
    public $estado;
    public $correo_institucional;
    public $nombre_rol;
    public $nombre_departamento;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function login($usuario, $contrasena) {
        $query = "SELECT u.*, r.nombre_rol, d.nombre_departamento 
                  FROM " . $this->table . " u 
                  INNER JOIN rol r ON u.id_rol = r.id_rol 
                  INNER JOIN departamento d ON u.id_departamento = d.id_departamento 
                  WHERE u.usuario = ? LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $usuario);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch();
            if (password_verify($contrasena, $row['contrasena'])) {
                $this->estado = $row['estado'];
                if ($row['estado'] !== 'ACTIVO') return false;
                $this->id_usuario = $row['id_usuario'];
                $this->id_rol = $row['id_rol'];
                $this->nombre_rol = $row['nombre_rol'];
                $this->nombre_departamento = $row['nombre_departamento'];
                $this->usuario = $row['usuario'];
                $this->nombre_completo = $row['nombre_completo'];
                $this->cargo = $row['cargo'];
                return true;
            }
        }
        return false;
    }

    public function registrarAcceso($id_usuario) {
        $query = "INSERT INTO acceso (id_usuario, fecha_hora) VALUES (?, NOW())";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id_usuario);
        return $stmt->execute();
    }

    public function getAll() {
        $query = "SELECT u.*, r.nombre_rol, d.nombre_departamento 
                  FROM " . $this->table . " u 
                  INNER JOIN rol r ON u.id_rol = r.id_rol 
                  INNER JOIN departamento d ON u.id_departamento = d.id_departamento 
                  ORDER BY u.nombre_completo";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function existeCorreoInstitucional($correo) {
        $stmt = $this->conn->prepare("SELECT id_usuario FROM {$this->table} WHERE correo_institucional = ? LIMIT 1");
        $stmt->execute([$correo]);
        return (bool)$stmt->fetchColumn();
    }

    public function crearSolicitudRegistro($id_departamento, $usuario, $contrasena, $nombre_completo, $cargo, $correo) {
        $rol = $this->conn->prepare("SELECT id_rol FROM rol WHERE nombre_rol = 'Solicitante' LIMIT 1");
        $rol->execute();
        $id_rol = $rol->fetchColumn();
        if (!$id_rol) return false;

        $stmt = $this->conn->prepare("INSERT INTO {$this->table}
            (id_rol, id_departamento, usuario, contrasena, nombre_completo, cargo, estado, correo_institucional)
            VALUES (?, ?, ?, ?, ?, ?, 'PENDIENTE', ?)");
        return $stmt->execute([$id_rol, $id_departamento, $usuario, $contrasena, $nombre_completo, $cargo, $correo]);
    }

    public function contarPendientes() {
        $stmt = $this->conn->query("SELECT COUNT(*) FROM {$this->table} u
            INNER JOIN rol r ON u.id_rol = r.id_rol
            WHERE u.estado = 'PENDIENTE' AND r.nombre_rol = 'Solicitante'");
        return (int)$stmt->fetchColumn();
    }

    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                  (id_rol, id_departamento, usuario, contrasena, nombre_completo, cargo, estado) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_rol);
        $stmt->bindParam(2, $this->id_departamento);
        $stmt->bindParam(3, $this->usuario);
        $stmt->bindParam(4, $this->contrasena);
        $stmt->bindParam(5, $this->nombre_completo);
        $stmt->bindParam(6, $this->cargo);
        $stmt->bindParam(7, $this->estado);
        return $stmt->execute();
    }

    public function existeUsuario($usuario) {
        $query = "SELECT id_usuario FROM " . $this->table . " WHERE usuario = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $usuario);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function delete($id) {
        // Primero borrar los registros de acceso relacionados (historial de sesiones)
        $query_acceso = "DELETE FROM acceso WHERE id_usuario = ?";
        $stmt_acceso = $this->conn->prepare($query_acceso);
        $stmt_acceso->bindParam(1, $id);
        $stmt_acceso->execute();

        // Luego borrar el usuario
        $query = "DELETE FROM " . $this->table . " WHERE id_usuario = ? AND id_usuario != ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->bindParam(2, $this->id_usuario);
        return $stmt->execute();
    }

    public function esAdmin() {
        return $this->nombre_rol === 'Administrador';
    }

    public function esSolicitante() {
        return $this->nombre_rol === 'Solicitante';
    }
}
