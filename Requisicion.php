<?php
require_once 'Database.php';

class Requisicion {
    private $conn;
    private $table = "solicitud";
    private $table_detalle = "detalle_solicitud";

    public $id_solicitud;
    public $folio;
    public $fecha_solicitud;
    public $tipo_solicitud;
    public $id_depto_solicitante;
    public $id_depto_destino;
    public $id_usuario_solicita;
    public $id_usuario_autoriza;
    public $fecha_autorizacion;
    public $id_usuario_recibe_material;
    public $estado_solicitud;
    public $nombre_usuario;
    public $nombre_depto_solicitante;
    public $nombre_depto_destino;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function generarFolio() {
        $year = date('Y');
        $query = "SELECT COUNT(*) as total FROM " . $this->table . " WHERE folio LIKE 'REQ-{$year}-%'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch();
        $numero = $row['total'] + 1;
        return "REQ-{$year}-" . str_pad($numero, 4, '0', STR_PAD_LEFT);
    }

    public function create($detalles) {
        $this->conn->beginTransaction();
        try {
            $query = "INSERT INTO " . $this->table . " 
                      (folio, fecha_solicitud, tipo_solicitud, id_depto_solicitante, 
                       id_depto_destino, id_usuario_solicita, estado_solicitud) 
                      VALUES (?, ?, ?, ?, ?, ?, 'PENDIENTE')";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                $this->folio,
                $this->fecha_solicitud,
                $this->tipo_solicitud,
                $this->id_depto_solicitante,
                $this->id_depto_destino,
                $this->id_usuario_solicita
            ]);

            $this->id_solicitud = $this->conn->lastInsertId();

            $query_det = "INSERT INTO " . $this->table_detalle . " 
                          (id_solicitud, id_material, numero_partida, cantidad_solicitada, 
                           justificacion, origen_suministro, observacion) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt_det = $this->conn->prepare($query_det);

            foreach ($detalles as $detalle) {
                $stmt_det->execute([
                    $this->id_solicitud,
                    $detalle['id_material'],
                    $detalle['numero_partida'],
                    $detalle['cantidad_solicitada'],
                    $detalle['justificacion'] ?? '',
                    $detalle['origen_suministro'] ?? 'ALMACEN / SERV. INTERNO',
                    $detalle['observacion'] ?? ''
                ]);
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function getAll($id_usuario = null, $esAdmin = false) {
        if ($esAdmin) {
            $query = "SELECT s.*, u.nombre_completo as nombre_usuario, 
                             ds.nombre_departamento as nombre_depto_solicitante,
                             dd.nombre_departamento as nombre_depto_destino
                      FROM " . $this->table . " s
                      INNER JOIN usuario u ON s.id_usuario_solicita = u.id_usuario
                      INNER JOIN departamento ds ON s.id_depto_solicitante = ds.id_departamento
                      INNER JOIN departamento dd ON s.id_depto_destino = dd.id_departamento
                      " . ($id_usuario !== null ? "WHERE s.id_usuario_solicita = ? " : "") . "
                      ORDER BY s.fecha_solicitud DESC, s.id_solicitud DESC";
            $stmt = $this->conn->prepare($query);
            if ($id_usuario !== null) $stmt->execute([$id_usuario]);
            else $stmt->execute();
        } else {
            $query = "SELECT s.*, u.nombre_completo as nombre_usuario,
                             ds.nombre_departamento as nombre_depto_solicitante,
                             dd.nombre_departamento as nombre_depto_destino
                      FROM " . $this->table . " s
                      INNER JOIN usuario u ON s.id_usuario_solicita = u.id_usuario
                      INNER JOIN departamento ds ON s.id_depto_solicitante = ds.id_departamento
                      INNER JOIN departamento dd ON s.id_depto_destino = dd.id_departamento
                      WHERE s.id_usuario_solicita = ?
                      ORDER BY s.fecha_solicitud DESC, s.id_solicitud DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $id_usuario);
            $stmt->execute();
        }
        return $stmt;
    }

    public function getResumenPorSolicitante() {
        $query = "SELECT u.id_usuario, u.nombre_completo, u.usuario, COUNT(s.id_solicitud) AS total_requisiciones
                  FROM usuario u
                  INNER JOIN rol r ON u.id_rol = r.id_rol
                  LEFT JOIN solicitud s ON s.id_usuario_solicita = u.id_usuario
                  WHERE r.nombre_rol = 'Solicitante'
                  GROUP BY u.id_usuario, u.nombre_completo, u.usuario
                  ORDER BY u.nombre_completo";
        return $this->conn->query($query);
    }

    public function getById($id) {
        $query = "SELECT s.*, u.nombre_completo as nombre_usuario,
                         ds.nombre_departamento as nombre_depto_solicitante,
                         dd.nombre_departamento as nombre_depto_destino
                  FROM " . $this->table . " s
                  INNER JOIN usuario u ON s.id_usuario_solicita = u.id_usuario
                  INNER JOIN departamento ds ON s.id_depto_solicitante = ds.id_departamento
                  INNER JOIN departamento dd ON s.id_depto_destino = dd.id_departamento
                  WHERE s.id_solicitud = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function getDetalles($id_solicitud) {
        $query = "SELECT d.*, m.descripcion as material_nombre, m.codigo as material_codigo, m.unidad_medida
                  FROM " . $this->table_detalle . " d
                  INNER JOIN material m ON d.id_material = m.id_material
                  WHERE d.id_solicitud = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id_solicitud);
        $stmt->execute();
        return $stmt;
    }

    public function autorizar($id_solicitud, $id_usuario_autoriza) {
        $query = "UPDATE " . $this->table . " 
                  SET estado_solicitud = 'AUTORIZADA', 
                      id_usuario_autoriza = ?, 
                      fecha_autorizacion = NOW() 
                  WHERE id_solicitud = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id_usuario_autoriza);
        $stmt->bindParam(2, $id_solicitud);
        return $stmt->execute();
    }

    public function eliminar($id_solicitud) {
        $this->conn->beginTransaction();
        try {
            // detalle_solicitud usa ON DELETE CASCADE; el historial de materiales no se altera.
            $stmt = $this->conn->prepare("DELETE FROM " . $this->table . " WHERE id_solicitud = ?");
            $stmt->execute([$id_solicitud]);
            $eliminada = $stmt->rowCount() > 0;
            $this->conn->commit();
            return $eliminada;
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) $this->conn->rollBack();
            throw $e;
        }
    }

    public function contarPorEstado($estado, $id_usuario = null, $esAdmin = false) {
        if ($esAdmin) {
            $query = "SELECT COUNT(*) as total FROM " . $this->table . " WHERE estado_solicitud = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $estado);
        } else {
            $query = "SELECT COUNT(*) as total FROM " . $this->table . " 
                      WHERE estado_solicitud = ? AND id_usuario_solicita = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $estado);
            $stmt->bindParam(2, $id_usuario);
        }
        $stmt->execute();
        return $stmt->fetch()['total'];
    }
}
