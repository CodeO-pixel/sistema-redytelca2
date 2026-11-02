-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: bd_redytelca
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `auditoria_logs`
--

DROP TABLE IF EXISTS `auditoria_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `auditoria_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `accion` enum('INSERT','UPDATE','DELETE','LOGIN','LOGOUT','LOGIN_FALLIDO') NOT NULL,
  `tabla_afectada` varchar(50) DEFAULT NULL,
  `id_registro_afectado` int(11) DEFAULT NULL,
  `tipo_actor` enum('staff','cliente','sistema') NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `credencial_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `old_value` longtext DEFAULT NULL CHECK (json_valid(`old_value`)),
  `new_value` longtext DEFAULT NULL CHECK (json_valid(`new_value`)),
  PRIMARY KEY (`id`),
  KEY `fk_log_usuario` (`usuario_id`),
  KEY `idx_log_fecha` (`creado_en`),
  KEY `idx_log_tabla_registro` (`tabla_afectada`,`id_registro_afectado`),
  KEY `idx_log_accion_fecha` (`accion`,`creado_en`),
  KEY `fk_log_credencial` (`credencial_id`),
  CONSTRAINT `fk_log_credencial` FOREIGN KEY (`credencial_id`) REFERENCES `clientes_credenciales` (`id`),
  CONSTRAINT `fk_log_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `chk_log_actor` CHECK (`tipo_actor` = 'staff' and `usuario_id` is not null and `credencial_id` is null or `tipo_actor` = 'cliente' and `credencial_id` is not null and `usuario_id` is null or `tipo_actor` = 'sistema' and `usuario_id` is null and `credencial_id` is null)
) ENGINE=InnoDB AUTO_INCREMENT=10446 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_auditoria_logs_inmutable_update BEFORE UPDATE ON auditoria_logs FOR EACH ROW BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'auditoria_logs es inmutable: UPDATE bloqueado.';
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER IF NOT EXISTS trg_auditoria_logs_inmutable_delete BEFORE DELETE ON auditoria_logs FOR EACH ROW BEGIN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'auditoria_logs es inmutable: DELETE bloqueado.'; END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `categorias_ticket`
--

DROP TABLE IF EXISTS `categorias_ticket`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categorias_ticket` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(60) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `color` char(7) NOT NULL DEFAULT '#104aa8',
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categoria_nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_categorias_ticket_audit_insert AFTER INSERT ON categorias_ticket FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, new_value)
  VALUES ('INSERT','categorias_ticket',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',NEW.id,'nombre',NEW.nombre,'activa',NEW.activa));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_categorias_ticket_audit_update AFTER UPDATE ON categorias_ticket FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value, new_value)
  VALUES ('UPDATE','categorias_ticket',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'nombre',OLD.nombre,'activa',OLD.activa),
          JSON_OBJECT('id',NEW.id,'nombre',NEW.nombre,'activa',NEW.activa));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_categorias_ticket_audit_delete AFTER DELETE ON categorias_ticket FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value)
  VALUES ('DELETE','categorias_ticket',OLD.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'nombre',OLD.nombre,'activa',OLD.activa));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `clientes`
--

DROP TABLE IF EXISTS `clientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(65) NOT NULL,
  `apellido` varchar(65) NOT NULL,
  `cedula` varchar(15) NOT NULL,
  `correo` varchar(80) NOT NULL,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `estado` enum('activo','suspendido','inactivo') NOT NULL DEFAULT 'activo',
  `segmento` varchar(30) DEFAULT NULL,
  `direccion_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cedula` (`cedula`),
  KEY `idx_clientes_estado` (`estado`),
  KEY `idx_clientes_apellidos_nombres` (`apellido`,`nombre`),
  KEY `idx_cliente_direccion` (`direccion_id`),
  CONSTRAINT `fk_cliente_direccion` FOREIGN KEY (`direccion_id`) REFERENCES `direccion` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_clientes_cedula` CHECK (`cedula` regexp '^([VEJPG]-)?[0-9]{6,9}$')
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_clientes_audit_insert AFTER INSERT ON clientes FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, new_value)
  VALUES ('INSERT','clientes',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',NEW.id,'nombre',NEW.nombre,'apellido',NEW.apellido,'cedula',NEW.cedula,
                      'correo',NEW.correo,'estado',NEW.estado,'segmento',NEW.segmento,'direccion_id',NEW.direccion_id));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_clientes_audit_update AFTER UPDATE ON clientes FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value, new_value)
  VALUES ('UPDATE','clientes',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'nombre',OLD.nombre,'apellido',OLD.apellido,'cedula',OLD.cedula,
                      'correo',OLD.correo,'estado',OLD.estado,'segmento',OLD.segmento,'direccion_id',OLD.direccion_id),
          JSON_OBJECT('id',NEW.id,'nombre',NEW.nombre,'apellido',NEW.apellido,'cedula',NEW.cedula,
                      'correo',NEW.correo,'estado',NEW.estado,'segmento',NEW.segmento,'direccion_id',NEW.direccion_id));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_clientes_audit_delete AFTER DELETE ON clientes FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value)
  VALUES ('DELETE','clientes',OLD.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'nombre',OLD.nombre,'apellido',OLD.apellido,'cedula',OLD.cedula,
                      'correo',OLD.correo,'estado',OLD.estado,'segmento',OLD.segmento,'direccion_id',OLD.direccion_id));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `clientes_credenciales`
--

DROP TABLE IF EXISTS `clientes_credenciales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clientes_credenciales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `correo_recuperacion` varchar(80) DEFAULT NULL,
  `estado` enum('activa','suspendida') DEFAULT 'activa',
  `intentos_fallidos` int(11) NOT NULL DEFAULT 0,
  `bloqueado_hasta` datetime DEFAULT NULL,
  `must_change_password` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `ultimo_acceso` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `uq_cliente` (`cliente_id`),
  CONSTRAINT `fk_credencial_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_clientes_credenciales_audit_insert AFTER INSERT ON clientes_credenciales FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, new_value)
  VALUES ('INSERT','clientes_credenciales',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',NEW.id,'cliente_id',NEW.cliente_id,'username',NEW.username,'estado',NEW.estado));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_clientes_credenciales_audit_update AFTER UPDATE ON clientes_credenciales FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value, new_value)
  VALUES ('UPDATE','clientes_credenciales',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'cliente_id',OLD.cliente_id,'username',OLD.username,'estado',OLD.estado),
          JSON_OBJECT('id',NEW.id,'cliente_id',NEW.cliente_id,'username',NEW.username,'estado',NEW.estado));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_clientes_credenciales_audit_delete AFTER DELETE ON clientes_credenciales FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value)
  VALUES ('DELETE','clientes_credenciales',OLD.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'cliente_id',OLD.cliente_id,'username',OLD.username,'estado',OLD.estado));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `configuracion_empresa`
--

DROP TABLE IF EXISTS `configuracion_empresa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `configuracion_empresa` (
  `id` int(11) NOT NULL,
  `nombre_empresa` varchar(100) NOT NULL DEFAULT '',
  `logo_path` varchar(255) DEFAULT NULL,
  `logo_path_sidebar` varchar(255) DEFAULT NULL,
  `color_primario` char(7) DEFAULT '#104aa8',
  `impuesto_porcentaje` decimal(5,2) NOT NULL DEFAULT 0.00,
  `dia_facturacion` tinyint(2) NOT NULL DEFAULT 1,
  `dias_gracia_mora` tinyint(2) NOT NULL DEFAULT 5,
  `monto_recargo_mora` decimal(10,2) NOT NULL DEFAULT 0.00,
  `dias_corte_automatico` tinyint(2) NOT NULL DEFAULT 10,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  CONSTRAINT `chk_config_singleton` CHECK (`id` = 1),
  CONSTRAINT `chk_config_impuesto` CHECK (`impuesto_porcentaje` between 0 and 100),
  CONSTRAINT `chk_config_dia_facturacion` CHECK (`dia_facturacion` between 1 and 31),
  CONSTRAINT `chk_config_dias_gracia` CHECK (`dias_gracia_mora` >= 0),
  CONSTRAINT `chk_config_recargo` CHECK (`monto_recargo_mora` >= 0),
  CONSTRAINT `chk_config_dias_corte` CHECK (`dias_corte_automatico` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_configuracion_empresa_audit_insert AFTER INSERT ON configuracion_empresa FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, new_value)
  VALUES ('INSERT','configuracion_empresa',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',NEW.id,'nombre_empresa',NEW.nombre_empresa,'color_primario',NEW.color_primario,
                      'impuesto_porcentaje',NEW.impuesto_porcentaje));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_configuracion_empresa_audit_update AFTER UPDATE ON configuracion_empresa FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value, new_value)
  VALUES ('UPDATE','configuracion_empresa',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'nombre_empresa',OLD.nombre_empresa,'color_primario',OLD.color_primario,
                      'impuesto_porcentaje',OLD.impuesto_porcentaje),
          JSON_OBJECT('id',NEW.id,'nombre_empresa',NEW.nombre_empresa,'color_primario',NEW.color_primario,
                      'impuesto_porcentaje',NEW.impuesto_porcentaje));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_configuracion_empresa_audit_delete AFTER DELETE ON configuracion_empresa FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value)
  VALUES ('DELETE','configuracion_empresa',OLD.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'nombre_empresa',OLD.nombre_empresa,'color_primario',OLD.color_primario,
                      'impuesto_porcentaje',OLD.impuesto_porcentaje));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `contratos`
--

DROP TABLE IF EXISTS `contratos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contratos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `servicio_id` int(11) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `tipo_contrato` enum('indefinido','plazo_fijo','promocional') DEFAULT 'indefinido',
  `estado` enum('vigente','vencido','rescindido') DEFAULT 'vigente',
  `observaciones` text DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_contrato_servicio` (`servicio_id`),
  CONSTRAINT `fk_contrato_servicio` FOREIGN KEY (`servicio_id`) REFERENCES `servicios` (`id`),
  CONSTRAINT `chk_contrato_fechas` CHECK (`fecha_fin` is null or `fecha_fin` >= `fecha_inicio`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_contratos_unico_vigente_insert BEFORE INSERT ON contratos FOR EACH ROW BEGIN
  DECLARE existe INT DEFAULT 0;
  IF NEW.estado = 'vigente' THEN
    SELECT COUNT(*) INTO existe FROM contratos
     WHERE servicio_id = NEW.servicio_id AND estado = 'vigente';
    IF existe > 0 THEN
      SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Ya existe un contrato vigente para este servicio.';
    END IF;
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_contratos_validar_insert BEFORE INSERT ON contratos FOR EACH ROW BEGIN
  IF NEW.estado <> 'vigente' THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'INSERT de contrato solo permite estado vigente. Use UPDATE para transicionar.';
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_contratos_audit_insert AFTER INSERT ON contratos FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, new_value)
  VALUES ('INSERT','contratos',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',NEW.id,'servicio_id',NEW.servicio_id,'fecha_inicio',NEW.fecha_inicio,
                      'fecha_fin',NEW.fecha_fin,'tipo_contrato',NEW.tipo_contrato,'estado',NEW.estado));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_contratos_unico_vigente_update BEFORE UPDATE ON contratos FOR EACH ROW BEGIN
  DECLARE existe INT DEFAULT 0;
  IF NEW.estado = 'vigente' AND (OLD.estado <> 'vigente' OR OLD.servicio_id <> NEW.servicio_id) THEN
    SELECT COUNT(*) INTO existe FROM contratos
     WHERE servicio_id = NEW.servicio_id AND estado = 'vigente' AND id <> NEW.id;
    IF existe > 0 THEN
      SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Ya existe otro contrato vigente para este servicio.';
    END IF;
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_contratos_validar_transicion BEFORE UPDATE ON contratos FOR EACH ROW BEGIN
  DECLARE msg VARCHAR(255);
  IF NEW.estado <> OLD.estado AND NOT EXISTS (
    SELECT 1 FROM transicion_estado
     WHERE tabla = 'contratos' AND estado_origen = OLD.estado AND estado_destino = NEW.estado
  ) THEN
    SET msg = CONCAT('Transicion no permitida en contratos: ', OLD.estado, ' -> ', NEW.estado);
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = msg;
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_contratos_audit_update AFTER UPDATE ON contratos FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value, new_value)
  VALUES ('UPDATE','contratos',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'servicio_id',OLD.servicio_id,'fecha_inicio',OLD.fecha_inicio,
                      'fecha_fin',OLD.fecha_fin,'tipo_contrato',OLD.tipo_contrato,'estado',OLD.estado),
          JSON_OBJECT('id',NEW.id,'servicio_id',NEW.servicio_id,'fecha_inicio',NEW.fecha_inicio,
                      'fecha_fin',NEW.fecha_fin,'tipo_contrato',NEW.tipo_contrato,'estado',NEW.estado));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_contratos_audit_delete AFTER DELETE ON contratos FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value)
  VALUES ('DELETE','contratos',OLD.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'servicio_id',OLD.servicio_id,'fecha_inicio',OLD.fecha_inicio,
                      'fecha_fin',OLD.fecha_fin,'tipo_contrato',OLD.tipo_contrato,'estado',OLD.estado));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `direccion`
--

