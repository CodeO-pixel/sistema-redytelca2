# Plan 001 — Fix Auditoría Logs

## Archivos y responsabilidades

| Archivo | Responsabilidad | RFs |
|---------|-----------------|-----|
| `conexion.php` | Función `setAuditContext(PDO, string, ?int, ?int)`. Invocación en `requireStaffAuth()` y `requireClientAuth()`. Comentario RNF-3. Guard CLI en headers/CORS/catch. | RF-1, RF-2, RF-3, RF-6, RNF-1, RNF-2, RNF-3 |
| `tests/audit_context.test.php` | Test unitario que valida `setAuditContext()` en los tres casos (staff, cliente, tipo inválido). | RNF-4 |

## Función principal

```php
function setAuditContext(PDO $pdo, string $tipoActor, ?int $idUsuario, ?int $idCredencial): void
Entrada: $pdo (conexión viva), $tipoActor ∈ {'staff','cliente'}, $idUsuario (para staff) o $idCredencial (para cliente).

Salida: void. Efecto: variables MySQL @app_* seteadas en la conexión.

Excepciones: InvalidArgumentException si $tipoActor no es 'staff' ni 'cliente'.

Pseudocódigo
text
FUNCTION setAuditContext(pdo, tipoActor, idUsuario, idCredencial):
    IF tipoActor == 'staff':
        ASSERT idUsuario != NULL
        ASSERT idCredencial == NULL
        sql = "SET @app_usuario_id = " . intval(idUsuario)
            . ", @app_credencial_id = NULL"
            . ", @app_tipo_actor = 'staff'"
    ELSE IF tipoActor == 'cliente':
        ASSERT idCredencial != NULL
        ASSERT idUsuario == NULL
        sql = "SET @app_usuario_id = NULL"
            . ", @app_credencial_id = " . intval(idCredencial)
            . ", @app_tipo_actor = 'cliente'"
    ELSE:
        THROW InvalidArgumentException("tipoActor debe ser 'staff' o 'cliente'")

    pdo.exec(sql)
END FUNCTION
Integración en middlewares
En requireStaffAuth(), tras validar la sesión y antes del return:

php
setAuditContext($pdo, 'staff', (int) $sesion['id_usuario'], null);
En requireClientAuth(), tras validar la sesión y antes del return:

php
setAuditContext($pdo, 'cliente', null, (int) $sesion['id_credencial']);
Guard CLI
conexion.php emite headers HTTP y tiene un bloque if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS'). En CLI, $_SERVER['REQUEST_METHOD'] no existe. Se añade guard:

php
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json; charset=utf-8');
    // ... resto de headers CORS
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}
El catch (PDOException $e) de apertura de conexión también se protege: en CLI, throw $e en vez de echo json_encode(...) + exit.

Decisiones justificadas
Decisión	Alternativa descartada	Justificación
setAuditContext() como función separada	Inline en cada middleware	DRY; un solo punto de cambio si evolucionan las variables; testeable en aislamiento (RNF-4).
Una sola query SET @a=.., @b=.., @c=..	Tres $pdo->exec() separadas	Un solo round-trip (RNF-1). MySQL soporta asignación múltiple.
intval() directo en el SQL	$pdo->prepare("SET @x = ?")	MySQL/MariaDB no soporta placeholders en SET @user_var = ?. intval() garantiza enteros seguros.
Invocación dentro de los middlewares	Endpoint por endpoint	Cumple HU-3; los middlewares ya se ejecutan en todos los endpoints autenticados. Cero omisiones.
Setear contexto también en GET	Solo en POST/PUT/DELETE	Costo ≈ cero. Evita que un endpoint GET futuro que escriba quede sin contexto.
Estrategia de tests
tests/audit_context.test.php, formato del harness (return [ 'nombre' => fn ]):

Setup: require_once __DIR__ . '/../conexion.php'; global $pdo;

Caso staff: setAuditContext($pdo, 'staff', 42, null) → SELECT @app_usuario_id, @app_credencial_id, @app_tipo_actor → assert (42, null, 'staff').

Caso cliente: setAuditContext($pdo, 'cliente', null, 99) → assert (null, 99, 'cliente').

Caso error: setAuditContext($pdo, 'admin', 1, null) → assertThrows(InvalidArgumentException).

Caso fallback: SET @app_usuario_id = NULL, @app_credencial_id = NULL, @app_tipo_actor = NULL → verificar que COALESCE(@app_tipo_actor, 'sistema') = 'sistema'.

Orden de tareas
T1. Añadir guard CLI a conexion.php. Crear tests/audit_context.test.php (rojo). RNF-3, RNF-4.

T2. Implementar setAuditContext() en conexion.php. RF-1, RF-2, RF-3.

T3. Invocar setAuditContext() en requireStaffAuth() y requireClientAuth(). RF-1, RF-2, RF-6.

T4. Comentario RNF-3 junto a new PDO(...). RNF-3.

T5. Prueba manual: login staff → POST registrar_cliente.php → verificar log. Criterio finalización.