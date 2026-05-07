-- ============================================================
-- PalCus Admin - Base de Datos
-- Ejecutar en phpMyAdmin o MySQL CLI
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Crear BD (ajusta el nombre si usas otro)
CREATE DATABASE IF NOT EXISTS `palcus_admin`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `palcus_admin`;

-- ────────────────────────────────────────────────────────────
-- USUARIOS
-- ────────────────────────────────────────────────────────────
CREATE TABLE `usuarios` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nombre`        VARCHAR(100)  NOT NULL,
  `email`         VARCHAR(150)  NOT NULL UNIQUE,
  `password_hash` VARCHAR(255)  NOT NULL,
  `rol`           ENUM('admin','vendedor','almacenero') NOT NULL DEFAULT 'vendedor',
  `telefono`      VARCHAR(20)   DEFAULT NULL,
  `activo`        TINYINT(1)    NOT NULL DEFAULT 1,
  `ultimo_acceso` DATETIME      DEFAULT NULL,
  `created_at`    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ────────────────────────────────────────────────────────────
-- CATEGORÍAS
-- ────────────────────────────────────────────────────────────
CREATE TABLE `categorias` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nombre`      VARCHAR(100) NOT NULL,
  `prefijo`     VARCHAR(10)  DEFAULT NULL,
  `descripcion` TEXT         DEFAULT NULL,
  `activo`      TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ────────────────────────────────────────────────────────────
-- PRODUCTOS
-- ────────────────────────────────────────────────────────────
CREATE TABLE `productos` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `sku`            VARCHAR(50)    UNIQUE DEFAULT NULL,
  `nombre`         VARCHAR(200)   NOT NULL,
  `descripcion`    TEXT           DEFAULT NULL,
  `precio_compra`  DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  `precio_venta`   DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  `categoria_id`   INT UNSIGNED   DEFAULT NULL,
  `imagen_url`     VARCHAR(500)   DEFAULT NULL,
  `activo`         TINYINT(1)     NOT NULL DEFAULT 1,
  `created_at`     TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`categoria_id`) REFERENCES `categorias`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ────────────────────────────────────────────────────────────
-- VARIACIONES (Talla + Color → Stock)
-- ────────────────────────────────────────────────────────────
CREATE TABLE `variaciones` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `producto_id`  INT UNSIGNED NOT NULL,
  `talla`        VARCHAR(20)  NOT NULL,
  `color`        VARCHAR(50)  NOT NULL,
  `stock`        INT          NOT NULL DEFAULT 0,
  `stock_minimo` INT          NOT NULL DEFAULT 5,
  `activo`       TINYINT(1)   NOT NULL DEFAULT 1,
  UNIQUE KEY `uk_variacion` (`producto_id`,`talla`,`color`),
  FOREIGN KEY (`producto_id`) REFERENCES `productos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ────────────────────────────────────────────────────────────
-- CLIENTES
-- ────────────────────────────────────────────────────────────
CREATE TABLE `clientes` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nombre`     VARCHAR(150) NOT NULL,
  `telefono`   VARCHAR(20)  DEFAULT NULL,
  `email`      VARCHAR(150) DEFAULT NULL,
  `direccion`  TEXT         DEFAULT NULL,
  `dni`        VARCHAR(20)  DEFAULT NULL,
  `notas`      TEXT         DEFAULT NULL,
  `activo`     TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ────────────────────────────────────────────────────────────
-- PROVEEDORES
-- ────────────────────────────────────────────────────────────
CREATE TABLE `proveedores` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nombre`     VARCHAR(150) NOT NULL,
  `contacto`   VARCHAR(100) DEFAULT NULL,
  `telefono`   VARCHAR(20)  DEFAULT NULL,
  `email`      VARCHAR(150) DEFAULT NULL,
  `direccion`  TEXT         DEFAULT NULL,
  `ruc`        VARCHAR(20)  DEFAULT NULL,
  `notas`      TEXT         DEFAULT NULL,
  `activo`     TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ────────────────────────────────────────────────────────────
-- VENTAS
-- ────────────────────────────────────────────────────────────
CREATE TABLE `ventas` (
  `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `codigo`              VARCHAR(20)   NOT NULL UNIQUE,
  `cliente_id`          INT UNSIGNED  DEFAULT NULL,
  `usuario_id`          INT UNSIGNED  NOT NULL,
  `subtotal`            DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `descuento`           DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total`               DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `metodo_pago`         ENUM('efectivo','yape','plin','transferencia','tarjeta','otro') NOT NULL DEFAULT 'efectivo',
  `estado`              ENUM('pendiente','completada','cancelada') NOT NULL DEFAULT 'completada',
  `notas`               TEXT          DEFAULT NULL,
  `documento_drive_url` VARCHAR(500)  DEFAULT NULL,
  `fecha`               DATE          NOT NULL,
  `created_at`          TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`cliente_id`) REFERENCES `clientes`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ────────────────────────────────────────────────────────────
-- VENTAS DETALLE
-- ────────────────────────────────────────────────────────────
CREATE TABLE `ventas_detalle` (
  `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `venta_id`         INT UNSIGNED   NOT NULL,
  `variacion_id`     INT UNSIGNED   DEFAULT NULL,
  `producto_id`      INT UNSIGNED   DEFAULT NULL,
  `nombre_producto`  VARCHAR(200)   NOT NULL,
  `talla`            VARCHAR(20)    DEFAULT NULL,
  `color`            VARCHAR(50)    DEFAULT NULL,
  `cantidad`         INT            NOT NULL DEFAULT 1,
  `precio_unitario`  DECIMAL(10,2)  NOT NULL,
  `subtotal`         DECIMAL(10,2)  NOT NULL,
  FOREIGN KEY (`venta_id`)     REFERENCES `ventas`(`id`)     ON DELETE CASCADE,
  FOREIGN KEY (`variacion_id`) REFERENCES `variaciones`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`producto_id`)  REFERENCES `productos`(`id`)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ────────────────────────────────────────────────────────────
