-- Bairoom SQL (listo para importar en InfinityFree)
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS password_reset;
DROP TABLE IF EXISTS pago;
DROP TABLE IF EXISTS contrato;
DROP TABLE IF EXISTS reserva;
DROP TABLE IF EXISTS habitacion_imagen;
DROP TABLE IF EXISTS propiedad_imagen;
DROP TABLE IF EXISTS bloqueo_habitacion;
DROP TABLE IF EXISTS habitacion;
DROP TABLE IF EXISTS propiedad;
DROP TABLE IF EXISTS usuario;
DROP TABLE IF EXISTS rol;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE rol (
  id_rol INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE usuario (
  id_usuario INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(50) NOT NULL,
  apellidos VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  contrasena VARCHAR(255) NOT NULL,
  telefono VARCHAR(20),
  fecha_alta DATE NOT NULL,
  estado ENUM('activo','inactivo') DEFAULT 'activo',
  id_rol INT NOT NULL,
  CONSTRAINT fk_usuario_rol
    FOREIGN KEY (id_rol) REFERENCES rol(id_rol)
      ON UPDATE CASCADE
      ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE propiedad (
  id_propiedad INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  direccion VARCHAR(150) NOT NULL,
  ciudad VARCHAR(80) NOT NULL,
  cp VARCHAR(10),
  descripcion TEXT,
  capacidad_total INT NOT NULL,
  id_propietario INT NOT NULL,
  CONSTRAINT fk_propiedad_propietario
    FOREIGN KEY (id_propietario) REFERENCES usuario(id_usuario)
      ON UPDATE CASCADE
      ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE habitacion (
  id_habitacion INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(80) NOT NULL,
  m2 DECIMAL(5,2),
  tipo VARCHAR(30) NOT NULL DEFAULT 'individual',
  capacidad INT NOT NULL,
  precio_noche DECIMAL(8,2) NOT NULL,
  estado ENUM('disponible','ocupada','mantenimiento') DEFAULT 'disponible',
  id_propiedad INT NOT NULL,
  CONSTRAINT fk_habitacion_propiedad
    FOREIGN KEY (id_propiedad) REFERENCES propiedad(id_propiedad)
      ON UPDATE CASCADE
      ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE reserva (
  id_reserva INT AUTO_INCREMENT PRIMARY KEY,
  fecha_inicio DATE NOT NULL,
  fecha_fin DATE NOT NULL,
  fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  estado ENUM('pendiente','aceptada','rechazada','cancelada') DEFAULT 'pendiente',
  num_personas INT DEFAULT 1,
  motivo VARCHAR(255),
  observaciones TEXT,
  id_usuario INT NOT NULL,
  id_habitacion INT NOT NULL,
  CONSTRAINT fk_reserva_usuario
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
      ON UPDATE CASCADE
      ON DELETE RESTRICT,
  CONSTRAINT fk_reserva_habitacion
    FOREIGN KEY (id_habitacion) REFERENCES habitacion(id_habitacion)
      ON UPDATE CASCADE
      ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE propiedad_imagen (
  id_imagen INT AUTO_INCREMENT PRIMARY KEY,
  id_propiedad INT NOT NULL,
  ruta_imagen VARCHAR(255) NOT NULL,
  es_principal TINYINT(1) DEFAULT 0,
  CONSTRAINT fk_propiedad_imagen_propiedad
    FOREIGN KEY (id_propiedad) REFERENCES propiedad(id_propiedad)
      ON UPDATE CASCADE
      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE habitacion_imagen (
  id_imagen INT AUTO_INCREMENT PRIMARY KEY,
  id_habitacion INT NOT NULL,
  ruta_imagen VARCHAR(255) NOT NULL,
  es_principal TINYINT(1) DEFAULT 0,
  CONSTRAINT fk_habitacion_imagen_habitacion
    FOREIGN KEY (id_habitacion) REFERENCES habitacion(id_habitacion)
      ON UPDATE CASCADE
      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE bloqueo_habitacion (
  id_bloqueo INT AUTO_INCREMENT PRIMARY KEY,
  id_habitacion INT NOT NULL,
  fecha_inicio DATE NOT NULL,
  fecha_fin DATE NOT NULL,
  motivo VARCHAR(255),
  id_usuario INT NOT NULL,
  CONSTRAINT fk_bloqueo_habitacion
    FOREIGN KEY (id_habitacion) REFERENCES habitacion(id_habitacion)
      ON UPDATE CASCADE
      ON DELETE CASCADE,
  CONSTRAINT fk_bloqueo_usuario
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
      ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE pago (
  id_pago INT AUTO_INCREMENT PRIMARY KEY,
  id_reserva INT NOT NULL,
  stripe_session_id VARCHAR(255) DEFAULT NULL,
  stripe_payment_intent VARCHAR(255) DEFAULT NULL,
  estado ENUM('pendiente','pagado','fallido','reembolsado') DEFAULT 'pendiente',
  importe DECIMAL(10,2) NOT NULL,
  moneda VARCHAR(10) DEFAULT 'EUR',
  fecha_pago DATETIME DEFAULT NULL,
  CONSTRAINT fk_pago_reserva
    FOREIGN KEY (id_reserva) REFERENCES reserva(id_reserva)
      ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE contrato (
  id_contrato INT AUTO_INCREMENT PRIMARY KEY,
  id_reserva INT NOT NULL UNIQUE,
  fecha_firma DATETIME NOT NULL,
  ruta_archivo VARCHAR(255),
  estado ENUM('pendiente','firmado','cancelado') DEFAULT 'pendiente',
  CONSTRAINT fk_contrato_reserva
    FOREIGN KEY (id_reserva) REFERENCES reserva(id_reserva)
      ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE password_reset (
  id_reset INT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT NOT NULL,
  token_hash VARCHAR(255) NOT NULL,
  expiracion DATETIME NOT NULL,
  usado_en DATETIME DEFAULT NULL,
  CONSTRAINT fk_password_reset_usuario
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
      ON DELETE CASCADE
      ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO rol (id_rol, nombre) VALUES
(1, 'Administrador'),
(2, 'Propietario'),
(3, 'Inquilino');

INSERT INTO usuario (id_usuario, nombre, apellidos, email, contrasena, telefono, fecha_alta, estado, id_rol) VALUES
(1, 'Jesús', 'Bailén Sánchez', 'admin@bairoom.com', '$2y$10$dh6/dAA36mhBD8PvaYSmReGWB0VQu5wv5oyjieGvdSztdebFI6iHq', '600000001', '2025-01-01', 'activo', 1),
(2, 'Laura', 'García Pérez', 'laura@bairoom.com', '$2y$10$6d6zfJlFNeMAQNLhdcHXAuafTRNd8J6F9N6BDGD3ZFooJmfAbFIzO', '600000002', '2025-01-05', 'activo', 2),
(3, 'Carlos', 'López Martín', 'carlos@bairoom.com', '$2y$10$T2AfDulj6TCMBsgvRlJwgOYWln2ir9LGdjk0lnmCE3mEvR8PKz9rG', '600000003', '2025-01-05', 'activo', 2),
(4, 'Marta', 'Ruiz Torres', 'marta@bairoom.com', '$2y$10$OmBk.sakTjEOJ65.UFa2CehUT3s48e4J7PkN1TlqScCE.sTVMNiX.', '600000004', '2025-01-10', 'activo', 2),
(5, 'Ana', 'Serrano Díaz', 'ana@bairoom.com', '$2y$10$RXnYQAc1r11w4KhpLf34XOJcXSQ7oTH6m3QmfN9QmARZ0cTj2BbZK', '600000005', '2025-02-01', 'activo', 3),
(6, 'Pablo', 'Hernández Gil', 'pablo@bairoom.com', '$2y$10$Cfch9MK3zMJpvecTCSHX8e/gNV1pjKbbq8hpZa/r/yOUuOYpJSepa', '600000006', '2025-02-03', 'activo', 3),
(7, 'Sergio', 'Morales Cano', 'sergio@bairoom.com', '$2y$10$/hlakF0dPBDc7MG/s2YTWO3qthvy2MEMN0y.nMNtgAHl3gsKkrur.', '600000007', '2025-02-07', 'activo', 3),
(8, 'Lucía', 'Navarro Ortiz', 'lucia@bairoom.com', '$2y$10$ZsYESvkkMGIasoqrvAkneu1/B3RMNovqA1MBc5nZCb8DNrh8QHuZG', '600000008', '2025-02-10', 'activo', 3),
(9, 'David', 'Crespo Vidal', 'david@bairoom.com', '$2y$10$n5RPlKPRlDN15M3Zw23wyuFsIbQQq0WxYTmW5ySsFTV1rMHFjK0f.', '600000009', '2025-02-12', 'activo', 3),
(10, 'Elena', 'Martí Gómez', 'elena@bairoom.com', '$2y$10$0n0SvlV7523Mj/IasreYguwjgY0FCUPtzslffVyQIMFBhrWtJFUWO', '600000010', '2025-02-15', 'activo', 3);

INSERT INTO propiedad (id_propiedad, nombre, direccion, ciudad, cp, descripcion, capacidad_total, id_propietario) VALUES
(1, 'Piso Centro Elche', 'C/ Mayor 10, 3ºA', 'Elche', '03201', 'Piso luminoso cerca del centro', 3, 2),
(2, 'Ático Playa Santa Pola', 'Av. Mar 25, 5ºB', 'Santa Pola', '03130', 'Ático con terraza y vistas al mar', 2, 2),
(3, 'Piso Universidad Elche', 'C/ Campus 5, 2ºC', 'Elche', '03202', 'Piso compartido para estudiantes', 4, 3),
(4, 'Habitaciones San Juan', 'C/ Sol 8, 1ºD', 'San Juan', '03550', 'Vivienda para trabajadores sanitarios', 3, 4),
(5, 'Loft Alicante Centro', 'C/ Teatro 3, 4ºA', 'Alicante', '03001', 'Loft moderno de una habitación', 1, 3);

INSERT INTO habitacion (id_habitacion, nombre, m2, tipo, capacidad, precio_noche, estado, id_propiedad) VALUES
(1, 'Hab 1 - Centro Elche', 12.00, 'individual', 1, 30.00, 'disponible', 1),
(2, 'Hab 2 - Centro Elche', 14.50, 'doble', 2, 45.00, 'disponible', 1),
(3, 'Hab 3 - Centro Elche', 10.00, 'individual', 1, 30.00, 'ocupada', 1),
(4, 'Hab 1 - Ático Santa Pola', 15.00, 'individual', 1, 30.00, 'disponible', 2),
(5, 'Hab 2 - Ático Santa Pola', 18.00, 'doble', 2, 45.00, 'ocupada', 2),
(6, 'Hab 1 - Univ Elche', 11.00, 'individual', 1, 30.00, 'ocupada', 3),
(7, 'Hab 2 - Univ Elche', 11.50, 'doble', 2, 45.00, 'ocupada', 3),
(8, 'Hab 3 - Univ Elche', 9.50, 'individual', 1, 30.00, 'ocupada', 3),
(9, 'Hab 1 - San Juan', 13.00, 'doble', 2, 45.00, 'ocupada', 4),
(10, 'Hab 2 - San Juan', 13.50, 'individual', 1, 30.00, 'mantenimiento', 4);

INSERT INTO reserva (id_reserva, fecha_inicio, fecha_fin, fecha_creacion, estado, num_personas, motivo, observaciones, id_usuario, id_habitacion) VALUES
(1, '2025-03-01', '2025-06-30', '2025-02-20 10:15:00', 'aceptada', 1, 'Trabajo en Elche', 'Llega con coche propio', 5, 1),
(2, '2025-02-15', '2025-05-15', '2025-02-10 09:40:00', 'aceptada', 1, 'Prácticas en empresa', NULL, 6, 3),
(3, '2025-04-01', '2025-07-01', '2025-03-20 12:05:00', 'pendiente', 1, 'Temporada de verano', 'Pendiente de confirmar fechas exactas', 7, 4),
(4, '2025-03-10', '2025-06-10', '2025-02-25 18:30:00', 'rechazada', 1, 'Curso intensivo', 'Rechazada por solape con otra reserva', 8, 6),
(5, '2025-05-01', '2025-08-31', '2025-04-12 11:20:00', 'aceptada', 1, 'Estudios universitarios', NULL, 9, 7),
(6, '2025-03-05', '2025-04-05', '2025-02-28 13:10:00', 'cancelada', 1, 'Trabajo temporal', 'Cancelada por el usuario', 10, 2),
(7, '2025-09-01', '2025-12-31', '2025-08-10 16:00:00', 'pendiente', 1, 'Máster en Elche', NULL, 5, 8),
(8, '2025-06-01', '2025-09-30', '2025-05-15 09:15:00', 'pendiente', 1, 'Verano en la costa', NULL, 6, 5),
(9, '2025-02-01', '2025-04-30', '2025-01-20 08:50:00', 'aceptada', 1, 'Contrato hospital', NULL, 7, 9),
(10, '2025-01-15', '2025-03-15', '2025-01-02 10:00:00', 'aceptada', 1, 'Sustitución sanitaria', 'Habitación en mantenimiento después', 8, 10);

COMMIT;
