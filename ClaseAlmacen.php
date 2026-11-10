<?php
require_once 'Database.php';

class ClaseAlmacen {
    private $conn;
    private $table = "material";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll() {
        $stmt = $this->conn->query("SELECT * FROM {$this->table} WHERE estado = 'ACTIVO' ORDER BY descripcion");
        return $stmt;
    }

    public function getInventarioResumen() {
        return $this->conn->query("SELECT id_material, codigo, descripcion, unidad_medida, existencia, stock_minimo,
            CASE WHEN existencia <= 0 THEN 'AGOTADO'
                 WHEN existencia <= stock_minimo THEN 'BAJO'
                 ELSE 'DISPONIBLE' END AS nivel_stock
            FROM {$this->table} WHERE estado = 'ACTIVO' ORDER BY
            CASE WHEN existencia <= 0 THEN 0 WHEN existencia <= stock_minimo THEN 1 ELSE 2 END,
            descripcion");
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE id_material = ? LIMIT 1");
        $stmt->bindParam(1, $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function create($descripcion, $unidad, $existencia, $minimo, $estado) {
        $this->conn->beginTransaction();
        try {
            // El valor temporal permite insertar y obtener el ID autoincremental de la BD.
            $codigoTemporal = 'TMP' . strtoupper(bin2hex(random_bytes(6)));
            $stmt = $this->conn->prepare("INSERT INTO {$this->table} (codigo, descripcion, unidad_medida, existencia, stock_minimo, estado) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$codigoTemporal, $descripcion, $unidad, $existencia, $minimo, $estado]);

            $id = (int)$this->conn->lastInsertId();
            $codigo = 'MAT' . date('ymd') . '-' . $id;
            $update = $this->conn->prepare("UPDATE {$this->table} SET codigo = ? WHERE id_material = ?");
            $update->execute([$codigo, $id]);
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) $this->conn->rollBack();
            throw $e;
        }
    }

    public function update($id, $descripcion, $unidad, $existencia, $minimo, $estado) {
        $stmt = $this->conn->prepare("UPDATE {$this->table} SET descripcion=?, unidad_medida=?, existencia=?, stock_minimo=?, estado=? WHERE id_material=?");
        return $stmt->execute([$descripcion, $unidad, $existencia, $minimo, $estado, $id]);
    }

    public function delete($id) {
        $check = $this->conn->prepare("SELECT COUNT(*) FROM detalle_solicitud WHERE id_material = ?");
        $check->execute([$id]);
        $tieneHistorial = (int)$check->fetchColumn() > 0;

        if ($tieneHistorial) {
            $stmt = $this->conn->prepare("UPDATE {$this->table} SET estado = 'INACTIVO' WHERE id_material = ?");
            $stmt->execute([$id]);
            return $stmt->rowCount() > 0 ? 'inactivated' : 'not_found';
        }

        try {
            $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE id_material = ?");
            $stmt->execute([$id]);
            return $stmt->rowCount() > 0 ? 'deleted' : 'not_found';
        } catch (PDOException $e) {
            // Si otra requisición lo referencia entre la consulta y el borrado, se conserva como inactivo.
            if (($e->errorInfo[1] ?? null) == 1451) {
                $stmt = $this->conn->prepare("UPDATE {$this->table} SET estado = 'INACTIVO' WHERE id_material = ?");
                $stmt->execute([$id]);
                return $stmt->rowCount() > 0 ? 'inactivated' : 'not_found';
            }
            throw $e;
        }
    }

    public function stockBajo() {
        $stmt = $this->conn->query("SELECT * FROM {$this->table} WHERE existencia <= stock_minimo AND estado = 'ACTIVO'");
        return $stmt;
    }
}
