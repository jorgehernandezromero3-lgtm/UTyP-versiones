-- Migración para instalaciones que ya tenían la tabla de administradores.
-- Ejecutar una sola vez antes de desplegar la versión con gestión de administradores.

ALTER TABLE asistente_admin_users
    ADD COLUMN rol ENUM('superadmin', 'admin') NOT NULL DEFAULT 'admin' AFTER nombre;

UPDATE asistente_admin_users
SET rol = 'superadmin'
WHERE usuario = 'oliver';