DROP TABLE IF EXISTS `direccion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `direccion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `calle` varchar(150) DEFAULT NULL,
  `carrera` varchar(150) DEFAULT NULL,
  `avenida` varchar(150) DEFAULT NULL,
  `latitud` decimal(10,8) DEFAULT NULL,
  `longitud` decimal(11,8) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  CONSTRAINT `chk_direccion_lat` CHECK (`latitud` is null or `latitud` between -90 and 90),
  CONSTRAINT `chk_direccion_lon` CHECK (`longitud` is null or `longitud` between -180 and 180)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_direccion_audit_insert AFTER INSERT ON direccion FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, new_value)
  VALUES ('INSERT','direccion',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',NEW.id,'calle',NEW.calle,'carrera',NEW.carrera,'avenida',NEW.avenida,
                      'latitud',NEW.latitud,'longitud',NEW.longitud));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_direccion_audit_update AFTER UPDATE ON direccion FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value, new_value)
  VALUES ('UPDATE','direccion',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'calle',OLD.calle,'carrera',OLD.carrera,'avenida',OLD.avenida,
                      'latitud',OLD.latitud,'longitud',OLD.longitud),
          JSON_OBJECT('id',NEW.id,'calle',NEW.calle,'carrera',NEW.carrera,'avenida',NEW.avenida,
                      'latitud',NEW.latitud,'longitud',NEW.longitud));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_direccion_audit_delete AFTER DELETE ON direccion FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value)
  VALUES ('DELETE','direccion',OLD.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'calle',OLD.calle,'carrera',OLD.carrera,'avenida',OLD.avenida,
                      'latitud',OLD.latitud,'longitud',OLD.longitud));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `direccion_servicio`
--

DROP TABLE IF EXISTS `direccion_servicio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `direccion_servicio` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `casa` varchar(50) DEFAULT NULL,
  `calle` varchar(150) DEFAULT NULL,
  `avenida` varchar(150) DEFAULT NULL,
  `latitud` decimal(10,8) DEFAULT NULL,
  `longitud` decimal(11,8) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  CONSTRAINT `chk_dir_serv_lat` CHECK (`latitud` is null or `latitud` between -90 and 90),
  CONSTRAINT `chk_dir_serv_lon` CHECK (`longitud` is null or `longitud` between -180 and 180)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_direccion_servicio_audit_insert AFTER INSERT ON direccion_servicio FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, new_value)
  VALUES ('INSERT','direccion_servicio',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',NEW.id,'casa',NEW.casa,'calle',NEW.calle,'avenida',NEW.avenida,
                      'latitud',NEW.latitud,'longitud',NEW.longitud));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_direccion_servicio_audit_update AFTER UPDATE ON direccion_servicio FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value, new_value)
  VALUES ('UPDATE','direccion_servicio',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'casa',OLD.casa,'calle',OLD.calle,'avenida',OLD.avenida,
                      'latitud',OLD.latitud,'longitud',OLD.longitud),
          JSON_OBJECT('id',NEW.id,'casa',NEW.casa,'calle',NEW.calle,'avenida',NEW.avenida,
                      'latitud',NEW.latitud,'longitud',NEW.longitud));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_direccion_servicio_audit_delete AFTER DELETE ON direccion_servicio FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value)
  VALUES ('DELETE','direccion_servicio',OLD.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'casa',OLD.casa,'calle',OLD.calle,'avenida',OLD.avenida,
                      'latitud',OLD.latitud,'longitud',OLD.longitud));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `email_log`
--

DROP TABLE IF EXISTS `email_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `email_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plantilla_id` int(11) DEFAULT NULL,
  `destinatario` varchar(120) NOT NULL,
  `asunto` varchar(150) NOT NULL,
  `cuerpo` text DEFAULT NULL,
  `estado` enum('pendiente','enviado','fallido') NOT NULL DEFAULT 'pendiente',
  `error_mensaje` varchar(255) DEFAULT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `enviado_en` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_email_plantilla` (`plantilla_id`),
  KEY `idx_email_cliente` (`cliente_id`),
  KEY `idx_email_usuario` (`usuario_id`),
  KEY `idx_email_estado` (`estado`),
  CONSTRAINT `fk_email_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_email_plantilla` FOREIGN KEY (`plantilla_id`) REFERENCES `email_plantillas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_email_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_email_log_audit_insert AFTER INSERT ON email_log FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, new_value)
  VALUES ('INSERT','email_log',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',NEW.id,'destinatario',NEW.destinatario,'asunto',NEW.asunto,'estado',NEW.estado));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_email_log_audit_update AFTER UPDATE ON email_log FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value, new_value)
  VALUES ('UPDATE','email_log',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'destinatario',OLD.destinatario,'asunto',OLD.asunto,'estado',OLD.estado),
          JSON_OBJECT('id',NEW.id,'destinatario',NEW.destinatario,'asunto',NEW.asunto,'estado',NEW.estado));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_email_log_audit_delete AFTER DELETE ON email_log FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value)
  VALUES ('DELETE','email_log',OLD.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'destinatario',OLD.destinatario,'asunto',OLD.asunto,'estado',OLD.estado));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `email_plantillas`
--

DROP TABLE IF EXISTS `email_plantillas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `email_plantillas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) NOT NULL,
  `asunto` varchar(150) NOT NULL,
  `cuerpo` text NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_plantilla_codigo` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_email_plantillas_audit_insert AFTER INSERT ON email_plantillas FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, new_value)
  VALUES ('INSERT','email_plantillas',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',NEW.id,'codigo',NEW.codigo,'asunto',NEW.asunto,'activa',NEW.activa));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_email_plantillas_audit_update AFTER UPDATE ON email_plantillas FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value, new_value)
  VALUES ('UPDATE','email_plantillas',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'codigo',OLD.codigo,'asunto',OLD.asunto,'activa',OLD.activa),
          JSON_OBJECT('id',NEW.id,'codigo',NEW.codigo,'asunto',NEW.asunto,'activa',NEW.activa));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_email_plantillas_audit_delete AFTER DELETE ON email_plantillas FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value)
  VALUES ('DELETE','email_plantillas',OLD.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'codigo',OLD.codigo,'asunto',OLD.asunto,'activa',OLD.activa));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `equipos`
--

DROP TABLE IF EXISTS `equipos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `equipos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo` enum('ONU','COMBO','ROUTER') NOT NULL,
  `marca` varchar(50) DEFAULT NULL,
  `modelo` varchar(50) DEFAULT NULL,
  `direccion_mac` char(17) NOT NULL,
  `serie` varchar(100) DEFAULT NULL,
  `puerto_nap` smallint(5) NOT NULL,
  `estado_fisico` enum('operativo','averiado','stock') DEFAULT 'stock',
  `estado_inventario` enum('stock','asignado','devuelto','averiado','baja') DEFAULT 'stock',
  `fecha_asignacion` date DEFAULT NULL,
  `fecha_devolucion` date DEFAULT NULL,
  `nap_id` int(11) NOT NULL,
  `servicio_id` int(11) DEFAULT NULL,
  `propiedad` enum('cliente','empresa') NOT NULL DEFAULT 'empresa',
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `direccion_mac` (`direccion_mac`),
  UNIQUE KEY `idx_nap_puerto` (`nap_id`,`puerto_nap`),
  UNIQUE KEY `uq_numero_serie` (`serie`),
  KEY `idx_equipo_servicio` (`servicio_id`),
  KEY `idx_equipo_estado` (`estado_inventario`),
  CONSTRAINT `fk_equipo_nap` FOREIGN KEY (`nap_id`) REFERENCES `naps` (`id`),
  CONSTRAINT `fk_equipo_srv` FOREIGN KEY (`servicio_id`) REFERENCES `servicios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_equipo_puerto` CHECK (`puerto_nap` > 0),
  CONSTRAINT `chk_equipo_mac` CHECK (`direccion_mac` regexp '^[0-9A-Fa-f]{2}(:[0-9A-Fa-f]{2}){5}$'),
  CONSTRAINT `chk_equipo_estado_servicio` CHECK (`estado_inventario` = 'stock' and `servicio_id` is null or `estado_inventario` = 'asignado' and `servicio_id` is not null or `estado_inventario` in ('devuelto','averiado','baja')),
  CONSTRAINT `chk_equipo_tipo_propiedad` CHECK (`tipo` = 'ONU' and `propiedad` = 'empresa' or `tipo` in ('COMBO','ROUTER'))
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_equipos_audit_insert AFTER INSERT ON equipos FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, new_value)
  VALUES ('INSERT','equipos',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',NEW.id,'tipo',NEW.tipo,'marca',NEW.marca,'modelo',NEW.modelo,
                      'direccion_mac',NEW.direccion_mac,'serie',NEW.serie,'puerto_nap',NEW.puerto_nap,
                      'estado_fisico',NEW.estado_fisico,'estado_inventario',NEW.estado_inventario,
                      'propiedad',NEW.propiedad,'nap_id',NEW.nap_id,'servicio_id',NEW.servicio_id));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_equipos_audit_update AFTER UPDATE ON equipos FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value, new_value)
  VALUES ('UPDATE','equipos',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'tipo',OLD.tipo,'marca',OLD.marca,'modelo',OLD.modelo,
                      'direccion_mac',OLD.direccion_mac,'serie',OLD.serie,'puerto_nap',OLD.puerto_nap,
                      'estado_fisico',OLD.estado_fisico,'estado_inventario',OLD.estado_inventario,
                      'propiedad',OLD.propiedad,'nap_id',OLD.nap_id,'servicio_id',OLD.servicio_id),
          JSON_OBJECT('id',NEW.id,'tipo',NEW.tipo,'marca',NEW.marca,'modelo',NEW.modelo,
                      'direccion_mac',NEW.direccion_mac,'serie',NEW.serie,'puerto_nap',NEW.puerto_nap,
                      'estado_fisico',NEW.estado_fisico,'estado_inventario',NEW.estado_inventario,
                      'propiedad',NEW.propiedad,'nap_id',NEW.nap_id,'servicio_id',NEW.servicio_id));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_equipos_audit_delete AFTER DELETE ON equipos FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value)
  VALUES ('DELETE','equipos',OLD.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'tipo',OLD.tipo,'marca',OLD.marca,'modelo',OLD.modelo,
                      'direccion_mac',OLD.direccion_mac,'serie',OLD.serie,'puerto_nap',OLD.puerto_nap,
                      'estado_fisico',OLD.estado_fisico,'estado_inventario',OLD.estado_inventario,
                      'propiedad',OLD.propiedad,'nap_id',OLD.nap_id,'servicio_id',OLD.servicio_id));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `factura_lineas`
--

DROP TABLE IF EXISTS `factura_lineas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `factura_lineas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `factura_id` int(11) NOT NULL,
  `servicio_id` int(11) DEFAULT NULL,
  `concepto` varchar(100) NOT NULL,
  `cantidad` decimal(10,2) NOT NULL DEFAULT 1.00,
  `precio_unitario` decimal(10,2) NOT NULL,
  `monto_linea` decimal(10,2) NOT NULL,
  `impuesto_porcentaje` decimal(5,2) NOT NULL DEFAULT 0.00,
  `impuesto_monto` decimal(10,2) NOT NULL DEFAULT 0.00,
  `descuento_monto` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_linea` decimal(10,2) NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_linea_factura` (`factura_id`),
  KEY `idx_linea_servicio` (`servicio_id`),
  CONSTRAINT `fk_linea_factura` FOREIGN KEY (`factura_id`) REFERENCES `facturas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_linea_servicio` FOREIGN KEY (`servicio_id`) REFERENCES `servicios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_factura_lineas_audit_insert AFTER INSERT ON factura_lineas FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, new_value)
  VALUES ('INSERT','factura_lineas',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',NEW.id,'factura_id',NEW.factura_id,'concepto',NEW.concepto,
                      'cantidad',NEW.cantidad,'precio_unitario',NEW.precio_unitario,'total_linea',NEW.total_linea));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_factura_lineas_audit_update AFTER UPDATE ON factura_lineas FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value, new_value)
  VALUES ('UPDATE','factura_lineas',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'factura_id',OLD.factura_id,'concepto',OLD.concepto,
                      'cantidad',OLD.cantidad,'precio_unitario',OLD.precio_unitario,'total_linea',OLD.total_linea),
          JSON_OBJECT('id',NEW.id,'factura_id',NEW.factura_id,'concepto',NEW.concepto,
                      'cantidad',NEW.cantidad,'precio_unitario',NEW.precio_unitario,'total_linea',NEW.total_linea));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_factura_lineas_audit_delete AFTER DELETE ON factura_lineas FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value)
  VALUES ('DELETE','factura_lineas',OLD.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'factura_id',OLD.factura_id,'concepto',OLD.concepto,
                      'cantidad',OLD.cantidad,'precio_unitario',OLD.precio_unitario,'total_linea',OLD.total_linea));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `facturas`
--

DROP TABLE IF EXISTS `facturas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `facturas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `servicio_id` int(11) NOT NULL,
  `periodo` varchar(7) NOT NULL,
  `monto` decimal(10,2) NOT NULL COMMENT 'Monto en USD (precio de referencia del catálogo). Independiente de la moneda de presentación.',
  `moneda` varchar(3) NOT NULL DEFAULT 'USD' COMMENT 'Divisa de presentación fiscal. USD = factura en dólares; VES = factura en bolívares al cambio BCV.',
  `tasa_cambio` decimal(10,4) DEFAULT NULL COMMENT 'Tasa BCV (Bs/USD) al momento de emisión. Obligatoria si moneda=VES, NULL si moneda=USD.',
  `impuesto_aplicado` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Porcentaje congelado al emitir',
  `fecha_emision` date NOT NULL,
  `fecha_vencimiento` date NOT NULL,
  `estado` enum('pendiente','pagada','parcial','vencida','anulada') DEFAULT 'pendiente',
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_factura_servicio_periodo` (`servicio_id`,`periodo`),
  KEY `fk_factura_servicio` (`servicio_id`),
  KEY `idx_factura_estado_vencimiento` (`estado`,`fecha_vencimiento`),
  KEY `idx_factura_periodo` (`periodo`),
  CONSTRAINT `fk_factura_servicio` FOREIGN KEY (`servicio_id`) REFERENCES `servicios` (`id`),
  CONSTRAINT `chk_fact_monto` CHECK (`monto` >= 0),
  CONSTRAINT `chk_fact_impuesto` CHECK (`impuesto_aplicado` between 0 and 100),
  CONSTRAINT `chk_fact_fechas` CHECK (`fecha_vencimiento` >= `fecha_emision`),
  CONSTRAINT `chk_facturas_periodo` CHECK (`periodo` regexp '^[0-9]{4}-[0-9]{2}$'),
  CONSTRAINT `chk_factura_moneda` CHECK (`moneda` in ('USD','VES')),
  CONSTRAINT `chk_factura_tasa` CHECK (`tasa_cambio` is null or `tasa_cambio` > 0)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_facturas_validar_insert BEFORE INSERT ON facturas FOR EACH ROW BEGIN
  IF NEW.estado <> 'pendiente' THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'INSERT de factura solo permite estado pendiente. Use UPDATE para transicionar.';
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_facturas_audit_insert AFTER INSERT ON facturas FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, new_value)
  VALUES ('INSERT','facturas',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',NEW.id,'servicio_id',NEW.servicio_id,'periodo',NEW.periodo,
                      'monto',NEW.monto,'moneda',NEW.moneda,'tasa_cambio',NEW.tasa_cambio,
                      'estado',NEW.estado,'fecha_vencimiento',NEW.fecha_vencimiento));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_facturas_validar_transicion BEFORE UPDATE ON facturas FOR EACH ROW BEGIN
  DECLARE msg VARCHAR(255);
  IF NEW.estado <> OLD.estado AND NOT EXISTS (
    SELECT 1 FROM transicion_estado
     WHERE tabla = 'facturas' AND estado_origen = OLD.estado AND estado_destino = NEW.estado
  ) THEN
    SET msg = CONCAT('Transicion no permitida en facturas: ', OLD.estado, ' -> ', NEW.estado);
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = msg;
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_facturas_audit_update AFTER UPDATE ON facturas FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value, new_value)
  VALUES ('UPDATE','facturas',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'servicio_id',OLD.servicio_id,'periodo',OLD.periodo,
                      'monto',OLD.monto,'moneda',OLD.moneda,'tasa_cambio',OLD.tasa_cambio,
                      'estado',OLD.estado,'fecha_vencimiento',OLD.fecha_vencimiento),
          JSON_OBJECT('id',NEW.id,'servicio_id',NEW.servicio_id,'periodo',NEW.periodo,
                      'monto',NEW.monto,'moneda',NEW.moneda,'tasa_cambio',NEW.tasa_cambio,
                      'estado',NEW.estado,'fecha_vencimiento',NEW.fecha_vencimiento));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_facturas_audit_delete AFTER DELETE ON facturas FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value)
  VALUES ('DELETE','facturas',OLD.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'servicio_id',OLD.servicio_id,'periodo',OLD.periodo,
                      'monto',OLD.monto,'moneda',OLD.moneda,'tasa_cambio',OLD.tasa_cambio,
                      'estado',OLD.estado,'fecha_vencimiento',OLD.fecha_vencimiento));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `metodos_pago`
