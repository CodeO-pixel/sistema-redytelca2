-- Rollback de 2026-11-02_pago_movil_destino.sql.
-- Requiere respaldo: se pierden los datos de destino guardados en estas columnas.
-- El método pago_movil permanece en el catálogo para preservar posibles FK en pagos.

ALTER TABLE `metodos_pago`
  DROP COLUMN `telefono_destino`,
  DROP COLUMN `identificacion_destino`,
  DROP COLUMN `tipo_identificacion_destino`,
  DROP COLUMN `banco_destino`;
