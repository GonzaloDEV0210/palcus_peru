-- admin/migrations/add_producto_imagenes.sql
CREATE TABLE `producto_imagenes` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `producto_id` INT UNSIGNED NOT NULL,
  `imagen_url` VARCHAR(500) NOT NULL,
  FOREIGN KEY (`producto_id`) REFERENCES `productos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