--

DROP TABLE IF EXISTS `metodos_pago`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `metodos_pago` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(30) NOT NULL,
  `nombre` varchar(60) NOT NULL,
  `requiere_referencia` tinyint(1) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `orden` tinyint(3) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_metodo_codigo` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_metodos_pago_audit_insert AFTER INSERT ON metodos_pago FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, new_value)
  VALUES ('INSERT','metodos_pago',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',NEW.id,'codigo',NEW.codigo,'nombre',NEW.nombre,'activo',NEW.activo));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_metodos_pago_audit_update AFTER UPDATE ON metodos_pago FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value, new_value)
  VALUES ('UPDATE','metodos_pago',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'codigo',OLD.codigo,'nombre',OLD.nombre,'activo',OLD.activo),
          JSON_OBJECT('id',NEW.id,'codigo',NEW.codigo,'nombre',NEW.nombre,'activo',NEW.activo));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_metodos_pago_audit_delete AFTER DELETE ON metodos_pago FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value)
  VALUES ('DELETE','metodos_pago',OLD.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'codigo',OLD.codigo,'nombre',OLD.nombre,'activo',OLD.activo));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `modulos`
--

DROP TABLE IF EXISTS `modulos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `modulos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_modulo` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_modulos_audit_insert AFTER INSERT ON modulos FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, new_value)
  VALUES ('INSERT','modulos',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',NEW.id,'nombre_modulo',NEW.nombre_modulo));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_modulos_audit_update AFTER UPDATE ON modulos FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value, new_value)
  VALUES ('UPDATE','modulos',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'nombre_modulo',OLD.nombre_modulo),
          JSON_OBJECT('id',NEW.id,'nombre_modulo',NEW.nombre_modulo));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_modulos_audit_delete AFTER DELETE ON modulos FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value)
  VALUES ('DELETE','modulos',OLD.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'nombre_modulo',OLD.nombre_modulo));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `naps`
--

DROP TABLE IF EXISTS `naps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `naps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) NOT NULL,
  `cantidad_puertos_max` tinyint(4) NOT NULL DEFAULT 16,
  `ubicacion_fisica` text DEFAULT NULL,
  `latitud` decimal(10,8) NOT NULL,
  `longitud` decimal(11,8) NOT NULL,
  `olt_id` int(11) NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `fk_nap_olt` (`olt_id`),
  CONSTRAINT `fk_nap_olt` FOREIGN KEY (`olt_id`) REFERENCES `olts` (`id`),
  CONSTRAINT `chk_nap_lat` CHECK (`latitud` between -90 and 90),
  CONSTRAINT `chk_nap_lon` CHECK (`longitud` between -180 and 180),
  CONSTRAINT `chk_nap_puertos` CHECK (`cantidad_puertos_max` > 0)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_naps_audit_insert AFTER INSERT ON naps FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, new_value)
  VALUES ('INSERT','naps',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',NEW.id,'codigo',NEW.codigo,'cantidad_puertos_max',NEW.cantidad_puertos_max,
                      'ubicacion_fisica',NEW.ubicacion_fisica,'latitud',NEW.latitud,
                      'longitud',NEW.longitud,'olt_id',NEW.olt_id));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_naps_audit_update AFTER UPDATE ON naps FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value, new_value)
  VALUES ('UPDATE','naps',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'codigo',OLD.codigo,'cantidad_puertos_max',OLD.cantidad_puertos_max,
                      'ubicacion_fisica',OLD.ubicacion_fisica,'latitud',OLD.latitud,
                      'longitud',OLD.longitud,'olt_id',OLD.olt_id),
          JSON_OBJECT('id',NEW.id,'codigo',NEW.codigo,'cantidad_puertos_max',NEW.cantidad_puertos_max,
                      'ubicacion_fisica',NEW.ubicacion_fisica,'latitud',NEW.latitud,
                      'longitud',NEW.longitud,'olt_id',NEW.olt_id));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_naps_audit_delete AFTER DELETE ON naps FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value)
  VALUES ('DELETE','naps',OLD.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'codigo',OLD.codigo,'cantidad_puertos_max',OLD.cantidad_puertos_max,
                      'ubicacion_fisica',OLD.ubicacion_fisica,'latitud',OLD.latitud,
                      'longitud',OLD.longitud,'olt_id',OLD.olt_id));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `nodos`
--

DROP TABLE IF EXISTS `nodos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nodos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `ubicacion` varchar(255) DEFAULT NULL,
  `latitud` decimal(10,8) NOT NULL,
  `longitud` decimal(11,8) NOT NULL,
  `estado` enum('activo','mantenimiento','inactivo') DEFAULT 'activo',
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_nodo_nombre` (`nombre`),
  CONSTRAINT `chk_nodo_lat` CHECK (`latitud` between -90 and 90),
  CONSTRAINT `chk_nodo_lon` CHECK (`longitud` between -180 and 180)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_nodos_audit_insert AFTER INSERT ON nodos FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, new_value)
  VALUES ('INSERT','nodos',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',NEW.id,'nombre',NEW.nombre,'ubicacion',NEW.ubicacion,
                      'latitud',NEW.latitud,'longitud',NEW.longitud,'estado',NEW.estado));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_nodos_audit_update AFTER UPDATE ON nodos FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value, new_value)
  VALUES ('UPDATE','nodos',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'nombre',OLD.nombre,'ubicacion',OLD.ubicacion,
                      'latitud',OLD.latitud,'longitud',OLD.longitud,'estado',OLD.estado),
          JSON_OBJECT('id',NEW.id,'nombre',NEW.nombre,'ubicacion',NEW.ubicacion,
                      'latitud',NEW.latitud,'longitud',NEW.longitud,'estado',NEW.estado));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_nodos_audit_delete AFTER DELETE ON nodos FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value)
  VALUES ('DELETE','nodos',OLD.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'nombre',OLD.nombre,'ubicacion',OLD.ubicacion,
                      'latitud',OLD.latitud,'longitud',OLD.longitud,'estado',OLD.estado));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `notas_credito`
--

DROP TABLE IF EXISTS `notas_credito`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notas_credito` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` int(11) NOT NULL,
  `factura_id` int(11) DEFAULT NULL,
  `monto` decimal(10,2) NOT NULL,
  `moneda` varchar(10) NOT NULL DEFAULT 'USD',
  `motivo` varchar(255) NOT NULL,
  `estado` enum('emitida','aplicada','anulada') NOT NULL DEFAULT 'emitida',
  `usuario_emisor_id` int(11) DEFAULT NULL,
  `emitida_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `aplicada_en` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_nc_cliente` (`cliente_id`),
  KEY `idx_nc_factura` (`factura_id`),
  KEY `idx_nc_emisor` (`usuario_emisor_id`),
  CONSTRAINT `fk_nc_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  CONSTRAINT `fk_nc_emisor` FOREIGN KEY (`usuario_emisor_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_nc_factura` FOREIGN KEY (`factura_id`) REFERENCES `facturas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_notas_credito_audit_insert AFTER INSERT ON notas_credito FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, new_value)
  VALUES ('INSERT','notas_credito',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',NEW.id,'cliente_id',NEW.cliente_id,'factura_id',NEW.factura_id,
                      'monto',NEW.monto,'estado',NEW.estado));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_notas_credito_audit_update AFTER UPDATE ON notas_credito FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value, new_value)
  VALUES ('UPDATE','notas_credito',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'cliente_id',OLD.cliente_id,'factura_id',OLD.factura_id,
                      'monto',OLD.monto,'estado',OLD.estado),
          JSON_OBJECT('id',NEW.id,'cliente_id',NEW.cliente_id,'factura_id',NEW.factura_id,
                      'monto',NEW.monto,'estado',NEW.estado));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_notas_credito_audit_delete AFTER DELETE ON notas_credito FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value)
  VALUES ('DELETE','notas_credito',OLD.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'cliente_id',OLD.cliente_id,'factura_id',OLD.factura_id,
                      'monto',OLD.monto,'estado',OLD.estado));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `notas_debito`
--

DROP TABLE IF EXISTS `notas_debito`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notas_debito` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` int(11) NOT NULL,
  `factura_id` int(11) DEFAULT NULL,
  `monto` decimal(10,2) NOT NULL,
  `moneda` varchar(10) NOT NULL DEFAULT 'USD',
  `motivo` varchar(255) NOT NULL,
  `estado` enum('emitida','aplicada','anulada') NOT NULL DEFAULT 'emitida',
  `usuario_emisor_id` int(11) DEFAULT NULL,
  `emitida_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `aplicada_en` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_nd_cliente` (`cliente_id`),
  KEY `idx_nd_factura` (`factura_id`),
  KEY `idx_nd_emisor` (`usuario_emisor_id`),
  CONSTRAINT `fk_nd_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  CONSTRAINT `fk_nd_emisor` FOREIGN KEY (`usuario_emisor_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_nd_factura` FOREIGN KEY (`factura_id`) REFERENCES `facturas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_notas_debito_audit_insert AFTER INSERT ON notas_debito FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, new_value)
  VALUES ('INSERT','notas_debito',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',NEW.id,'cliente_id',NEW.cliente_id,'factura_id',NEW.factura_id,
                      'monto',NEW.monto,'estado',NEW.estado));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_notas_debito_audit_update AFTER UPDATE ON notas_debito FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value, new_value)
  VALUES ('UPDATE','notas_debito',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'cliente_id',OLD.cliente_id,'factura_id',OLD.factura_id,
                      'monto',OLD.monto,'estado',OLD.estado),
          JSON_OBJECT('id',NEW.id,'cliente_id',NEW.cliente_id,'factura_id',NEW.factura_id,
                      'monto',NEW.monto,'estado',NEW.estado));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_notas_debito_audit_delete AFTER DELETE ON notas_debito FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value)
  VALUES ('DELETE','notas_debito',OLD.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'cliente_id',OLD.cliente_id,'factura_id',OLD.factura_id,
                      'monto',OLD.monto,'estado',OLD.estado));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `notificaciones`
--

