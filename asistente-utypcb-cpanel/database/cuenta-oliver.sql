-- Cuenta inicial del ingeniero Oliver para una instalación nueva.
-- Importar solo si NO puedes usar la terminal de cPanel.
-- Si la cuenta ya existe, INSERT IGNORE no la modifica.
-- Cambiar la contraseña en el primer acceso desde Mi cuenta.

INSERT IGNORE INTO asistente_admin_users
    (usuario, password_hash, nombre, rol, activo)
VALUES
    ('oliver', '$2y$12$8udLS.5b0cvhC0E03iGFE.Hb6uJnEem3hWWdieQZslNbxKHNxKucC', 'Ing. Oliver', 'superadmin', 1);