-- GASTOS
-- ────────────────────────────────────────────────────────────
CREATE TABLE `gastos` (
  `id`                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `descripcion`           VARCHAR(300) NOT NULL,
  `monto`                 DECIMAL(10,2) NOT NULL,
  `categoria`             ENUM('compra_inventario','alquiler','servicios','transporte','marketing','personal','otro') NOT NULL DEFAULT 'otro',
  `proveedor_id`          INT UNSIGNED DEFAULT NULL,
  `comprobante_drive_url` VARCHAR(500) DEFAULT NULL,
  `fecha`                 DATE         NOT NULL,
  `usuario_id`            INT UNSIGNED NOT NULL,
  `notas`                 TEXT         DEFAULT NULL,
  `created_at`            TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`usuario_id`)   REFERENCES `usuarios`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ────────────────────────────────────────────────────────────
-- MOVIMIENTOS DE INVENTARIO (Kardex)
-- ────────────────────────────────────────────────────────────
CREATE TABLE `movimientos_inventario` (
  `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tipo`             ENUM('entrada','salida','ajuste') NOT NULL,
  `variacion_id`     INT UNSIGNED  DEFAULT NULL,
  `producto_id`      INT UNSIGNED  DEFAULT NULL,
  `nombre_producto`  VARCHAR(200)  NOT NULL,
  `talla`            VARCHAR(20)   DEFAULT NULL,
  `color`            VARCHAR(50)   DEFAULT NULL,
  `cantidad`         INT           NOT NULL,
  `stock_antes`      INT           NOT NULL DEFAULT 0,
  `stock_despues`    INT           NOT NULL DEFAULT 0,
  `motivo`           VARCHAR(300)  DEFAULT NULL,
  `referencia_id`    INT UNSIGNED  DEFAULT NULL,
  `referencia_tipo`  VARCHAR(50)   DEFAULT NULL,
  `usuario_id`       INT UNSIGNED  DEFAULT NULL,
  `created_at`       TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`variacion_id`) REFERENCES `variaciones`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`usuario_id`)   REFERENCES `usuarios`(`id`)    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ────────────────────────────────────────────────────────────
-- DOCUMENTOS (solo link de Google Drive)
-- ────────────────────────────────────────────────────────────
CREATE TABLE `documentos` (
  `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nombre`           VARCHAR(200) NOT NULL,
  `tipo`             VARCHAR(50)  NOT NULL,
  `drive_url`        VARCHAR(500) NOT NULL,
  `drive_file_id`    VARCHAR(200) DEFAULT NULL,
  `referencia_id`    INT UNSIGNED DEFAULT NULL,
  `referencia_tipo`  VARCHAR(50)  DEFAULT NULL,
  `usuario_id`       INT UNSIGNED DEFAULT NULL,
  `created_at`       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ────────────────────────────────────────────────────────────
-- CONFIGURACIÓN DEL SISTEMA
-- ────────────────────────────────────────────────────────────
CREATE TABLE `configuracion` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `clave`       VARCHAR(100) NOT NULL UNIQUE,
  `valor`       TEXT         DEFAULT NULL,
  `descripcion` VARCHAR(300) DEFAULT NULL,
  `updated_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ────────────────────────────────────────────────────────────
-- DATOS INICIALES
-- ────────────────────────────────────────────────────────────

-- Usuario Admin por defecto: email=admin@palcus.com / pass=Admin2025!
-- Hash generado con: password_hash('Admin2025!', PASSWORD_BCRYPT, ['cost'=>12])
INSERT INTO `usuarios` (`nombre`,`email`,`password_hash`,`rol`) VALUES
('Administrador','admin@palcus.com','$2y$12$emC8uwkeAP9kAK9w2n7hJulwf1iwZxmUJyB2xjzIv5FYVUQUkU3DG','admin');

-- Categorías base
INSERT INTO `categorias` (`nombre`,`prefijo`,`descripcion`) VALUES
('Manga Corta',  'MANCO', 'Polos con manga corta para mujer'),
('Manga Cero',   'MANCE', 'Tops sin mangas para mujer'),
('Cuello Canoa', 'CUECA', 'Polos con cuello canoa para mujer');

-- Configuración por defecto
INSERT INTO `configuracion` (`clave`,`valor`,`descripcion`) VALUES
('nombre_tienda',         'PalCus Perú',        'Nombre de la tienda'),
('moneda_simbolo',        'S/',                  'Símbolo de moneda'),
('stock_minimo_global',   '5',                   'Stock mínimo global para alertas'),
('callmebot_phone',       '',                    'Número WhatsApp para alertas (ej: 51981293422)'),
('callmebot_api_key',     '',                    'API Key de CallMeBot'),
('google_drive_folder_id','',                    'ID de carpeta raíz en Google Drive'),
('igv_porcentaje',        '18',                  'Porcentaje de IGV');