DROP TABLE IF EXISTS `notificaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notificaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(100) NOT NULL,
  `mensaje` varchar(255) NOT NULL,
  `fecha_envio` datetime DEFAULT current_timestamp(),
  `estado` enum('leido','no leido') DEFAULT 'no leido',
  `cliente_id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `tipo_evento` varchar(50) DEFAULT NULL,
  `url_destino` varchar(255) DEFAULT NULL,
  `leida_en` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_notif_cliente` (`cliente_id`),
  KEY `idx_notif_cliente_estado` (`cliente_id`,`estado`),
  KEY `fk_notif_usuario` (`usuario_id`),
  CONSTRAINT `fk_notif_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  CONSTRAINT `fk_notif_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_notificaciones_audit_insert AFTER INSERT ON notificaciones FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, new_value)
  VALUES ('INSERT','notificaciones',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',NEW.id,'titulo',NEW.titulo,'tipo_evento',NEW.tipo_evento,
                      'estado',NEW.estado,'cliente_id',NEW.cliente_id));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_notificaciones_audit_update AFTER UPDATE ON notificaciones FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value, new_value)
  VALUES ('UPDATE','notificaciones',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'titulo',OLD.titulo,'tipo_evento',OLD.tipo_evento,
                      'estado',OLD.estado,'cliente_id',OLD.cliente_id),
          JSON_OBJECT('id',NEW.id,'titulo',NEW.titulo,'tipo_evento',NEW.tipo_evento,
                      'estado',NEW.estado,'cliente_id',NEW.cliente_id));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_notificaciones_audit_delete AFTER DELETE ON notificaciones FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value)
  VALUES ('DELETE','notificaciones',OLD.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'titulo',OLD.titulo,'tipo_evento',OLD.tipo_evento,
                      'estado',OLD.estado,'cliente_id',OLD.cliente_id));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `notificaciones_staff`
--

DROP TABLE IF EXISTS `notificaciones_staff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notificaciones_staff` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(100) NOT NULL,
  `mensaje` varchar(255) NOT NULL,
  `tipo_evento` varchar(50) DEFAULT NULL,
  `url_destino` varchar(255) DEFAULT NULL,
  `estado` enum('leido','no leido') NOT NULL DEFAULT 'no leido',
  `usuario_id` int(11) NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `leida_en` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_notif_staff_usuario_estado` (`usuario_id`,`estado`),
  CONSTRAINT `fk_notif_staff_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_notificaciones_staff_audit_insert AFTER INSERT ON notificaciones_staff FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, new_value)
  VALUES ('INSERT','notificaciones_staff',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',NEW.id,'titulo',NEW.titulo,'tipo_evento',NEW.tipo_evento,
                      'estado',NEW.estado,'usuario_id',NEW.usuario_id));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_notificaciones_staff_audit_update AFTER UPDATE ON notificaciones_staff FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value, new_value)
  VALUES ('UPDATE','notificaciones_staff',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'titulo',OLD.titulo,'tipo_evento',OLD.tipo_evento,
                      'estado',OLD.estado,'usuario_id',OLD.usuario_id),
          JSON_OBJECT('id',NEW.id,'titulo',NEW.titulo,'tipo_evento',NEW.tipo_evento,
                      'estado',NEW.estado,'usuario_id',NEW.usuario_id));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_notificaciones_staff_audit_delete AFTER DELETE ON notificaciones_staff FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value)
  VALUES ('DELETE','notificaciones_staff',OLD.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'titulo',OLD.titulo,'tipo_evento',OLD.tipo_evento,
                      'estado',OLD.estado,'usuario_id',OLD.usuario_id));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `olts`
--

DROP TABLE IF EXISTS `olts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `olts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `marca_modelo` varchar(100) NOT NULL,
  `puertos_pon` tinyint(4) NOT NULL DEFAULT 16,
  `ip_gestion` varchar(45) DEFAULT NULL,
  `nodo_id` int(11) NOT NULL,
  `codigo` varchar(20) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_olt_codigo` (`codigo`),
  UNIQUE KEY `uq_olt_ip_gestion` (`ip_gestion`),
  KEY `fk_olt_nodo` (`nodo_id`),
  CONSTRAINT `fk_olt_nodo` FOREIGN KEY (`nodo_id`) REFERENCES `nodos` (`id`),
  CONSTRAINT `chk_olt_puertos` CHECK (`puertos_pon` > 0)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_olts_audit_insert AFTER INSERT ON olts FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, new_value)
  VALUES ('INSERT','olts',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',NEW.id,'marca_modelo',NEW.marca_modelo,'puertos_pon',NEW.puertos_pon,
                      'ip_gestion',NEW.ip_gestion,'nodo_id',NEW.nodo_id,'codigo',NEW.codigo));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_olts_audit_update AFTER UPDATE ON olts FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value, new_value)
  VALUES ('UPDATE','olts',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'marca_modelo',OLD.marca_modelo,'puertos_pon',OLD.puertos_pon,
                      'ip_gestion',OLD.ip_gestion,'nodo_id',OLD.nodo_id,'codigo',OLD.codigo),
          JSON_OBJECT('id',NEW.id,'marca_modelo',NEW.marca_modelo,'puertos_pon',NEW.puertos_pon,
                      'ip_gestion',NEW.ip_gestion,'nodo_id',NEW.nodo_id,'codigo',NEW.codigo));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_olts_audit_delete AFTER DELETE ON olts FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value)
  VALUES ('DELETE','olts',OLD.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'marca_modelo',OLD.marca_modelo,'puertos_pon',OLD.puertos_pon,
                      'ip_gestion',OLD.ip_gestion,'nodo_id',OLD.nodo_id,'codigo',OLD.codigo));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `paginas`
--

DROP TABLE IF EXISTS `paginas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `paginas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_pagina` varchar(100) NOT NULL,
  `url_pagina` varchar(255) NOT NULL,
  `modulo_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pagina_modulo` (`id`,`modulo_id`),
  KEY `fk_pagina_modulo` (`modulo_id`),
  CONSTRAINT `fk_pagina_modulo` FOREIGN KEY (`modulo_id`) REFERENCES `modulos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_paginas_audit_insert AFTER INSERT ON paginas FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, new_value)
  VALUES ('INSERT','paginas',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',NEW.id,'nombre_pagina',NEW.nombre_pagina,'url_pagina',NEW.url_pagina,'modulo_id',NEW.modulo_id));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_paginas_audit_update AFTER UPDATE ON paginas FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value, new_value)
  VALUES ('UPDATE','paginas',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'nombre_pagina',OLD.nombre_pagina,'url_pagina',OLD.url_pagina,'modulo_id',OLD.modulo_id),
          JSON_OBJECT('id',NEW.id,'nombre_pagina',NEW.nombre_pagina,'url_pagina',NEW.url_pagina,'modulo_id',NEW.modulo_id));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_paginas_audit_delete AFTER DELETE ON paginas FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value)
  VALUES ('DELETE','paginas',OLD.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'nombre_pagina',OLD.nombre_pagina,'url_pagina',OLD.url_pagina,'modulo_id',OLD.modulo_id));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `pagos`
--

DROP TABLE IF EXISTS `pagos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pagos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `factura_id` int(11) DEFAULT NULL,
  `cliente_id` int(11) NOT NULL,
  `servicio_id` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL COMMENT 'Monto en USD (precio de referencia). Independiente de la moneda_pago.',
  `moneda_pago` enum('USD','VES') NOT NULL DEFAULT 'USD' COMMENT 'Divisa en la que se realizó el pago. USD = pago en dólares; VES = pago en bolívares.',
  `tasa_cambio` decimal(10,4) DEFAULT NULL COMMENT 'Tasa BCV (Bs/USD) del día del pago. Obligatoria si moneda_pago=VES, NULL si moneda_pago=USD.',
  `fecha_pago` datetime NOT NULL,
  `metodo_id` int(11) DEFAULT NULL,
  `referencia_bancaria` varchar(255) DEFAULT NULL,
  `comprobante_path` varchar(255) DEFAULT NULL,
  `estado` enum('pendiente','validado','rechazado') DEFAULT 'pendiente',
  `motivo_rechazo` varchar(255) DEFAULT NULL,
  `fecha_validacion` datetime DEFAULT NULL,
  `usuario_valido_id` int(11) DEFAULT NULL,
  `origen` enum('portal_cliente','backoffice') DEFAULT 'backoffice',
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_pago_factura` (`factura_id`),
  KEY `fk_pago_cliente` (`cliente_id`),
  KEY `fk_pago_servicio` (`servicio_id`),
  KEY `fk_pago_usuario` (`usuario_valido_id`),
  KEY `idx_pago_estado` (`estado`),
  KEY `idx_pago_factura_estado` (`factura_id`,`estado`),
  KEY `idx_pago_fecha` (`fecha_pago`),
  KEY `idx_pago_metodo` (`metodo_id`),
  CONSTRAINT `fk_pago_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  CONSTRAINT `fk_pago_factura` FOREIGN KEY (`factura_id`) REFERENCES `facturas` (`id`),
  CONSTRAINT `fk_pago_metodo` FOREIGN KEY (`metodo_id`) REFERENCES `metodos_pago` (`id`),
  CONSTRAINT `fk_pago_servicio` FOREIGN KEY (`servicio_id`) REFERENCES `servicios` (`id`),
  CONSTRAINT `fk_pago_usuario` FOREIGN KEY (`usuario_valido_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `chk_pago_monto` CHECK (`monto` > 0),
  CONSTRAINT `chk_pago_tasa` CHECK (`tasa_cambio` is null or `tasa_cambio` > 0)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_pagos_validar_insert BEFORE INSERT ON pagos FOR EACH ROW BEGIN
  IF NEW.estado <> 'pendiente' THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'INSERT de pago solo permite estado pendiente. Use UPDATE para transicionar.';
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_pagos_audit_insert AFTER INSERT ON pagos FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, new_value)
  VALUES ('INSERT','pagos',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',NEW.id,'factura_id',NEW.factura_id,'monto',NEW.monto,
                      'moneda_pago',NEW.moneda_pago,'estado',NEW.estado));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_pagos_validar_transicion BEFORE UPDATE ON pagos FOR EACH ROW BEGIN
  DECLARE msg VARCHAR(255);
  IF NEW.estado <> OLD.estado AND NOT EXISTS (
    SELECT 1 FROM transicion_estado
     WHERE tabla = 'pagos' AND estado_origen = OLD.estado AND estado_destino = NEW.estado
  ) THEN
    SET msg = CONCAT('Transicion no permitida en pagos: ', OLD.estado, ' -> ', NEW.estado);
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = msg;
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_pagos_audit_update AFTER UPDATE ON pagos FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value, new_value)
  VALUES ('UPDATE','pagos',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'factura_id',OLD.factura_id,'monto',OLD.monto,
                      'moneda_pago',OLD.moneda_pago,'estado',OLD.estado),
          JSON_OBJECT('id',NEW.id,'factura_id',NEW.factura_id,'monto',NEW.monto,
                      'moneda_pago',NEW.moneda_pago,'estado',NEW.estado));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_pagos_audit_delete AFTER DELETE ON pagos FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value)
  VALUES ('DELETE','pagos',OLD.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'factura_id',OLD.factura_id,'monto',OLD.monto,
                      'moneda_pago',OLD.moneda_pago,'estado',OLD.estado));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `pedido_asignaciones`
--

