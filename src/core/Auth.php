<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Clase Auth
 *
 * Middleware de autenticación y autorización para el proyecto REDYTELCA.
 * Gestiona la validación de tokens, control de acceso basado en roles (RBAC)
 * y el contexto de auditoría en la base de datos.
 */
final class Auth
{
    /**
     * Constructor privado para evitar instanciación.
     */
    private function __construct()
    {
    }

    /**
     * Establece el contexto de auditoría en la conexión PDO actual.
     *
     * @param \PDO $pdo Conexión a la base de datos.
     * @param string $tipoActor Tipo de actor ('staff' o 'cliente').
     * @param int|null $idUsuario ID del usuario (si es staff).
     * @param int|null $idCredencial ID de la credencial (si es cliente).
     * @return void
     * @throws \InvalidArgumentException Si el tipo de actor no es válido.
     */
    public static function setAuditContext(\PDO $pdo, string $tipoActor, ?int $idUsuario, ?int $idCredencial): void
    {
        if ($tipoActor !== 'staff' && $tipoActor !== 'cliente') {
            throw new \InvalidArgumentException("tipoActor debe ser 'staff' o 'cliente'");
        }

        $u = $idUsuario !== null ? (int) $idUsuario : 'NULL';
        $c = $idCredencial !== null ? (int) $idCredencial : 'NULL';
        $t = $pdo->quote($tipoActor);

        $pdo->exec("SET @app_usuario_id = $u, @app_credencial_id = $c, @app_tipo_actor = $t");
    }

