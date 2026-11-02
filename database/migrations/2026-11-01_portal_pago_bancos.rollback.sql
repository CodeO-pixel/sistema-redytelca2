-- Rollback de 2026-11-01_portal_pago_bancos.sql.
-- Requiere respaldo: se pierden las referencias de banco guardadas en pagos.

ALTER TABLE `pagos`
  DROP FOREIGN KEY `fk_pago_banco_emisor`,
  DROP KEY `idx_pago_banco_emisor`,
  DROP COLUMN `banco_emisor_id`;

DROP TABLE `bancos`;
