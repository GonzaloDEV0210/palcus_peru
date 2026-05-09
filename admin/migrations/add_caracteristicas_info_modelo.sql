ALTER TABLE `productos`
  ADD COLUMN `caracteristicas` TEXT NULL AFTER `imagen_url`,
  ADD COLUMN `info_modelo` TEXT NULL AFTER `caracteristicas`;
