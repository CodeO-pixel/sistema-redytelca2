-- Rollback F2.31. Este DDL es destructivo y MariaDB hace commit implícito
-- en ALTER/DROP: respaldar pagos y tasas_bcv antes de continuar.
--
-- Preflight obligatorio:
-- 1) tasa_cambio no debe contener precisión superior a los 4 decimales previos.
-- 2) exportar todos los pagos con tasa_fecha_valor si se necesita conservar
--    ese dato antes de eliminar la columna.
-- 3) conservar/exportar tasas_bcv si se necesita el historial después del rollback.

SELECT COUNT(*) AS pagos_con_precision_mayor_a_4
FROM `pagos`
WHERE `tasa_cambio` IS NOT NULL
  AND `tasa_cambio` <> ROUND(`tasa_cambio`, 4);

SELECT COUNT(*) AS pagos_con_fecha_valor_bcv
FROM `pagos`
WHERE `tasa_fecha_valor` IS NOT NULL;

-- Proceder solo si se revisaron las consultas anteriores y se protegieron sus datos.
ALTER TABLE `pagos`
  DROP COLUMN `tasa_fecha_valor`,
  MODIFY COLUMN `tasa_cambio` decimal(10,4) DEFAULT NULL
    COMMENT 'Tasa BCV (Bs/USD) del día del pago. Obligatoria si moneda_pago=VES, NULL si moneda_pago=USD.';

DROP TABLE `tasas_bcv`;