DROP TABLE IF EXISTS `pedido_asignaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pedido_asignaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pedido_id` int(11) NOT NULL,
  `tecnico_id` int(11) NOT NULL,
  `usuario_asignador_id` int(11) DEFAULT NULL,
  `estado` enum('activa','completada','rechazada') NOT NULL DEFAULT 'activa',
  `motivo_rechazo` varchar(255) DEFAULT NULL,
  `asignado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `cerrado_en` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_asig_pedido` (`pedido_id`),
  KEY `idx_asig_tecnico` (`tecnico_id`),
  KEY `idx_asig_asignador` (`usuario_asignador_id`),
  CONSTRAINT `fk_asig_asignador` FOREIGN KEY (`usuario_asignador_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_asig_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_asig_tecnico` FOREIGN KEY (`tecnico_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_pedido_asignaciones_audit_insert AFTER INSERT ON pedido_asignaciones FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, new_value)
  VALUES ('INSERT','pedido_asignaciones',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',NEW.id,'pedido_id',NEW.pedido_id,'tecnico_id',NEW.tecnico_id,
                      'estado',NEW.estado));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_pedido_asignaciones_audit_update AFTER UPDATE ON pedido_asignaciones FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value, new_value)
  VALUES ('UPDATE','pedido_asignaciones',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'pedido_id',OLD.pedido_id,'tecnico_id',OLD.tecnico_id,
                      'estado',OLD.estado),
          JSON_OBJECT('id',NEW.id,'pedido_id',NEW.pedido_id,'tecnico_id',NEW.tecnico_id,
                      'estado',NEW.estado));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_pedido_asignaciones_audit_delete AFTER DELETE ON pedido_asignaciones FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value)
  VALUES ('DELETE','pedido_asignaciones',OLD.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'pedido_id',OLD.pedido_id,'tecnico_id',OLD.tecnico_id,
                      'estado',OLD.estado));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `pedido_materiales`
--

DROP TABLE IF EXISTS `pedido_materiales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pedido_materiales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pedido_id` int(11) NOT NULL,
  `equipo_id` int(11) DEFAULT NULL,
  `descripcion` varchar(150) NOT NULL,
  `cantidad` decimal(10,2) NOT NULL DEFAULT 1.00,
  `unidad` varchar(20) NOT NULL DEFAULT 'unidad',
  `notas` varchar(255) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_material_pedido` (`pedido_id`),
  KEY `idx_material_equipo` (`equipo_id`),
  CONSTRAINT `fk_material_equipo` FOREIGN KEY (`equipo_id`) REFERENCES `equipos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_material_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_pedido_materiales_audit_insert AFTER INSERT ON pedido_materiales FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, new_value)
  VALUES ('INSERT','pedido_materiales',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',NEW.id,'pedido_id',NEW.pedido_id,'equipo_id',NEW.equipo_id,
                      'descripcion',NEW.descripcion,'cantidad',NEW.cantidad));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_pedido_materiales_audit_update AFTER UPDATE ON pedido_materiales FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value, new_value)
  VALUES ('UPDATE','pedido_materiales',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'pedido_id',OLD.pedido_id,'equipo_id',OLD.equipo_id,
                      'descripcion',OLD.descripcion,'cantidad',OLD.cantidad),
          JSON_OBJECT('id',NEW.id,'pedido_id',NEW.pedido_id,'equipo_id',NEW.equipo_id,
                      'descripcion',NEW.descripcion,'cantidad',NEW.cantidad));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_pedido_materiales_audit_delete AFTER DELETE ON pedido_materiales FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value)
  VALUES ('DELETE','pedido_materiales',OLD.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'pedido_id',OLD.pedido_id,'equipo_id',OLD.equipo_id,
                      'descripcion',OLD.descripcion,'cantidad',OLD.cantidad));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `pedidos`
--

DROP TABLE IF EXISTS `pedidos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pedidos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` int(11) NOT NULL,
  `servicio_id` int(11) DEFAULT NULL,
  `tipo` enum('instalacion','reparacion','retiro','cambio_plan','traslado') NOT NULL,
  `estado` enum('pendiente','asignado','en_curso','completado','cancelado') NOT NULL DEFAULT 'pendiente',
  `prioridad` enum('Alta','Media','Baja') NOT NULL DEFAULT 'Media',
  `descripcion` text DEFAULT NULL,
  `usuario_creador_id` int(11) DEFAULT NULL,
  `tecnico_asignado_id` int(11) DEFAULT NULL,
  `fecha_solicitud` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_programada` date DEFAULT NULL,
  `fecha_inicio` datetime DEFAULT NULL,
  `fecha_completado` datetime DEFAULT NULL,
  `observaciones_cierre` text DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pedido_cliente` (`cliente_id`),
  KEY `idx_pedido_servicio` (`servicio_id`),
  KEY `idx_pedido_estado` (`estado`),
  KEY `idx_pedido_tecnico` (`tecnico_asignado_id`),
  KEY `idx_pedido_creador` (`usuario_creador_id`),
  CONSTRAINT `fk_pedido_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  CONSTRAINT `fk_pedido_creador` FOREIGN KEY (`usuario_creador_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_pedido_servicio` FOREIGN KEY (`servicio_id`) REFERENCES `servicios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_pedido_tecnico` FOREIGN KEY (`tecnico_asignado_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_pedidos_audit_insert AFTER INSERT ON pedidos FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, new_value)
  VALUES ('INSERT','pedidos',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',NEW.id,'cliente_id',NEW.cliente_id,'servicio_id',NEW.servicio_id,
                      'tipo',NEW.tipo,'estado',NEW.estado,'prioridad',NEW.prioridad,
                      'tecnico_asignado_id',NEW.tecnico_asignado_id));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_pedidos_audit_update AFTER UPDATE ON pedidos FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value, new_value)
  VALUES ('UPDATE','pedidos',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'cliente_id',OLD.cliente_id,'servicio_id',OLD.servicio_id,
                      'tipo',OLD.tipo,'estado',OLD.estado,'prioridad',OLD.prioridad,
                      'tecnico_asignado_id',OLD.tecnico_asignado_id),
          JSON_OBJECT('id',NEW.id,'cliente_id',NEW.cliente_id,'servicio_id',NEW.servicio_id,
                      'tipo',NEW.tipo,'estado',NEW.estado,'prioridad',NEW.prioridad,
                      'tecnico_asignado_id',NEW.tecnico_asignado_id));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_pedidos_audit_delete AFTER DELETE ON pedidos FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value)
  VALUES ('DELETE','pedidos',OLD.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'cliente_id',OLD.cliente_id,'servicio_id',OLD.servicio_id,
                      'tipo',OLD.tipo,'estado',OLD.estado,'prioridad',OLD.prioridad,
                      'tecnico_asignado_id',OLD.tecnico_asignado_id));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `permisos`
--

DROP TABLE IF EXISTS `permisos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permisos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(30) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_permiso_nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_permisos_audit_insert AFTER INSERT ON permisos FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, new_value)
  VALUES ('INSERT','permisos',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',NEW.id,'nombre',NEW.nombre));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_permisos_audit_update AFTER UPDATE ON permisos FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value, new_value)
  VALUES ('UPDATE','permisos',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'nombre',OLD.nombre),
          JSON_OBJECT('id',NEW.id,'nombre',NEW.nombre));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_permisos_audit_delete AFTER DELETE ON permisos FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value)
  VALUES ('DELETE','permisos',OLD.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'nombre',OLD.nombre));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `planes`
--

DROP TABLE IF EXISTS `planes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `planes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(60) NOT NULL,
  `velocidad_bajada_mbps` decimal(8,2) DEFAULT NULL,
  `velocidad_subida_mbps` decimal(8,2) DEFAULT NULL,
  `tecnologia` enum('FTTH','HFC','WISP','OTRO') NOT NULL DEFAULT 'FTTH',
  `precio_mensual` decimal(10,2) NOT NULL,
  `moneda` varchar(10) NOT NULL DEFAULT 'USD',
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  CONSTRAINT `chk_plan_precio` CHECK (`precio_mensual` >= 0),
  CONSTRAINT `chk_plan_moneda` CHECK (`moneda` in ('USD','VES','COP','EUR')),
  CONSTRAINT `chk_plan_velocidad_bajada` CHECK (`velocidad_bajada_mbps` is null or `velocidad_bajada_mbps` > 0),
  CONSTRAINT `chk_plan_velocidad_subida` CHECK (`velocidad_subida_mbps` is null or `velocidad_subida_mbps` > 0)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_planes_audit_insert AFTER INSERT ON planes FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, new_value)
  VALUES ('INSERT','planes',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',NEW.id,'nombre',NEW.nombre,'velocidad_bajada_mbps',NEW.velocidad_bajada_mbps,
                      'velocidad_subida_mbps',NEW.velocidad_subida_mbps,'tecnologia',NEW.tecnologia,
                      'precio_mensual',NEW.precio_mensual,'moneda',NEW.moneda,'estado',NEW.estado));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_planes_audit_update AFTER UPDATE ON planes FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value, new_value)
  VALUES ('UPDATE','planes',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'nombre',OLD.nombre,'velocidad_bajada_mbps',OLD.velocidad_bajada_mbps,
                      'velocidad_subida_mbps',OLD.velocidad_subida_mbps,'tecnologia',OLD.tecnologia,
                      'precio_mensual',OLD.precio_mensual,'moneda',OLD.moneda,'estado',OLD.estado),
          JSON_OBJECT('id',NEW.id,'nombre',NEW.nombre,'velocidad_bajada_mbps',NEW.velocidad_bajada_mbps,
                      'velocidad_subida_mbps',NEW.velocidad_subida_mbps,'tecnologia',NEW.tecnologia,
                      'precio_mensual',NEW.precio_mensual,'moneda',NEW.moneda,'estado',NEW.estado));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_planes_audit_delete AFTER DELETE ON planes FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value)
  VALUES ('DELETE','planes',OLD.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'nombre',OLD.nombre,'velocidad_bajada_mbps',OLD.velocidad_bajada_mbps,
                      'velocidad_subida_mbps',OLD.velocidad_subida_mbps,'tecnologia',OLD.tecnologia,
                      'precio_mensual',OLD.precio_mensual,'moneda',OLD.moneda,'estado',OLD.estado));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `r_cliente_telefono`
--

DROP TABLE IF EXISTS `r_cliente_telefono`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `r_cliente_telefono` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` int(11) NOT NULL,
  `telefono_id` int(11) NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cliente_telefono` (`cliente_id`,`telefono_id`),
  KEY `idx_rct_telefono` (`telefono_id`),
  CONSTRAINT `fk_rct_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rct_telefono` FOREIGN KEY (`telefono_id`) REFERENCES `telefono` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_r_cliente_telefono_audit_insert AFTER INSERT ON r_cliente_telefono FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, new_value)
  VALUES ('INSERT','r_cliente_telefono',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',NEW.id,'cliente_id',NEW.cliente_id,'telefono_id',NEW.telefono_id));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_r_cliente_telefono_audit_update AFTER UPDATE ON r_cliente_telefono FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value, new_value)
  VALUES ('UPDATE','r_cliente_telefono',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'cliente_id',OLD.cliente_id,'telefono_id',OLD.telefono_id),
          JSON_OBJECT('id',NEW.id,'cliente_id',NEW.cliente_id,'telefono_id',NEW.telefono_id));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_r_cliente_telefono_audit_delete AFTER DELETE ON r_cliente_telefono FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value)
  VALUES ('DELETE','r_cliente_telefono',OLD.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'cliente_id',OLD.cliente_id,'telefono_id',OLD.telefono_id));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `rol`
--

DROP TABLE IF EXISTS `rol`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rol` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_rol` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre_rol` (`nombre_rol`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_rol_audit_insert AFTER INSERT ON rol FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, new_value)
  VALUES ('INSERT','rol',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',NEW.id,'nombre_rol',NEW.nombre_rol,'descripcion',NEW.descripcion));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_rol_audit_update AFTER UPDATE ON rol FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value, new_value)
  VALUES ('UPDATE','rol',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'nombre_rol',OLD.nombre_rol,'descripcion',OLD.descripcion),
          JSON_OBJECT('id',NEW.id,'nombre_rol',NEW.nombre_rol,'descripcion',NEW.descripcion));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_rol_audit_delete AFTER DELETE ON rol FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value)
  VALUES ('DELETE','rol',OLD.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'nombre_rol',OLD.nombre_rol,'descripcion',OLD.descripcion));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `rol_modulo`
--

DROP TABLE IF EXISTS `rol_modulo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rol_modulo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rol_id` int(11) NOT NULL,
  `modulo_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_rol_modulo` (`rol_id`,`modulo_id`),
  UNIQUE KEY `uq_rolmodulo_modulo` (`id`,`modulo_id`),
  KEY `fk_rm_rol` (`rol_id`),
  KEY `fk_rm_modulo` (`modulo_id`),
  CONSTRAINT `fk_rm_modulo` FOREIGN KEY (`modulo_id`) REFERENCES `modulos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rm_rol` FOREIGN KEY (`rol_id`) REFERENCES `rol` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_rol_modulo_audit_insert AFTER INSERT ON rol_modulo FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, new_value)
  VALUES ('INSERT','rol_modulo',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',NEW.id,'rol_id',NEW.rol_id,'modulo_id',NEW.modulo_id));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_rol_modulo_audit_update AFTER UPDATE ON rol_modulo FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value, new_value)
  VALUES ('UPDATE','rol_modulo',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'rol_id',OLD.rol_id,'modulo_id',OLD.modulo_id),
          JSON_OBJECT('id',NEW.id,'rol_id',NEW.rol_id,'modulo_id',NEW.modulo_id));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_rol_modulo_audit_delete AFTER DELETE ON rol_modulo FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value)
  VALUES ('DELETE','rol_modulo',OLD.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'rol_id',OLD.rol_id,'modulo_id',OLD.modulo_id));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `rol_modulo_pagina`
--

DROP TABLE IF EXISTS `rol_modulo_pagina`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rol_modulo_pagina` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rol_modulo_id` int(11) NOT NULL,
  `modulo_id` int(11) NOT NULL,
  `pagina_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_rolmodulo_pagina` (`rol_modulo_id`,`pagina_id`),
  KEY `fk_rmpag_pagina` (`pagina_id`),
  CONSTRAINT `fk_rmpag_pagina` FOREIGN KEY (`pagina_id`) REFERENCES `paginas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rmpag_rolmodulo` FOREIGN KEY (`rol_modulo_id`) REFERENCES `rol_modulo` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2419 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_rol_modulo_pagina_audit_insert AFTER INSERT ON rol_modulo_pagina FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, new_value)
  VALUES ('INSERT','rol_modulo_pagina',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',NEW.id,'rol_modulo_id',NEW.rol_modulo_id,'modulo_id',NEW.modulo_id,'pagina_id',NEW.pagina_id));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_rol_modulo_pagina_audit_update AFTER UPDATE ON rol_modulo_pagina FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value, new_value)
  VALUES ('UPDATE','rol_modulo_pagina',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'rol_modulo_id',OLD.rol_modulo_id,'modulo_id',OLD.modulo_id,'pagina_id',OLD.pagina_id),
          JSON_OBJECT('id',NEW.id,'rol_modulo_id',NEW.rol_modulo_id,'modulo_id',NEW.modulo_id,'pagina_id',NEW.pagina_id));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_rol_modulo_pagina_audit_delete AFTER DELETE ON rol_modulo_pagina FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value)
  VALUES ('DELETE','rol_modulo_pagina',OLD.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'rol_modulo_id',OLD.rol_modulo_id,'modulo_id',OLD.modulo_id,'pagina_id',OLD.pagina_id));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `rol_modulo_permiso`
--

DROP TABLE IF EXISTS `rol_modulo_permiso`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rol_modulo_permiso` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rol_modulo_id` int(11) NOT NULL,
  `modulo_id` int(11) NOT NULL,
  `pagina_id` int(11) NOT NULL,
  `permiso_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_rm_permiso` (`rol_modulo_id`,`pagina_id`,`permiso_id`),
  KEY `fk_rmp_rolmodulo` (`rol_modulo_id`),
  KEY `fk_rmp_pagina` (`pagina_id`),
  KEY `fk_rmp_permiso` (`permiso_id`),
  KEY `fk_rmp_pagina_modulo` (`pagina_id`,`modulo_id`),
  KEY `fk_rmp_rolmodulo_modulo` (`rol_modulo_id`,`modulo_id`),
  CONSTRAINT `fk_rmp_pagina` FOREIGN KEY (`pagina_id`) REFERENCES `paginas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rmp_pagina_modulo` FOREIGN KEY (`pagina_id`, `modulo_id`) REFERENCES `paginas` (`id`, `modulo_id`),
  CONSTRAINT `fk_rmp_permiso` FOREIGN KEY (`permiso_id`) REFERENCES `permisos` (`id`),
  CONSTRAINT `fk_rmp_rolmodulo` FOREIGN KEY (`rol_modulo_id`) REFERENCES `rol_modulo` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rmp_rolmodulo_modulo` FOREIGN KEY (`rol_modulo_id`, `modulo_id`) REFERENCES `rol_modulo` (`id`, `modulo_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11110 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_rol_modulo_permiso_audit_insert AFTER INSERT ON rol_modulo_permiso FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, new_value)
  VALUES ('INSERT','rol_modulo_permiso',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',NEW.id,'rol_modulo_id',NEW.rol_modulo_id,'modulo_id',NEW.modulo_id,
                      'pagina_id',NEW.pagina_id,'permiso_id',NEW.permiso_id));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_rol_modulo_permiso_audit_update AFTER UPDATE ON rol_modulo_permiso FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value, new_value)
  VALUES ('UPDATE','rol_modulo_permiso',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'rol_modulo_id',OLD.rol_modulo_id,'modulo_id',OLD.modulo_id,
                      'pagina_id',OLD.pagina_id,'permiso_id',OLD.permiso_id),
          JSON_OBJECT('id',NEW.id,'rol_modulo_id',NEW.rol_modulo_id,'modulo_id',NEW.modulo_id,
                      'pagina_id',NEW.pagina_id,'permiso_id',NEW.permiso_id));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_rol_modulo_permiso_audit_delete AFTER DELETE ON rol_modulo_permiso FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value)
  VALUES ('DELETE','rol_modulo_permiso',OLD.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'rol_modulo_id',OLD.rol_modulo_id,'modulo_id',OLD.modulo_id,
                      'pagina_id',OLD.pagina_id,'permiso_id',OLD.permiso_id));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `servicios`
--

DROP TABLE IF EXISTS `servicios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `servicios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` int(11) NOT NULL,
  `alias` varchar(50) DEFAULT NULL,
  `estado_comercial` enum('activo','suspendido','retirado','pendiente') DEFAULT 'pendiente',
  `plan_id` int(11) NOT NULL,
  `nap_id` int(11) DEFAULT NULL,
  `direccion_id` int(11) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_srv_cliente` (`cliente_id`),
  KEY `fk_srv_plan` (`plan_id`),
  KEY `fk_srv_nap` (`nap_id`),
  KEY `idx_servicio_direccion` (`direccion_id`),
  CONSTRAINT `fk_servicio_direccion` FOREIGN KEY (`direccion_id`) REFERENCES `direccion_servicio` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_srv_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  CONSTRAINT `fk_srv_nap` FOREIGN KEY (`nap_id`) REFERENCES `naps` (`id`),
  CONSTRAINT `fk_srv_plan` FOREIGN KEY (`plan_id`) REFERENCES `planes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_servicios_validar_insert BEFORE INSERT ON servicios FOR EACH ROW BEGIN
  IF NEW.estado_comercial <> 'pendiente' THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'INSERT de servicio solo permite estado pendiente. Use UPDATE para transicionar.';
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_servicios_audit_insert AFTER INSERT ON servicios FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, new_value)
  VALUES ('INSERT','servicios',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',NEW.id,'cliente_id',NEW.cliente_id,'alias',NEW.alias,
                      'estado_comercial',NEW.estado_comercial,'plan_id',NEW.plan_id,'nap_id',NEW.nap_id,
                      'direccion_id',NEW.direccion_id));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_servicios_validar_transicion BEFORE UPDATE ON servicios FOR EACH ROW BEGIN
  DECLARE msg VARCHAR(255);
  IF NEW.estado_comercial <> OLD.estado_comercial AND NOT EXISTS (
    SELECT 1 FROM transicion_estado
     WHERE tabla = 'servicios' AND estado_origen = OLD.estado_comercial AND estado_destino = NEW.estado_comercial
  ) THEN
    SET msg = CONCAT('Transicion no permitida en servicios: ', OLD.estado_comercial, ' -> ', NEW.estado_comercial);
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = msg;
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_servicios_audit_update AFTER UPDATE ON servicios FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value, new_value)
  VALUES ('UPDATE','servicios',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'cliente_id',OLD.cliente_id,'alias',OLD.alias,
                      'estado_comercial',OLD.estado_comercial,'plan_id',OLD.plan_id,'nap_id',OLD.nap_id,
                      'direccion_id',OLD.direccion_id),
          JSON_OBJECT('id',NEW.id,'cliente_id',NEW.cliente_id,'alias',NEW.alias,
                      'estado_comercial',NEW.estado_comercial,'plan_id',NEW.plan_id,'nap_id',NEW.nap_id,
                      'direccion_id',NEW.direccion_id));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_servicios_audit_delete AFTER DELETE ON servicios FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value)
  VALUES ('DELETE','servicios',OLD.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'cliente_id',OLD.cliente_id,'alias',OLD.alias,
                      'estado_comercial',OLD.estado_comercial,'plan_id',OLD.plan_id,'nap_id',OLD.nap_id,
                      'direccion_id',OLD.direccion_id));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `sesiones`
