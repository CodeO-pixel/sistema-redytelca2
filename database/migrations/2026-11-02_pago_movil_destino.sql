-- Guarda y publica los datos de destino del método Pago Móvil en el portal.
-- Hacer respaldo antes de aplicar. Requiere MariaDB 10.4.

ALTER TABLE `metodos_pago`
  ADD COLUMN `banco_destino` varchar(120) DEFAULT NULL AFTER `orden`,
  ADD COLUMN `tipo_identificacion_destino` varchar(10) DEFAULT NULL AFTER `banco_destino`,
  ADD COLUMN `identificacion_destino` varchar(20) DEFAULT NULL AFTER `tipo_identificacion_destino`,
  ADD COLUMN `telefono_destino` varchar(20) DEFAULT NULL AFTER `identificacion_destino`;

INSERT INTO `metodos_pago` (
  `codigo`,
  `nombre`,
  `requiere_referencia`,
  `activo`,
  `orden`,
  `banco_destino`,
  `tipo_identificacion_destino`,
  `identificacion_destino`,
  `telefono_destino`
) VALUES (
  'pago_movil',
  'Pago móvil',
  1,
  1,
  2,
  'Banco de Venezuela',
  'CI',
  '31.143.909',
  '04246690219'
)
ON DUPLICATE KEY UPDATE
  `nombre` = VALUES(`nombre`),
  `requiere_referencia` = VALUES(`requiere_referencia`),
  `activo` = VALUES(`activo`),
  `orden` = VALUES(`orden`),
  `banco_destino` = VALUES(`banco_destino`),
  `tipo_identificacion_destino` = VALUES(`tipo_identificacion_destino`),
  `identificacion_destino` = VALUES(`identificacion_destino`),
  `telefono_destino` = VALUES(`telefono_destino`);
