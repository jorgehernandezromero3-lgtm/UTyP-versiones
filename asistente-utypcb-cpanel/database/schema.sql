SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS asistente_areas (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug VARCHAR(60) NOT NULL,
    nombre VARCHAR(120) NOT NULL,
    correo VARCHAR(190) NULL,
    telefono VARCHAR(80) NULL,
    url_contacto VARCHAR(500) NULL,
    creada_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_areas_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS asistente_intents (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug VARCHAR(100) NOT NULL,
    categoria VARCHAR(60) NOT NULL,
    area_id INT UNSIGNED NOT NULL,
    pregunta VARCHAR(255) NOT NULL,
    respuesta MEDIUMTEXT NOT NULL,
    fuente_titulo VARCHAR(160) NOT NULL,
    fuente_url VARCHAR(500) NOT NULL,
    enlaces_json TEXT NULL,
    palabras_clave TEXT NULL,
    programa_slug VARCHAR(60) NULL,
    tema_slug VARCHAR(60) NULL,
    prioridad TINYINT NOT NULL DEFAULT 0,
    validada TINYINT(1) NOT NULL DEFAULT 0,
    validada_por VARCHAR(120) NULL,
    validada_en DATE NULL,
    activa TINYINT(1) NOT NULL DEFAULT 1,
    creada_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizada_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_intents_slug (slug),
    KEY idx_intents_activa (activa, categoria),
    KEY idx_intents_programa (programa_slug, tema_slug),
    CONSTRAINT fk_intents_area FOREIGN KEY (area_id) REFERENCES asistente_areas (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS asistente_intent_variants (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    intent_id INT UNSIGNED NOT NULL,
    texto VARCHAR(255) NOT NULL,
    texto_norm VARCHAR(255) NOT NULL,
    origen ENUM('base', 'admin') NOT NULL DEFAULT 'base',
    creada_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_variant_intent_norm (intent_id, texto_norm),
    KEY idx_variants_norm (texto_norm),
    CONSTRAINT fk_variants_intent FOREIGN KEY (intent_id) REFERENCES asistente_intents (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS asistente_unanswered (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    pregunta VARCHAR(300) NOT NULL,
    pregunta_norm VARCHAR(300) NOT NULL,
    veces INT UNSIGNED NOT NULL DEFAULT 1,
    primera_vez DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ultima_vez DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    revisada TINYINT(1) NOT NULL DEFAULT 0,
    revisada_en DATETIME NULL,
    intent_id INT UNSIGNED NULL,
    ip_hash CHAR(64) NULL,
    nota VARCHAR(500) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_unanswered_norm (pregunta_norm),
    KEY idx_unanswered_revisada (revisada, ultima_vez),
    KEY idx_unanswered_intent (intent_id),
    CONSTRAINT fk_unanswered_intent FOREIGN KEY (intent_id) REFERENCES asistente_intents (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS asistente_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    intent_id INT UNSIGNED NULL,
    acierto TINYINT(1) NOT NULL DEFAULT 0,
    confianza DECIMAL(4,3) NOT NULL DEFAULT 0.000,
    ms INT UNSIGNED NOT NULL DEFAULT 0,
    creada_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_events_fecha (creada_en),
    KEY idx_events_intent (intent_id),
    CONSTRAINT fk_events_intent FOREIGN KEY (intent_id) REFERENCES asistente_intents (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS asistente_rate_limits (
    bucket CHAR(64) NOT NULL,
    hits INT UNSIGNED NOT NULL DEFAULT 0,
    reset_at INT UNSIGNED NOT NULL,
    PRIMARY KEY (bucket),
    KEY idx_rate_reset (reset_at)
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_bin;

CREATE TABLE IF NOT EXISTS asistente_admin_users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario VARCHAR(60) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    nombre VARCHAR(120) NOT NULL,
    rol ENUM('superadmin', 'admin') NOT NULL DEFAULT 'admin',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    ultimo_acceso DATETIME NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_admin_usuario (usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS asistente_sync_log (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    iniciado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    terminado_en DATETIME NULL,
    estado ENUM('ok', 'error', 'sin_datos') NOT NULL DEFAULT 'ok',
    detalle VARCHAR(500) NULL,
    actualizados INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_sync_fecha (iniciado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