--

DROP TABLE IF EXISTS `sesiones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sesiones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `token` varchar(64) NOT NULL,
  `tipo_usuario` enum('staff','cliente') NOT NULL DEFAULT 'staff',
  `usuario_id` int(11) DEFAULT NULL,
  `credencial_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `expira_en` datetime NOT NULL,
  `revocado_en` datetime DEFAULT NULL,
  `motivo_cierre` enum('logout','cambio_password','revocada_por_admin','seguridad') DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `fk_sesion_usuario` (`usuario_id`),
  KEY `fk_sesion_credencial` (`credencial_id`),
  KEY `idx_sesion_expira` (`expira_en`),
  CONSTRAINT `fk_sesion_credencial` FOREIGN KEY (`credencial_id`) REFERENCES `clientes_credenciales` (`id`),
  CONSTRAINT `fk_sesion_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `chk_sesion_revocacion` CHECK (`revocado_en` is null and `motivo_cierre` is null or `revocado_en` is not null and `motivo_cierre` is not null),
  CONSTRAINT `chk_sesiones_discriminador` CHECK (`tipo_usuario` = 'staff' and `usuario_id` is not null and `credencial_id` is null or `tipo_usuario` = 'cliente' and `credencial_id` is not null and `usuario_id` is null),
  CONSTRAINT `chk_sesiones_un_tipo` CHECK (`usuario_id` is not null and `credencial_id` is null or `usuario_id` is null and `credencial_id` is not null),
  CONSTRAINT `chk_sesion_expira` CHECK (`expira_en` > `creado_en`)
) ENGINE=InnoDB AUTO_INCREMENT=711 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_sesiones_audit_insert AFTER INSERT ON sesiones FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, new_value)
  VALUES ('INSERT','sesiones',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',NEW.id,'tipo_usuario',NEW.tipo_usuario,'usuario_id',NEW.usuario_id,
                      'credencial_id',NEW.credencial_id,'expira_en',NEW.expira_en));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_sesiones_audit_update AFTER UPDATE ON sesiones FOR EACH ROW BEGIN
  IF NOT (NEW.revocado_en <=> OLD.revocado_en) THEN
    INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value, new_value)
    VALUES ('UPDATE','sesiones',NEW.id,
            COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
            JSON_OBJECT('id',OLD.id,'revocado_en',OLD.revocado_en,'motivo_cierre',OLD.motivo_cierre),
            JSON_OBJECT('id',NEW.id,'revocado_en',NEW.revocado_en,'motivo_cierre',NEW.motivo_cierre));
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_sesiones_audit_delete AFTER DELETE ON sesiones FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value)
  VALUES ('DELETE','sesiones',OLD.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'tipo_usuario',OLD.tipo_usuario,'usuario_id',OLD.usuario_id,
                      'credencial_id',OLD.credencial_id));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `sla_definiciones`
--

DROP TABLE IF EXISTS `sla_definiciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sla_definiciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `categoria_id` int(11) NOT NULL,
  `prioridad` enum('Alta','Media','Baja') NOT NULL,
  `horas_respuesta` smallint(5) NOT NULL,
  `horas_resolucion` smallint(5) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sla_categoria_prioridad` (`categoria_id`,`prioridad`),
  CONSTRAINT `fk_sla_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias_ticket` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_sla_definiciones_audit_insert AFTER INSERT ON sla_definiciones FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, new_value)
  VALUES ('INSERT','sla_definiciones',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',NEW.id,'categoria_id',NEW.categoria_id,'prioridad',NEW.prioridad,
                      'horas_respuesta',NEW.horas_respuesta,'horas_resolucion',NEW.horas_resolucion));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_sla_definiciones_audit_update AFTER UPDATE ON sla_definiciones FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value, new_value)
  VALUES ('UPDATE','sla_definiciones',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'categoria_id',OLD.categoria_id,'prioridad',OLD.prioridad,
                      'horas_respuesta',OLD.horas_respuesta,'horas_resolucion',OLD.horas_resolucion),
          JSON_OBJECT('id',NEW.id,'categoria_id',NEW.categoria_id,'prioridad',NEW.prioridad,
                      'horas_respuesta',NEW.horas_respuesta,'horas_resolucion',NEW.horas_resolucion));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_sla_definiciones_audit_delete AFTER DELETE ON sla_definiciones FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value)
  VALUES ('DELETE','sla_definiciones',OLD.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'categoria_id',OLD.categoria_id,'prioridad',OLD.prioridad,
                      'horas_respuesta',OLD.horas_respuesta,'horas_resolucion',OLD.horas_resolucion));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `tareas`
--

DROP TABLE IF EXISTS `tareas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tareas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `estado` enum('Pendiente','En curso','Completada') NOT NULL DEFAULT 'Pendiente',
  `prioridad` enum('Alta','Media','Baja') NOT NULL DEFAULT 'Media',
  `cliente_id` int(11) DEFAULT NULL,
  `ticket_id` int(11) DEFAULT NULL,
  `servicio_id` int(11) DEFAULT NULL,
  `usuario_creador_id` int(11) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_tarea_cliente` (`cliente_id`),
  KEY `fk_tarea_usuario` (`usuario_creador_id`),
  KEY `fk_tarea_servicio` (`servicio_id`),
  KEY `fk_tarea_ticket` (`ticket_id`),
  CONSTRAINT `fk_tarea_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  CONSTRAINT `fk_tarea_servicio` FOREIGN KEY (`servicio_id`) REFERENCES `servicios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_tarea_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_tarea_usuario` FOREIGN KEY (`usuario_creador_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_tareas_audit_insert AFTER INSERT ON tareas FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, new_value)
  VALUES ('INSERT','tareas',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',NEW.id,'titulo',NEW.titulo,'estado',NEW.estado,'prioridad',NEW.prioridad,
                      'cliente_id',NEW.cliente_id,'ticket_id',NEW.ticket_id,
                      'servicio_id',NEW.servicio_id,'usuario_creador_id',NEW.usuario_creador_id));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_tareas_audit_update AFTER UPDATE ON tareas FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value, new_value)
  VALUES ('UPDATE','tareas',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'titulo',OLD.titulo,'estado',OLD.estado,'prioridad',OLD.prioridad,
                      'cliente_id',OLD.cliente_id,'ticket_id',OLD.ticket_id,
                      'servicio_id',OLD.servicio_id,'usuario_creador_id',OLD.usuario_creador_id),
          JSON_OBJECT('id',NEW.id,'titulo',NEW.titulo,'estado',NEW.estado,'prioridad',NEW.prioridad,
                      'cliente_id',NEW.cliente_id,'ticket_id',NEW.ticket_id,
                      'servicio_id',NEW.servicio_id,'usuario_creador_id',NEW.usuario_creador_id));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_tareas_audit_delete AFTER DELETE ON tareas FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value)
  VALUES ('DELETE','tareas',OLD.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'titulo',OLD.titulo,'estado',OLD.estado,'prioridad',OLD.prioridad,
                      'cliente_id',OLD.cliente_id,'ticket_id',OLD.ticket_id,
                      'servicio_id',OLD.servicio_id,'usuario_creador_id',OLD.usuario_creador_id));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `telefono`
--

DROP TABLE IF EXISTS `telefono`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `telefono` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero` varchar(25) NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_telefono_numero` (`numero`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_telefono_audit_insert AFTER INSERT ON telefono FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, new_value)
  VALUES ('INSERT','telefono',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',NEW.id,'numero',NEW.numero));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_telefono_audit_update AFTER UPDATE ON telefono FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value, new_value)
  VALUES ('UPDATE','telefono',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'numero',OLD.numero),
          JSON_OBJECT('id',NEW.id,'numero',NEW.numero));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_telefono_audit_delete AFTER DELETE ON telefono FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value)
  VALUES ('DELETE','telefono',OLD.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'numero',OLD.numero));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `ticket_respuestas`
--

DROP TABLE IF EXISTS `ticket_respuestas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ticket_respuestas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `tipo_autor` enum('staff','cliente') NOT NULL,
  `mensaje` text NOT NULL,
  `adjunto_path` varchar(255) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `credencial_id` int(11) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_respuesta_credencial` (`credencial_id`),
  KEY `fk_respuesta_ticket` (`ticket_id`),
  KEY `fk_respuesta_usuario` (`usuario_id`),
  CONSTRAINT `fk_respuesta_credencial` FOREIGN KEY (`credencial_id`) REFERENCES `clientes_credenciales` (`id`),
  CONSTRAINT `fk_respuesta_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_respuesta_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `chk_ticket_respuestas_discriminador` CHECK (`tipo_autor` = 'staff' and `usuario_id` is not null and `credencial_id` is null or `tipo_autor` = 'cliente' and `credencial_id` is not null and `usuario_id` is null)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_ticket_respuestas_audit_insert AFTER INSERT ON ticket_respuestas FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, new_value)
  VALUES ('INSERT','ticket_respuestas',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',NEW.id,'ticket_id',NEW.ticket_id,'tipo_autor',NEW.tipo_autor,
                      'usuario_id',NEW.usuario_id,'credencial_id',NEW.credencial_id));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_ticket_respuestas_audit_update AFTER UPDATE ON ticket_respuestas FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value, new_value)
  VALUES ('UPDATE','ticket_respuestas',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'ticket_id',OLD.ticket_id,'tipo_autor',OLD.tipo_autor,
                      'usuario_id',OLD.usuario_id,'credencial_id',OLD.credencial_id),
          JSON_OBJECT('id',NEW.id,'ticket_id',NEW.ticket_id,'tipo_autor',NEW.tipo_autor,
                      'usuario_id',NEW.usuario_id,'credencial_id',NEW.credencial_id));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_ticket_respuestas_audit_delete AFTER DELETE ON ticket_respuestas FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value)
  VALUES ('DELETE','ticket_respuestas',OLD.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'ticket_id',OLD.ticket_id,'tipo_autor',OLD.tipo_autor,
                      'usuario_id',OLD.usuario_id,'credencial_id',OLD.credencial_id));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `tickets`
--

