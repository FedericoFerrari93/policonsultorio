CREATE DATABASE IF NOT EXISTS policonsultorio
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE policonsultorio;

CREATE TABLE IF NOT EXISTS roles (
    id_rol INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre_rol VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_rol INT UNSIGNED NOT NULL,
    username VARCHAR(80) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_usuarios_rol
        FOREIGN KEY (id_rol) REFERENCES roles (id_rol)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS especialidades (
    id_especialidad INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre_especialidad VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS medicos (
    id_medico INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT UNSIGNED NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    matricula VARCHAR(50) NULL UNIQUE,
    telefono VARCHAR(30) NULL,
    email VARCHAR(190) NULL,
    CONSTRAINT fk_medicos_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS medico_especialidades (
    id_medico INT UNSIGNED NOT NULL,
    id_especialidad INT UNSIGNED NOT NULL,
    PRIMARY KEY (id_medico, id_especialidad),
    CONSTRAINT fk_medico_especialidades_medico
        FOREIGN KEY (id_medico) REFERENCES medicos (id_medico)
        ON DELETE CASCADE,
    CONSTRAINT fk_medico_especialidades_especialidad
        FOREIGN KEY (id_especialidad) REFERENCES especialidades (id_especialidad)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS obras_sociales (
    id_obra_social INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre_institucion VARCHAR(150) NOT NULL UNIQUE,
    activo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pacientes (
    id_paciente INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_obra_social INT UNSIGNED NULL,
    dni VARCHAR(20) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    fecha_nacimiento DATE NOT NULL,
    telefono VARCHAR(30) NULL,
    email VARCHAR(190) NULL,
    nro_afiliado VARCHAR(80) NULL,
    fecha_alta TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pacientes_obra_social
        FOREIGN KEY (id_obra_social) REFERENCES obras_sociales (id_obra_social)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS turnos (
    id_turno INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_paciente INT UNSIGNED NOT NULL,
    id_medico INT UNSIGNED NOT NULL,
    fecha_hora DATETIME NOT NULL,
    tipo_cobertura VARCHAR(100) NULL,
    motivo_consulta TEXT NULL,
    estado ENUM('Pendiente', 'Atendido', 'Cancelado') NOT NULL DEFAULT 'Pendiente',
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_turnos_medico_horario UNIQUE (id_medico, fecha_hora),
    INDEX idx_turnos_fecha_medico (fecha_hora, id_medico),
    CONSTRAINT fk_turnos_paciente
        FOREIGN KEY (id_paciente) REFERENCES pacientes (id_paciente),
    CONSTRAINT fk_turnos_medico
        FOREIGN KEY (id_medico) REFERENCES medicos (id_medico)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS historias_clinicas (
    id_historia INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_paciente INT UNSIGNED NOT NULL,
    id_medico INT UNSIGNED NOT NULL,
    id_turno INT UNSIGNED NOT NULL UNIQUE,
    fecha_atencion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    diagnostico TEXT NOT NULL,
    tratamiento TEXT NULL,
    CONSTRAINT fk_historias_paciente
        FOREIGN KEY (id_paciente) REFERENCES pacientes (id_paciente),
    CONSTRAINT fk_historias_medico
        FOREIGN KEY (id_medico) REFERENCES medicos (id_medico),
    CONSTRAINT fk_historias_turno
        FOREIGN KEY (id_turno) REFERENCES turnos (id_turno)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS logs_acceso (
    id_log BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT UNSIGNED NULL,
    username_intentado VARCHAR(80) NOT NULL,
    evento VARCHAR(40) NOT NULL,
    ip_origen VARCHAR(45) NULL,
    user_agent TEXT NULL,
    fecha_hora TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_logs_fecha_evento (fecha_hora, evento),
    CONSTRAINT fk_logs_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario)
        ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT IGNORE INTO roles (id_rol, nombre_rol) VALUES
    (1, 'Administrador'),
    (2, 'Recepcionista'),
    (3, 'Médico');

-- Cuentas únicamente para desarrollo local. Cambiar o eliminar antes de usar en producción.
INSERT IGNORE INTO usuarios (id_usuario, id_rol, username, password_hash, activo) VALUES
    (1, 1, 'admin_demo', '$2y$10$J1b9LsLuTXYyLKvbZpH4Y.HV0n1KLQz/wu6ntiFCQQwOLwZop0pHS', 1),
    (2, 2, 'recepcion_demo', '$2y$10$.hBW.kse.C0tYCHQdkwHXOZoCPfVd72l8vo/zpJbpS28Ip3zF1U22', 1),
    (3, 3, 'medico_demo', '$2y$10$S7SclApXNEYYNs4yzaDONOPDxesw.9uzK15NP4eC2IvDtmgdq7TAS', 1);

INSERT IGNORE INTO especialidades (id_especialidad, nombre_especialidad) VALUES
    (1, 'Clínica médica'),
    (2, 'Cardiología');

INSERT IGNORE INTO obras_sociales (id_obra_social, nombre_institucion, activo) VALUES
    (1, 'Cobertura de demostración', 1);

INSERT IGNORE INTO medicos (id_medico, id_usuario, nombre, apellido, matricula, telefono, email) VALUES
    (1, 3, 'Alex', 'Medico Demo', 'DEMO-001', NULL, 'medico.demo@example.test');

INSERT IGNORE INTO medico_especialidades (id_medico, id_especialidad) VALUES
    (1, 1),
    (1, 2);

INSERT IGNORE INTO pacientes (
    id_paciente, id_obra_social, dni, nombre, apellido, fecha_nacimiento, telefono, email, nro_afiliado
) VALUES
    (1, 1, '00000001', 'Paciente', 'Ejemplo', '1990-01-01', NULL, 'paciente@example.test', 'DEMO-0001');

INSERT IGNORE INTO turnos (
    id_turno, id_paciente, id_medico, fecha_hora, tipo_cobertura, motivo_consulta, estado
) VALUES (
    1, 1, 1, DATE_ADD(NOW(), INTERVAL 1 HOUR), 'Demostración', 'Turno de prueba', 'Pendiente'
);
