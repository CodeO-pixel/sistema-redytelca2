-- F2.31: historial diario de la tasa oficial BCV y fecha valor congelada en pagos.
-- Requiere MariaDB 10.4. Hacer respaldo antes de aplicar; ejecutar una sola vez.
-- Los pagos existentes conservan su tasa; la Fecha Valor histórica queda NULL
-- porque no puede reconstruirse de forma confiable a partir de fecha_pago.

CREATE TABLE `tasas_bcv` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fecha_valor` date NOT NULL,
  `tasa` decimal(16,8) NOT NULL,
  `consultado_en` datetime NOT NULL,
  `estado_consulta` enum('disponible','indisponible') NOT NULL DEFAULT 'disponible',
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tasas_bcv_fecha_valor` (`fecha_valor`),
  KEY `idx_tasas_bcv_consultado_en` (`consultado_en`),
  CONSTRAINT `chk_tasas_bcv_tasa` CHECK (`tasa` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `pagos`
  MODIFY COLUMN `tasa_cambio` decimal(16,8) DEFAULT NULL
    COMMENT 'Tasa BCV (Bs/USD) congelada al registrar el pago. Obligatoria si moneda_pago=VES, NULL si moneda_pago=USD.',
  ADD COLUMN `tasa_fecha_valor` date DEFAULT NULL
    COMMENT 'Fecha Valor BCV de la tasa congelada en tasa_cambio.'
    AFTER `tasa_cambio`;