DROP TABLE IF EXISTS `tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asunto` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `estado` enum('Abierto','En proceso','Cerrado') NOT NULL DEFAULT 'Abierto',
  `prioridad` enum('Alta','Media','Baja') NOT NULL DEFAULT 'Media',
  `categoria_id` int(11) DEFAULT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `servicio_id` int(11) DEFAULT NULL,
  `usuario_creador_id` int(11) DEFAULT NULL,
  `origen` enum('portal_cliente','backoffice') NOT NULL DEFAULT 'backoffice',
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `sla_vence_en` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_ticket_cliente` (`cliente_id`),
  KEY `fk_ticket_servicio` (`servicio_id`),
  KEY `fk_ticket_usuario` (`usuario_creador_id`),
  KEY `idx_tickets_estado` (`estado`),
  KEY `idx_tickets_sla` (`sla_vence_en`),
  KEY `idx_tickets_categoria` (`categoria_id`),
  CONSTRAINT `fk_ticket_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias_ticket` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_ticket_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  CONSTRAINT `fk_ticket_servicio` FOREIGN KEY (`servicio_id`) REFERENCES `servicios` (`id`),
  CONSTRAINT `fk_ticket_usuario` FOREIGN KEY (`usuario_creador_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_tickets_sla_insert BEFORE INSERT ON tickets FOR EACH ROW BEGIN
  DECLARE horas SMALLINT DEFAULT NULL;
  IF NEW.categoria_id IS NOT NULL THEN
    SELECT horas_resolucion INTO horas FROM sla_definiciones
    WHERE categoria_id = NEW.categoria_id AND prioridad = NEW.prioridad LIMIT 1;
  END IF;
  IF horas IS NOT NULL THEN
    SET NEW.sla_vence_en = DATE_ADD(NOW(), INTERVAL horas HOUR);
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_tickets_validar_insert BEFORE INSERT ON tickets FOR EACH ROW BEGIN
  IF NEW.estado <> 'Abierto' THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'INSERT de ticket solo permite estado Abierto. Use UPDATE para transicionar.';
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_tickets_audit_insert AFTER INSERT ON tickets FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, new_value)
  VALUES ('INSERT','tickets',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',NEW.id,'asunto',NEW.asunto,'estado',NEW.estado,'prioridad',NEW.prioridad,
                      'categoria_id',NEW.categoria_id,'cliente_id',NEW.cliente_id,
                      'servicio_id',NEW.servicio_id,'origen',NEW.origen));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_tickets_sla_update BEFORE UPDATE ON tickets FOR EACH ROW BEGIN
  DECLARE horas SMALLINT DEFAULT NULL;
  IF NOT (NEW.categoria_id <=> OLD.categoria_id)
     OR NOT (NEW.prioridad <=> OLD.prioridad) THEN
    IF NEW.categoria_id IS NOT NULL THEN
      SELECT horas_resolucion INTO horas FROM sla_definiciones
      WHERE categoria_id = NEW.categoria_id AND prioridad = NEW.prioridad LIMIT 1;
    END IF;
    IF horas IS NOT NULL THEN
      SET NEW.sla_vence_en = DATE_ADD(NOW(), INTERVAL horas HOUR);
    ELSE
      SET NEW.sla_vence_en = NULL;
    END IF;
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_tickets_validar_transicion BEFORE UPDATE ON tickets FOR EACH ROW BEGIN
  DECLARE msg VARCHAR(255);
  IF NEW.estado <> OLD.estado AND NOT EXISTS (
    SELECT 1 FROM transicion_estado
     WHERE tabla = 'tickets' AND estado_origen = OLD.estado AND estado_destino = NEW.estado
  ) THEN
    SET msg = CONCAT('Transicion no permitida en tickets: ', OLD.estado, ' -> ', NEW.estado);
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = msg;
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_tickets_audit_update AFTER UPDATE ON tickets FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value, new_value)
  VALUES ('UPDATE','tickets',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'asunto',OLD.asunto,'estado',OLD.estado,'prioridad',OLD.prioridad,
                      'categoria_id',OLD.categoria_id,'cliente_id',OLD.cliente_id,
                      'servicio_id',OLD.servicio_id,'origen',OLD.origen),
          JSON_OBJECT('id',NEW.id,'asunto',NEW.asunto,'estado',NEW.estado,'prioridad',NEW.prioridad,
                      'categoria_id',NEW.categoria_id,'cliente_id',NEW.cliente_id,
                      'servicio_id',NEW.servicio_id,'origen',NEW.origen));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_tickets_audit_delete AFTER DELETE ON tickets FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value)
  VALUES ('DELETE','tickets',OLD.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'asunto',OLD.asunto,'estado',OLD.estado,'prioridad',OLD.prioridad,
                      'categoria_id',OLD.categoria_id,'cliente_id',OLD.cliente_id,
                      'servicio_id',OLD.servicio_id,'origen',OLD.origen));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `transicion_estado`
--

DROP TABLE IF EXISTS `transicion_estado`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transicion_estado` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tabla` varchar(50) NOT NULL,
  `estado_origen` varchar(30) NOT NULL,
  `estado_destino` varchar(30) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_transicion` (`tabla`,`estado_origen`,`estado_destino`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_transicion_estado_audit_insert AFTER INSERT ON transicion_estado FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, new_value)
  VALUES ('INSERT','transicion_estado',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',NEW.id,'tabla',NEW.tabla,'estado_origen',NEW.estado_origen,'estado_destino',NEW.estado_destino));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_transicion_estado_audit_update AFTER UPDATE ON transicion_estado FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value, new_value)
  VALUES ('UPDATE','transicion_estado',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'tabla',OLD.tabla,'estado_origen',OLD.estado_origen,'estado_destino',OLD.estado_destino),
          JSON_OBJECT('id',NEW.id,'tabla',NEW.tabla,'estado_origen',NEW.estado_origen,'estado_destino',NEW.estado_destino));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_transicion_estado_audit_delete AFTER DELETE ON transicion_estado FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value)
  VALUES ('DELETE','transicion_estado',OLD.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'tabla',OLD.tabla,'estado_origen',OLD.estado_origen,'estado_destino',OLD.estado_destino));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `usuario_rol`
--

DROP TABLE IF EXISTS `usuario_rol`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuario_rol` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `rol_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuario_rol` (`usuario_id`,`rol_id`),
  KEY `fk_ur_usuario` (`usuario_id`),
  KEY `fk_ur_rol` (`rol_id`),
  CONSTRAINT `fk_ur_rol` FOREIGN KEY (`rol_id`) REFERENCES `rol` (`id`),
  CONSTRAINT `fk_ur_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_usuario_rol_audit_insert AFTER INSERT ON usuario_rol FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, new_value)
  VALUES ('INSERT','usuario_rol',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',NEW.id,'usuario_id',NEW.usuario_id,'rol_id',NEW.rol_id));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_usuario_rol_audit_update AFTER UPDATE ON usuario_rol FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value, new_value)
  VALUES ('UPDATE','usuario_rol',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'usuario_id',OLD.usuario_id,'rol_id',OLD.rol_id),
          JSON_OBJECT('id',NEW.id,'usuario_id',NEW.usuario_id,'rol_id',NEW.rol_id));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_usuario_rol_audit_delete AFTER DELETE ON usuario_rol FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value)
  VALUES ('DELETE','usuario_rol',OLD.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'usuario_id',OLD.usuario_id,'rol_id',OLD.rol_id));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL DEFAULT '',
  `must_change_password` tinyint(1) NOT NULL DEFAULT 0,
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `estado` enum('Activo','Inactivo','Bloqueado') NOT NULL DEFAULT 'Activo',
  `intentos_fallidos` int(11) NOT NULL DEFAULT 0,
  `bloqueado_hasta` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `uq_usuario_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_usuarios_audit_insert AFTER INSERT ON usuarios FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, new_value)
  VALUES ('INSERT','usuarios',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',NEW.id,'username',NEW.username,'email',NEW.email,
                      'estado',NEW.estado,'email_verified',NEW.email_verified,
                      'must_change_password',NEW.must_change_password));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_usuarios_audit_update AFTER UPDATE ON usuarios FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value, new_value)
  VALUES ('UPDATE','usuarios',NEW.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'username',OLD.username,'email',OLD.email,
                      'estado',OLD.estado,'email_verified',OLD.email_verified,
                      'must_change_password',OLD.must_change_password),
          JSON_OBJECT('id',NEW.id,'username',NEW.username,'email',NEW.email,
                      'estado',NEW.estado,'email_verified',NEW.email_verified,
                      'must_change_password',NEW.must_change_password));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_usuarios_audit_delete AFTER DELETE ON usuarios FOR EACH ROW BEGIN
  INSERT INTO auditoria_logs (accion, tabla_afectada, id_registro_afectado, tipo_actor, usuario_id, credencial_id, old_value)
  VALUES ('DELETE','usuarios',OLD.id,
          COALESCE(@app_tipo_actor,'sistema'), COALESCE(@app_usuario_id,NULL), COALESCE(@app_credencial_id,NULL),
          JSON_OBJECT('id',OLD.id,'username',OLD.username,'email',OLD.email,'estado',OLD.estado));
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Temporary table structure for view `v_clientes_360`
--

DROP TABLE IF EXISTS `v_clientes_360`;
/*!50001 DROP VIEW IF EXISTS `v_clientes_360`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_clientes_360` AS SELECT
 1 AS `id`,
  1 AS `cliente`,
  1 AS `cedula`,
  1 AS `correo`,
  1 AS `fecha_registro`,
  1 AS `estado_cliente`,
  1 AS `segmento`,
  1 AS `servicios_activos`,
  1 AS `tickets_abiertos`,
  1 AS `facturas_vencidas`,
  1 AS `ultima_actividad` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_equipos_asignados`
--

DROP TABLE IF EXISTS `v_equipos_asignados`;
/*!50001 DROP VIEW IF EXISTS `v_equipos_asignados`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_equipos_asignados` AS SELECT
 1 AS `id`,
  1 AS `tipo`,
  1 AS `marca`,
  1 AS `modelo`,
  1 AS `direccion_mac`,
  1 AS `puerto_nap`,
  1 AS `estado_fisico`,
  1 AS `estado_inventario`,
  1 AS `propiedad`,
  1 AS `nap_codigo`,
  1 AS `olt_codigo`,
  1 AS `nodo_nombre`,
  1 AS `servicio_id`,
  1 AS `servicio_alias`,
  1 AS `direccion_texto`,
  1 AS `cliente_id`,
  1 AS `cliente` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_estado_cuenta_cliente`
--

DROP TABLE IF EXISTS `v_estado_cuenta_cliente`;
/*!50001 DROP VIEW IF EXISTS `v_estado_cuenta_cliente`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_estado_cuenta_cliente` AS SELECT
 1 AS `id`,
  1 AS `cliente`,
  1 AS `cedula`,
  1 AS `correo`,
  1 AS `saldo_pendiente`,
  1 AS `facturas_vencidas`,
  1 AS `facturas_pagadas`,
  1 AS `ultima_fecha_vencimiento` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_facturas_con_saldo`
--

DROP TABLE IF EXISTS `v_facturas_con_saldo`;
/*!50001 DROP VIEW IF EXISTS `v_facturas_con_saldo`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_facturas_con_saldo` AS SELECT
 1 AS `id`,
  1 AS `servicio_id`,
  1 AS `periodo`,
  1 AS `monto`,
  1 AS `fecha_emision`,
  1 AS `fecha_vencimiento`,
  1 AS `estado`,
  1 AS `total_pagado`,
  1 AS `saldo`,
  1 AS `servicio_alias`,
  1 AS `cliente_id`,
  1 AS `cliente`,
  1 AS `cedula` */;
SET character_set_client = @saved_cs_client;

--
-- Dumping events for database 'bd_redytelca'
--

--
-- Dumping routines for database 'bd_redytelca'
--

--
-- Final view structure for view `v_clientes_360`
--

