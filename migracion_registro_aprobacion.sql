-- Añade el correo institucional para el registro público de perfiles.
-- El valor permite NULL para conservar las cuentas existentes.
ALTER TABLE usuario
  ADD COLUMN correo_institucional VARCHAR(190) NULL AFTER cargo,
  ADD UNIQUE KEY uq_usuario_correo_institucional (correo_institucional);

-- La columna estado existente es VARCHAR(20), por lo que admite PENDIENTE,
-- ACTIVO e INACTIVO sin modificar el resto de la estructura.