    /**
     * Carga los IDs de los roles asignados a un usuario.
     *
     * @param \PDO $pdo Conexión a la base de datos.
     * @param int $idUsuario ID del usuario.
     * @return array<int> Lista de IDs de roles.
     */
    public static function loadUserRoles(\PDO $pdo, int $idUsuario): array
    {
        if ($idUsuario <= 0) {
            return [];
        }

        $stmt = $pdo->prepare("SELECT rol_id FROM usuario_rol WHERE usuario_id = ? ORDER BY rol_id");
        $stmt->execute([$idUsuario]);

        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN, 0));
    }

    /**
     * Carga los nombres de los roles asignados a un usuario.
     *
     * @param \PDO $pdo Conexión a la base de datos.
     * @param int $idUsuario ID del usuario.
     * @return array<string> Lista de nombres de roles.
     */
    public static function loadUserRoleNames(\PDO $pdo, int $idUsuario): array
    {
        if ($idUsuario <= 0) {
            return [];
        }

        $stmt = $pdo->prepare("
            SELECT r.nombre_rol
            FROM usuario_rol ur
            INNER JOIN rol r ON r.id = ur.rol_id
            WHERE ur.usuario_id = ?
            ORDER BY r.id
        ");
        $stmt->execute([$idUsuario]);

        return $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);
    }

    /**
     * Valida la autenticación de un miembro del staff.
     *
     * @param \PDO $pdo Conexión a la base de datos.
     * @param int|null $minRoleId ID de rol mínimo requerido (opcional).
     * @param bool $bypassMustChangePassword Si es true, se salta el check de
     *   must_change_password. Solo debe usarse desde cambiar_password.php.
     *   El resto de la verificación (token vigente, usuario activo, roles,
     *   minRoleId) se mantiene.
     * @return array Datos de la sesión del staff.
     */
    public static function requireStaffAuth(\PDO $pdo, ?int $minRoleId = null, bool $bypassMustChangePassword = false): array
    {
        $token = self::getTokenFromHeader();

        if ($token === null) {
            self::jsonError('No autenticado. Inicia sesión nuevamente.', 401);
        }

        $stmt = $pdo->prepare("
            SELECT s.id, s.usuario_id, u.username, u.must_change_password
            FROM sesiones s
            INNER JOIN usuarios u ON u.id = s.usuario_id
            WHERE s.token = ?
              AND (s.tipo_usuario = 'staff' OR s.tipo_usuario IS NULL)
              AND s.expira_en > NOW()
              AND s.revocado_en IS NULL
        ");
        $stmt->execute([$token]);
        $sesion = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$sesion) {
            self::jsonError('Sesión inválida o expirada.', 401);
        }

        if (!$bypassMustChangePassword && isset($sesion['must_change_password']) && (int) $sesion['must_change_password'] === 1) {
            self::jsonError('Debes cambiar tu contraseña antes de continuar.', 403);
        }

        $idUsuario = (int) $sesion['usuario_id'];
        $roles = self::loadUserRoles($pdo, $idUsuario);

        if (empty($roles)) {
            self::jsonError('Tu cuenta no tiene roles asignados. Contacta a un Administrador.', 403);
        }

        if ($minRoleId !== null) {
            $hasMinRole = false;
            foreach ($roles as $r) {
                if ($r <= $minRoleId) {
                    $hasMinRole = true;
                    break;
                }
            }
            if (!$hasMinRole) {
                self::jsonError('No tienes permisos suficientes para esta acción.', 403);
            }
        }

        self::setAuditContext($pdo, 'staff', $idUsuario, null);

        return [
            'id_usuario' => $idUsuario,
            'username' => $sesion['username'],
            'roles' => $roles,
            'es_admin' => in_array(1, $roles, true),
        ];
    }

    /**
     * Valida la autenticación de un cliente en el portal.
     *
     * @param \PDO $pdo Conexión a la base de datos.
     * @param bool $bypassMustChangePassword Si es true, se salta el check de
     *   must_change_password. Solo debe usarse desde api_portal_logout.php,
     *   api_portal_auth_state.php y api_portal_cambiar_password.php.
     *   El resto de la verificación (token vigente, credencial activa,
     *   cliente no inactivo) se mantiene.
     * @return array Datos de la sesión del cliente.
     */
    public static function requireClientAuth(\PDO $pdo, bool $bypassMustChangePassword = false): array
    {
        $token = self::getTokenFromHeader();

        if ($token === null) {
            self::jsonError('No autenticado. Inicia sesión nuevamente.', 401);
        }

        $stmt = $pdo->prepare("
            SELECT s.id, s.credencial_id, cc.cliente_id, cc.must_change_password
            FROM sesiones s
            INNER JOIN clientes_credenciales cc ON cc.id = s.credencial_id
            WHERE s.token = ?
              AND s.tipo_usuario = 'cliente'
              AND s.expira_en > NOW()
              AND s.revocado_en IS NULL
        ");
        $stmt->execute([$token]);
        $sesion = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$sesion) {
            self::jsonError('Sesión inválida o expirada.', 401);
        }

        if (!$bypassMustChangePassword && isset($sesion['must_change_password']) && (int) $sesion['must_change_password'] === 1) {
            self::jsonError('Debes cambiar tu contraseña antes de continuar.', 403);
        }

        $idCredencial = (int) $sesion['credencial_id'];
        $idCliente = (int) $sesion['cliente_id'];

        self::setAuditContext($pdo, 'cliente', null, $idCredencial);

        return [
            'id_credencial' => $idCredencial,
            'id_cliente' => $idCliente,
        ];
    }

    /**
     * Valida que el usuario tenga visibilidad sobre una página (Nivel 1 RBAC).
     *
     * @param \PDO $pdo Conexión a la base de datos.
     * @param string $urlPagina URL o identificador de la página.
     * @return array Datos de la sesión del staff.
     */
    public static function requirePageAccess(\PDO $pdo, string $urlPagina): array
    {
        $staffSession = self::requireStaffAuth($pdo);

        // Guard SoD (Separación de Funciones)
        $stmtModuloSoD = $pdo->prepare("SELECT modulo_id FROM paginas WHERE url_pagina = ?");
        $stmtModuloSoD->execute([$urlPagina]);
        $idModuloPaginaSoD = $stmtModuloSoD->fetchColumn();

        if ($idModuloPaginaSoD !== false && (int) $idModuloPaginaSoD === 4 && !in_array(1, $staffSession['roles'], true)) {
            self::jsonError('Acceso denegado. La gestión de la configuración del sistema está reservada exclusivamente al rol Administrador por políticas de Separación de Funciones (SoD).', 403);
        }

        $placeholders = implode(',', array_fill(0, count($staffSession['roles']), '?'));
        $sql = "
            SELECT 1
            FROM rol_modulo_pagina rmpag
            INNER JOIN rol_modulo rm ON rm.id = rmpag.rol_modulo_id
            INNER JOIN paginas p ON p.id = rmpag.pagina_id
            WHERE rm.rol_id IN ($placeholders) AND p.url_pagina = ?
            LIMIT 1
        ";

        $params = array_merge($staffSession['roles'], [$urlPagina]);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        if (!$stmt->fetch()) {
            self::jsonError('Tu rol no tiene habilitado este módulo. Pide a un Administrador que active esta vista desde Configuración > Permisos.', 403);
        }

        return $staffSession;
    }

    /**
     * Valida que el usuario tenga permiso para una operación específica en una página (Nivel 2 RBAC).
     *
     * @param \PDO $pdo Conexión a la base de datos.
     * @param string $urlPagina URL o identificador de la página.
     * @param string $operacion Nombre de la operación (ej. 'leer', 'crear', 'editar').
     * @return array Datos de la sesión del staff.
     */
    public static function requireOperation(\PDO $pdo, string $urlPagina, string $operacion): array
    {
        $staffSession = self::requireStaffAuth($pdo);

        $placeholders = implode(',', array_fill(0, count($staffSession['roles']), '?'));
        $sql = "
            SELECT 1
            FROM rol_modulo_permiso rmp
            INNER JOIN rol_modulo rm ON rm.id = rmp.rol_modulo_id
            INNER JOIN paginas p ON p.id = rmp.pagina_id
            INNER JOIN permisos pe ON pe.id = rmp.permiso_id
            WHERE rm.rol_id IN ($placeholders) AND p.url_pagina = ? AND pe.nombre = ?
            LIMIT 1
        ";

        $params = array_merge($staffSession['roles'], [$urlPagina, $operacion]);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        if (!$stmt->fetch()) {
            self::jsonError('Tu rol no tiene el permiso "' . $operacion . '" sobre esta página. Pide a un Administrador que lo active desde Configuración > Permisos.', 403);
        }

        return $staffSession;
    }

    /**
     * Verifica una contraseña y la migra a bcrypt si está en texto plano.
     *
     * @param \PDO $pdo Conexión a la base de datos.
     * @param string $passwordIngresada Contraseña proporcionada por el usuario.
     * @param string $passwordAlmacenada Contraseña almacenada en la base de datos.
     * @param int $idUsuario ID del usuario para actualizar el hash si es necesario.
     * @return bool True si la contraseña es correcta, false en caso contrario.
     */
    public static function verificarYMigrarPassword(\PDO $pdo, string $passwordIngresada, string $passwordAlmacenada, int $idUsuario): bool
    {
        $infoHash = password_get_info($passwordAlmacenada);

        if ($infoHash['algo'] !== null && $infoHash['algo'] !== 0) {
            return password_verify($passwordIngresada, $passwordAlmacenada);
        }

        // Fallback para contraseñas legacy en texto plano
        if (hash_equals($passwordAlmacenada, $passwordIngresada)) {
            $nuevoHash = password_hash($passwordIngresada, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE usuarios SET password = ? WHERE id = ?');
            $stmt->execute([$nuevoHash, $idUsuario]);
            return true;
        }

        return false;
    }

    /**
     * Genera un hash seguro para una nueva contraseña.
     *
     * @param string $passwordPlana Contraseña en texto plano.
     * @return string Hash generado.
     */
    public static function hashearPasswordNueva(string $passwordPlana): string
    {
        return password_hash($passwordPlana, PASSWORD_DEFAULT);
    }

    /**
     * Emite una respuesta JSON de error y termina la ejecución.
     * Solo setea cabeceras si no han sido enviadas (evita warnings por
     * output previo accidental). Consistente con Response::json().
     *
     * @param string $message Mensaje de error.
     * @param int $httpStatus Código de estado HTTP.
     * @return void
     */
    private static function jsonError(string $message, int $httpStatus): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code($httpStatus);
        }
        echo json_encode(['status' => 'error', 'message' => $message], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Obtiene el token de sesión desde las cabeceras HTTP.
     *
     * @return string|null Token limpio o null si no está presente.
     */
    private static function getTokenFromHeader(): ?string
    {
        if (isset($_SERVER['HTTP_X_SESSION_TOKEN'])) {
            $token = trim((string) $_SERVER['HTTP_X_SESSION_TOKEN']);
            if ($token !== '') {
                return $token;
            }
        }
        return null;
    }
}