/*!50001 DROP VIEW IF EXISTS `v_clientes_360`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = cp850 */;
/*!50001 SET character_set_results     = cp850 */;
/*!50001 SET collation_connection      = cp850_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_clientes_360` AS select `c`.`id` AS `id`,concat(`c`.`nombre`,' ',`c`.`apellido`) AS `cliente`,`c`.`cedula` AS `cedula`,`c`.`correo` AS `correo`,`c`.`fecha_registro` AS `fecha_registro`,`c`.`estado` AS `estado_cliente`,`c`.`segmento` AS `segmento`,(select count(0) from `servicios` `s` where `s`.`cliente_id` = `c`.`id` and `s`.`estado_comercial` <> 'retirado') AS `servicios_activos`,(select count(0) from `tickets` `t` where `t`.`cliente_id` = `c`.`id` and `t`.`estado` <> 'Cerrado') AS `tickets_abiertos`,(select count(0) from (`facturas` `f` join `servicios` `s2` on(`s2`.`id` = `f`.`servicio_id`)) where `s2`.`cliente_id` = `c`.`id` and `f`.`estado` = 'vencida') AS `facturas_vencidas`,(select max(`cc`.`ultimo_acceso`) from `clientes_credenciales` `cc` where `cc`.`cliente_id` = `c`.`id`) AS `ultima_actividad` from `clientes` `c` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_equipos_asignados`
--

/*!50001 DROP VIEW IF EXISTS `v_equipos_asignados`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = cp850 */;
/*!50001 SET character_set_results     = cp850 */;
/*!50001 SET collation_connection      = cp850_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_equipos_asignados` AS select `eq`.`id` AS `id`,`eq`.`tipo` AS `tipo`,`eq`.`marca` AS `marca`,`eq`.`modelo` AS `modelo`,`eq`.`direccion_mac` AS `direccion_mac`,`eq`.`puerto_nap` AS `puerto_nap`,`eq`.`estado_fisico` AS `estado_fisico`,`eq`.`estado_inventario` AS `estado_inventario`,`eq`.`propiedad` AS `propiedad`,`na`.`codigo` AS `nap_codigo`,`o`.`codigo` AS `olt_codigo`,`n`.`nombre` AS `nodo_nombre`,`s`.`id` AS `servicio_id`,`s`.`alias` AS `servicio_alias`,`ds`.`calle` AS `direccion_texto`,`c`.`id` AS `cliente_id`,concat(`c`.`nombre`,' ',`c`.`apellido`) AS `cliente` from ((((((`equipos` `eq` join `naps` `na` on(`na`.`id` = `eq`.`nap_id`)) left join `olts` `o` on(`o`.`id` = `na`.`olt_id`)) left join `nodos` `n` on(`n`.`id` = `o`.`nodo_id`)) left join `servicios` `s` on(`s`.`id` = `eq`.`servicio_id`)) left join `direccion_servicio` `ds` on(`ds`.`id` = `s`.`direccion_id`)) left join `clientes` `c` on(`c`.`id` = `s`.`cliente_id`)) where `eq`.`estado_inventario` = 'asignado' and `eq`.`servicio_id` is not null */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_estado_cuenta_cliente`
--

/*!50001 DROP VIEW IF EXISTS `v_estado_cuenta_cliente`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = cp850 */;
/*!50001 SET character_set_results     = cp850 */;
/*!50001 SET collation_connection      = cp850_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_estado_cuenta_cliente` AS select `c`.`id` AS `id`,concat(`c`.`nombre`,' ',`c`.`apellido`) AS `cliente`,`c`.`cedula` AS `cedula`,`c`.`correo` AS `correo`,coalesce(sum(case when `f`.`estado` in ('pendiente','parcial','vencida') then greatest(`f`.`monto` - coalesce(`pv`.`total_pagado`,0),0) else 0 end),0) AS `saldo_pendiente`,count(distinct case when `f`.`estado` = 'vencida' then `f`.`id` end) AS `facturas_vencidas`,count(distinct case when `f`.`estado` = 'pagada' then `f`.`id` end) AS `facturas_pagadas`,max(`f`.`fecha_vencimiento`) AS `ultima_fecha_vencimiento` from (((`clientes` `c` left join `servicios` `s` on(`s`.`cliente_id` = `c`.`id`)) left join `facturas` `f` on(`f`.`servicio_id` = `s`.`id`)) left join (select `pagos`.`factura_id` AS `factura_id`,sum(`pagos`.`monto`) AS `total_pagado` from `pagos` where `pagos`.`estado` = 'validado' group by `pagos`.`factura_id`) `pv` on(`pv`.`factura_id` = `f`.`id`)) group by `c`.`id`,`c`.`nombre`,`c`.`apellido`,`c`.`cedula`,`c`.`correo` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_facturas_con_saldo`
--

/*!50001 DROP VIEW IF EXISTS `v_facturas_con_saldo`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = cp850 */;
/*!50001 SET character_set_results     = cp850 */;
/*!50001 SET collation_connection      = cp850_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_facturas_con_saldo` AS select `f`.`id` AS `id`,`f`.`servicio_id` AS `servicio_id`,`f`.`periodo` AS `periodo`,`f`.`monto` AS `monto`,`f`.`fecha_emision` AS `fecha_emision`,`f`.`fecha_vencimiento` AS `fecha_vencimiento`,`f`.`estado` AS `estado`,coalesce((select sum(`p`.`monto`) from `pagos` `p` where `p`.`factura_id` = `f`.`id` and `p`.`estado` = 'validado'),0) AS `total_pagado`,greatest(`f`.`monto` - coalesce((select sum(`p`.`monto`) from `pagos` `p` where `p`.`factura_id` = `f`.`id` and `p`.`estado` = 'validado'),0),0) AS `saldo`,`s`.`alias` AS `servicio_alias`,`c`.`id` AS `cliente_id`,concat(`c`.`nombre`,' ',`c`.`apellido`) AS `cliente`,`c`.`cedula` AS `cedula` from ((`facturas` `f` join `servicios` `s` on(`s`.`id` = `f`.`servicio_id`)) join `clientes` `c` on(`c`.`id` = `s`.`cliente_id`)) where `f`.`estado` in ('pendiente','parcial','vencida') */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-01 16:53:50
-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: bd_redytelca
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Dumping data for table `rol`
--

LOCK TABLES `rol` WRITE;
/*!40000 ALTER TABLE `rol` DISABLE KEYS */;
INSERT INTO `rol` VALUES (1,'Administrador',NULL),(2,'Operador',NULL),(23,'Técnico','Personal de campo. Cierra pedidos y asigna equipos al servicio.'),(24,'a',NULL);
/*!40000 ALTER TABLE `rol` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `modulos`
--

LOCK TABLES `modulos` WRITE;
/*!40000 ALTER TABLE `modulos` DISABLE KEYS */;
INSERT INTO `modulos` VALUES (1,'Principal'),(2,'Clientes'),(3,'Operación'),(4,'Configuración'),(5,'Reportes'),(6,'Infraestructura'),(7,'Finanzas');
/*!40000 ALTER TABLE `modulos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `paginas`
--

LOCK TABLES `paginas` WRITE;
/*!40000 ALTER TABLE `paginas` DISABLE KEYS */;
INSERT INTO `paginas` VALUES (1,'Inicio','dash',1),(2,'Gestión de clientes','clientes',2),(4,'Control de tareas','tareas',3),(5,'Tickets','tickets',3),(6,'Mapa','mapa',3),(7,'Usuarios','usuarios',4),(8,'Permisos','permisos',4),(9,'Cambiar contraseña','password',4),(10,'Roles','roles',4),(11,'Reportes','reportes',5),(12,'Nodos','nodos',6),(13,'OLTs','olts',6),(14,'NAPs','naps',6),(15,'Facturación','facturas',7),(16,'Pagos','pagos',7),(17,'Planes','planes',7),(18,'Contratos','contratos',7),(19,'Notificaciones','notificaciones',3),(20,'Equipos','equipos',6),(21,'Configuración de empresa','empresa',4),(22,'Servicios','servicios',2),(23,'Pedidos de campo','pedidos',3),(24,'Auditoría','auditoria',4);
/*!40000 ALTER TABLE `paginas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `permisos`
--

LOCK TABLES `permisos` WRITE;
/*!40000 ALTER TABLE `permisos` DISABLE KEYS */;
INSERT INTO `permisos` VALUES (2,'crear'),(3,'editar'),(4,'eliminar'),(1,'leer'),(5,'validar');
/*!40000 ALTER TABLE `permisos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `rol_modulo`
--

LOCK TABLES `rol_modulo` WRITE;
/*!40000 ALTER TABLE `rol_modulo` DISABLE KEYS */;
INSERT INTO `rol_modulo` VALUES (8,1,1),(9,1,2),(10,1,3),(11,1,4),(12,1,5),(13,1,6),(14,1,7),(1,2,1),(2,2,2),(3,2,3),(5,2,5),(6,2,6),(7,2,7),(28,23,1),(29,23,2),(25,23,3),(30,23,5),(26,23,6),(31,23,7);
/*!40000 ALTER TABLE `rol_modulo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `rol_modulo_pagina`
--

LOCK TABLES `rol_modulo_pagina` WRITE;
/*!40000 ALTER TABLE `rol_modulo_pagina` DISABLE KEYS */;
INSERT INTO `rol_modulo_pagina` VALUES (2165,28,1,1),(2166,29,2,2),(2167,29,2,22),(2168,25,3,5),(2169,25,3,6),(2170,25,3,23),(2171,30,5,11),(2172,26,6,12),(2173,26,6,13),(2174,26,6,14),(2175,26,6,20),(2176,31,7,17),(2240,1,1,1),(2241,2,2,2),(2242,2,2,22),(2243,3,3,4),(2244,3,3,5),(2245,3,3,6),(2246,3,3,23),(2247,5,5,11),(2248,6,6,12),(2249,6,6,13),(2250,6,6,14),(2251,6,6,20),(2252,7,7,15),(2253,7,7,16),(2254,7,7,17),(2255,7,7,18),(2393,8,1,1),(2394,9,2,2),(2395,9,2,22),(2396,10,3,4),(2397,10,3,5),(2398,10,3,6),(2399,10,3,23),(2400,11,4,7),(2401,11,4,8),(2402,11,4,9),(2403,11,4,10),(2404,11,4,21),(2405,11,4,24),(2406,12,5,11),(2407,13,6,12),(2408,13,6,13),(2409,13,6,14),(2410,13,6,20),(2411,14,7,15),(2412,14,7,16),(2413,14,7,17),(2414,14,7,18),(2416,10,3,19),(2417,3,3,19),(2418,25,3,19);
/*!40000 ALTER TABLE `rol_modulo_pagina` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `rol_modulo_permiso`
--

LOCK TABLES `rol_modulo_permiso` WRITE;
/*!40000 ALTER TABLE `rol_modulo_permiso` DISABLE KEYS */;
INSERT INTO `rol_modulo_permiso` VALUES (10271,28,1,1,1),(10272,28,1,1,2),(10273,28,1,1,3),(10274,29,2,2,1),(10275,29,2,22,1),(10276,25,3,5,1),(10277,25,3,5,2),(10278,25,3,5,3),(10279,25,3,6,1),(10280,25,3,6,2),(10281,25,3,6,3),(10282,25,3,23,1),(10283,25,3,23,2),(10284,25,3,23,3),(10285,25,3,23,4),(10286,30,5,11,1),(10287,30,5,11,2),(10288,30,5,11,3),(10289,26,6,12,1),(10290,26,6,12,2),(10291,26,6,12,3),(10292,26,6,13,1),(10293,26,6,13,2),(10294,26,6,13,3),(10295,26,6,14,1),(10296,26,6,14,2),(10297,26,6,14,3),(10298,26,6,20,1),(10299,26,6,20,2),(10300,26,6,20,3),(10301,31,7,17,1),(10302,31,7,17,2),(10303,31,7,17,3),(10535,1,1,1,1),(10536,1,1,1,2),(10537,1,1,1,3),(10538,2,2,2,1),(10539,2,2,2,2),(10540,2,2,2,3),(10541,2,2,22,1),(10542,2,2,22,2),(10543,2,2,22,3),(10544,3,3,4,1),(10545,3,3,4,2),(10546,3,3,4,3),(10547,3,3,5,1),(10548,3,3,5,2),(10549,3,3,5,3),(10550,3,3,6,1),(10551,3,3,6,2),(10552,3,3,6,3),(10553,3,3,23,1),(10554,3,3,23,2),(10555,3,3,23,3),(10556,5,5,11,1),(10557,5,5,11,2),(10558,5,5,11,3),(10559,6,6,12,1),(10560,6,6,12,2),(10561,6,6,12,3),(10562,6,6,13,1),(10563,6,6,13,2),(10564,6,6,13,3),(10565,6,6,14,1),(10566,6,6,14,2),(10567,6,6,14,3),(10568,6,6,20,1),(10569,6,6,20,2),(10570,6,6,20,3),(10571,7,7,15,1),(10572,7,7,15,2),(10573,7,7,15,3),(10574,7,7,16,1),(10575,7,7,16,2),(10576,7,7,16,3),(10577,7,7,17,1),(10578,7,7,17,2),(10579,7,7,17,3),(10580,7,7,18,1),(10581,7,7,18,2),(10582,7,7,18,3),(11028,8,1,1,1),(11029,9,2,2,1),(11030,9,2,2,2),(11031,9,2,2,3),(11032,9,2,2,4),(11033,9,2,22,1),(11034,9,2,22,2),(11035,9,2,22,3),(11036,9,2,22,4),(11037,10,3,4,1),(11038,10,3,4,2),(11039,10,3,4,3),(11040,10,3,4,4),(11041,10,3,5,1),(11042,10,3,5,2),(11043,10,3,5,3),(11044,10,3,5,4),(11045,10,3,6,1),(11046,10,3,23,1),(11047,10,3,23,2),(11048,10,3,23,3),(11049,10,3,23,4),(11050,11,4,7,1),(11051,11,4,7,2),(11052,11,4,7,3),(11053,11,4,7,4),(11054,11,4,8,1),(11055,11,4,8,2),(11056,11,4,8,3),(11057,11,4,8,4),(11058,11,4,9,1),(11059,11,4,9,3),(11060,11,4,10,1),(11061,11,4,10,2),(11062,11,4,10,3),(11063,11,4,10,4),(11064,11,4,21,1),(11065,11,4,21,3),(11066,11,4,24,1),(11067,12,5,11,1),(11068,13,6,12,1),(11069,13,6,12,2),(11070,13,6,12,3),(11071,13,6,12,4),(11072,13,6,13,1),(11073,13,6,13,2),(11074,13,6,13,3),(11075,13,6,13,4),(11076,13,6,14,1),(11077,13,6,14,2),(11078,13,6,14,3),(11079,13,6,14,4),(11080,13,6,20,1),(11081,13,6,20,2),(11082,13,6,20,3),(11083,13,6,20,4),(11084,14,7,15,1),(11085,14,7,15,2),(11086,14,7,15,3),(11087,14,7,15,4),(11088,14,7,16,1),(11089,14,7,16,2),(11090,14,7,16,3),(11091,14,7,16,4),(11092,14,7,17,1),(11093,14,7,17,2),(11094,14,7,17,3),(11095,14,7,17,4),(11096,14,7,18,1),(11097,14,7,18,2),(11098,14,7,18,3),(11099,14,7,18,4),(11102,10,3,19,1),(11103,10,3,19,3),(11104,3,3,19,1),(11105,3,3,19,2),(11106,3,3,19,3),(11107,25,3,19,1),(11108,25,3,19,2),(11109,25,3,19,3);
/*!40000 ALTER TABLE `rol_modulo_permiso` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `configuracion_empresa`
--

LOCK TABLES `configuracion_empresa` WRITE;
/*!40000 ALTER TABLE `configuracion_empresa` DISABLE KEYS */;
INSERT INTO `configuracion_empresa` VALUES (1,'REDYTELCA','uploads/empresa/logo_login.png','uploads/empresa/logo_sidebar.svg','#0c5dcf',0.00,1,5,0.00,10,'2026-10-12 19:38:26','2026-10-31 10:34:09');
/*!40000 ALTER TABLE `configuracion_empresa` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-01 16:53:55
