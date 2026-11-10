-- ============================================
-- Base de datos: modulo_requisicion
-- Sistema: Módulo de Requisición de Inventario v1.0.0
-- Compatible con carpeta: C:\xampp\htdocs\modulo_requisiciones\
-- ============================================

CREATE DATABASE IF NOT EXISTS modulo_requisicion CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE modulo_requisicion;

-- -----------------------------------------------------
-- Tabla: rol
-- -----------------------------------------------------
CREATE TABLE rol (
    id_rol INT AUTO_INCREMENT PRIMARY KEY,
    nombre_rol VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Tabla: departamento
-- -----------------------------------------------------
CREATE TABLE departamento (
    id_departamento INT AUTO_INCREMENT PRIMARY KEY,
    nombre_departamento VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Tabla: usuario
-- -----------------------------------------------------
CREATE TABLE usuario (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    id_rol INT NOT NULL,
    id_departamento INT NOT NULL,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    contrasena VARCHAR(255) NOT NULL,
    nombre_completo VARCHAR(150) NOT NULL,
    cargo VARCHAR(100),
    correo_institucional VARCHAR(150),
    estado ENUM('ACTIVO','INACTIVO','PENDIENTE') DEFAULT 'PENDIENTE',
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_rol) REFERENCES rol(id_rol),
    FOREIGN KEY (id_departamento) REFERENCES departamento(id_departamento)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Tabla: material (almacén)
-- -----------------------------------------------------
CREATE TABLE material (
    id_material INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    descripcion VARCHAR(150) NOT NULL,
    unidad_medida VARCHAR(50) DEFAULT 'Pieza',
    existencia DECIMAL(10,2) DEFAULT 0,
    stock_minimo DECIMAL(10,2) DEFAULT 0,
    estado ENUM('ACTIVO','INACTIVO') DEFAULT 'ACTIVO',
    fecha_alta DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Tabla: solicitud (requisiciones)
-- -----------------------------------------------------
CREATE TABLE solicitud (
    id_solicitud INT AUTO_INCREMENT PRIMARY KEY,
    folio VARCHAR(30) NOT NULL UNIQUE,
    fecha_solicitud DATETIME DEFAULT CURRENT_TIMESTAMP,
    tipo_solicitud VARCHAR(50),
    id_depto_solicitante INT NOT NULL,
    id_depto_destino INT NOT NULL,
    id_usuario_solicita INT NOT NULL,
    id_usuario_autoriza INT NULL,
    fecha_autorizacion DATETIME NULL,
    id_usuario_recibe_material INT NULL,
    estado_solicitud ENUM('PENDIENTE','AUTORIZADA','ENTREGADA') DEFAULT 'PENDIENTE',
    FOREIGN KEY (id_depto_solicitante) REFERENCES departamento(id_departamento),
    FOREIGN KEY (id_depto_destino) REFERENCES departamento(id_departamento),
    FOREIGN KEY (id_usuario_solicita) REFERENCES usuario(id_usuario),
    FOREIGN KEY (id_usuario_autoriza) REFERENCES usuario(id_usuario),
    FOREIGN KEY (id_usuario_recibe_material) REFERENCES usuario(id_usuario)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Tabla: detalle_solicitud
-- -----------------------------------------------------
CREATE TABLE detalle_solicitud (
    id_detalle INT AUTO_INCREMENT PRIMARY KEY,
    id_solicitud INT NOT NULL,
    id_material INT NOT NULL,
    numero_partida INT,
    cantidad_solicitada DECIMAL(10,2) NOT NULL,
    cantidad_entregada DECIMAL(10,2) DEFAULT 0,
    justificacion TEXT,
    origen_suministro VARCHAR(100) DEFAULT 'ALMACEN / SERV. INTERNO',
    observacion TEXT,
    FOREIGN KEY (id_solicitud) REFERENCES solicitud(id_solicitud) ON DELETE CASCADE,
    FOREIGN KEY (id_material) REFERENCES material(id_material)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Tabla: acceso (historial de sesiones)
-- -----------------------------------------------------
CREATE TABLE acceso (
    id_acceso INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    fecha_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- Datos iniciales
-- ============================================

INSERT INTO rol (nombre_rol) VALUES ('Administrador'), ('Solicitante');

INSERT INTO departamento (nombre_departamento) VALUES 
('Sistemas'), 
('Almacén'), 
('Ventas'), 
('Contabilidad'), 
('Recursos Humanos');

-- Usuario administrador: guillermina / 123456 (estado ACTIVO)
-- Hash generado con password_hash('123456', PASSWORD_DEFAULT)
INSERT INTO usuario (id_rol, id_departamento, usuario, contrasena, nombre_completo, cargo, correo_institucional, estado) VALUES
(1, 1, 'guillermina', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.u.2jX9K.S', 'Ing. Guillermina', 'Administrador de Sistemas', 'guillermina@utrng.edu.mx', 'ACTIVO');

-- Usuario solicitante de ejemplo: yurismi / 123456 (estado ACTIVO)
INSERT INTO usuario (id_rol, id_departamento, usuario, contrasena, nombre_completo, cargo, correo_institucional, estado) VALUES
(2, 3, 'yurismi', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.u.2jX9K.S', 'Yurismi', 'Solicitante', 'yurismi@utrng.edu.mx', 'ACTIVO');

-- ============================================
-- Índices recomendados
-- ============================================
CREATE INDEX idx_solicitud_estado ON solicitud(estado_solicitud);
CREATE INDEX idx_solicitud_fecha ON solicitud(fecha_solicitud);
CREATE INDEX idx_detalle_solicitud ON detalle_solicitud(id_solicitud);
CREATE INDEX idx_acceso_usuario ON acceso(id_usuario);
CREATE INDEX idx_usuario_estado ON usuario(estado);
CREATE INDEX idx_material_codigo ON material(codigo);
