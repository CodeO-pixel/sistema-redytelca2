-- Catálogo de bancos emisores y fecha de pago declarada en el portal.
-- Hacer respaldo antes de aplicar. Requiere MariaDB 10.4.

CREATE TABLE `bancos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(120) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_bancos_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `bancos` (`nombre`, `activo`) VALUES
  ('100% Banco', 1),
  ('Bancamiga', 1),
  ('Banco Activo', 1),
  ('Banco Agrícola de Venezuela', 1),
  ('Banco Digital de los Trabajadores', 1),
  ('Banco Caroní', 1),
  ('Banco de Comercio Exterior (BANCOEX)', 1),
  ('Banco de Desarrollo Económico y Social de Venezuela (BANDES)', 1),
  ('Banco de la Fuerza Armada Nacional Bolivariana (BANFANB)', 1),
  ('Banco de la Gente Emprendedora (BANGENTE)', 1),
  ('Banco de Venezuela', 1),
  ('Bancaribe', 1),
  ('Banco del Tesoro', 1),
  ('Banco Exterior', 1),
  ('Banco Internacional de Desarrollo', 1),
  ('Banco Nacional de Crédito (BNC)', 1),
  ('Banco Plaza', 1),
  ('Banco Provincial', 1),
  ('Banco Sofitasa', 1),
  ('Bancrecer', 1),
  ('Banesco', 1),
  ('Banplus', 1),
  ('BFC Banco Fondo Común', 1),
  ('Del Sur Banco Universal', 1),
  ('Mercantil', 1),
  ('R4 Banco Microfinanciero', 1),
  ('Venezolano de Crédito', 1),
  ('N58 Banco Digital', 1);

ALTER TABLE `pagos`
  ADD COLUMN `banco_emisor_id` int(11) DEFAULT NULL AFTER `metodo_id`,
  ADD KEY `idx_pago_banco_emisor` (`banco_emisor_id`),
  ADD CONSTRAINT `fk_pago_banco_emisor`
    FOREIGN KEY (`banco_emisor_id`) REFERENCES `bancos` (`id`)
    ON UPDATE RESTRICT ON DELETE RESTRICT;
