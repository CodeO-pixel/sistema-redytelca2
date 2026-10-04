# Plan 001 - Fix Auditoría Logs

## Archivos y responsabilidades

| Archivo | Responsabilidad | RFs cubiertos |
|---------|----------------|---------------|
| `conexion.php` | Función helper `setAuditContext(PDO, string $tipoActor, ?int $idUsuario, ?int $idCredencial)` que emite los `SET @app_*`. Llamada desde `requireStaffAuth()` y `requireClientAuth()` tras autenticación exitosa. **En T1 se añade guard `if (php_sapi_name() !== 'cli')` alrededor del bloque de headers/CORS/OPTIONS Y del catch de conexión fallida (para que en CLI lance la excepción en lugar de echo+exit), permitiendo `require_once` desde CLI sin emitir headers ni hacer `exit`.** | RF-1, RF-2, RF-3 |
| `conexion.php` (cerca de `new PDO`) | Comentario documentando variables `@app_*` por conexión, no uso de `PDO::ATTR_PERSISTENT`, y reset necesario si se habilita persistencia futura. | RNF-3 |
| `tests/audit_context.test.php` | Test unitario que hace `require_once __DIR__ . '/../conexion.php'; global $pdo;` y usa la variable `$pdo` que `conexion.php` ya deja en scope global (una sola conexión, sin duplicación). Verifica que `setAuditContext()` emite los 3 `SET` correctos con parámetros dados. Solo usa `SET @app_*` y `SELECT @app_*` — no toca tablas, no requiere transacción ni BD de test dedicada. | RNF-4 |

## Funciones puras

```php
// En conexion.php
function setAuditContext(PDO $pdo, string $tipoActor, ?int $idUsuario, ?int $idCredencial): void {
    // Emite 3 SET en una sola query multi-statement (sin placeholders: intval() directo)
    // No retorna nada; lanza excepción si falla (PDO::ERRMODE_EXCEPTION ya está activo)
}
```

- **Entradas**: conexión PDO viva, `tipoActor` ∈ {'staff','cliente'}, `idUsuario` (staff) o `idCredencial` (cliente), el otro siempre `null`.
- **Salida**: `void`. Efecto colateral: variables de sesión MySQL `@app_usuario_id`, `@app_credencial_id`, `@app_tipo_actor` seteadas en la conexión.
- **Sin estado**: no usa globals, no depende de `$_SESSION`, no lee `$_SERVER`.

## Algoritmo en pseudocódigo

```
FUNCTION setAuditContext(pdo, tipoActor, idUsuario, idCredencial):
    IF tipoActor == 'staff':
        ASSERT idUsuario != NULL
        ASSERT idCredencial == NULL
        // MySQL no soporta placeholders en SET @user_var = ? → usar intval() directo
        sql = "SET @app_usuario_id = " . intval(idUsuario) . ", @app_tipo_actor = 'staff', @app_credencial_id = NULL"
        pdo.exec(sql)
    ELSE IF tipoActor == 'cliente':
        ASSERT idCredencial != NULL
        ASSERT idUsuario == NULL
        sql = "SET @app_usuario_id = NULL, @app_tipo_actor = 'cliente', @app_credencial_id = " . intval(idCredencial)
        pdo.exec(sql)
    ELSE:
        THROW InvalidArgumentException("tipoActor inválido")
END FUNCTION

// En requireStaffAuth() tras validar sesión:
sessionData = query("SELECT id_usuario FROM sesiones ... WHERE token = ?")
setAuditContext(pdo, 'staff', sessionData.id_usuario, NULL)
RETURN sessionData

// En requireClientAuth() tras validar sesión:
sessionData = query("SELECT id_credencial FROM sesiones ... WHERE token = ?")
setAuditContext(pdo, 'cliente', NULL, sessionData.id_credencial)
RETURN sessionData
```

## Interfaz

- **No hay API HTTP nueva**. La interfaz es interna: `setAuditContext()` llamada desde los dos middlewares de auth existentes.
- **Contrato**: tras llamar a `requireStaffAuth($pdo)` o `requireClientAuth($pdo)`, la conexión `$pdo` queda con `@app_*` listas para cualquier trigger posterior.

## Decisiones justificadas

| Decisión | Alternativa descartada | Justificación |
|----------|------------------------|---------------|
| `setAuditContext()` como función separada (no inline en cada middleware) | Duplicar los 3 `SET` en `requireStaffAuth()` y `requireClientAuth()` | DRY; centraliza la lógica de auditoría; facilita test unitario (RNF-4); un solo punto de cambio si cambian variables o triggers. |
| 3 `SET` en una sola query multi-statement (`SET @a=1, @b=2, @c=3`) | 3 queries `prepare/execute` separadas | Una sola round-trip MySQL (RNF-1 latencia); atómico en la conexión; MySQL soporta asignación múltiple en un `SET`. |
| Llamar a `setAuditContext()` **dentro** de `requireStaffAuth()`/`requireClientAuth()` (no en cada endpoint) | Que cada endpoint llame a `setAuditContext()` manualmente | Cumple HU-3 (automático, centralizado, sin omisiones); los middlewares ya se ejecutan en todos los endpoints autenticados. |
| No resetear `@app_*` al abrir conexión PDO (solo comentario en RNF-3) | Ejecutar `SET @app_usuario_id=NULL, @app_credencial_id=NULL, @app_tipo_actor='sistema'` en `new PDO()` | Proyecto no usa conexiones persistentes hoy (RNF-2); resetear añade latencia innecesaria a cada request; el comentario documenta la deuda técnica para si se habilita persistencia. |
| Test unitario con PDO real contra BD del proyecto (`bd_redytelca`) | Mockear `PDO::prepare`/`execute` o asumir BD de test | Verifica SQL real emitido; detecta errores de sintaxis; `setAuditContext()` solo hace `SET @app_*` y `SELECT @app_*` — no toca tablas, no ensucia datos, no requiere transacción ni BD de test dedicada. |
| Guard CLI en headers + catch de `conexion.php` (T1) | Test duplica lógica de `setAuditContext()` sin require_once | Permite testear la función real en su archivo nativo; cambio mínimo (un `if`); no afecta runtime HTTP; evita duplicación de lógica en test. |
| Test usa `$pdo` de `conexion.php` (no crea el suyo) | Test crea su propio `new PDO(...)` | Una sola conexión por proceso de test; evita ambigüedad de scope; `conexion.php` ya configura charset, errmode y credenciales. |
| `intval()` directo en SQL (no placeholders) | `prepare('SET @x = ?')->execute([$val])` | **MySQL no soporta placeholders en `SET @user_var = ?`** (limitación del protocolo). `intval()` garantiza inyección segura para enteros. |

## Estrategia de tests (`php tests/run.php`)

1. **Test unitario `tests/audit_context.test.php`** (RNF-4):
   - Setup: `require_once __DIR__ . '/../conexion.php'; global $pdo;`
   - Caso staff: `setAuditContext($pdo, 'staff', 42, null)` → `SELECT @app_usuario_id, @app_tipo_actor, @app_credencial_id` → assert `(42, 'staff', NULL)`.
   - Caso cliente: `setAuditContext($pdo, 'cliente', null, 99)` → assert `(NULL, 'cliente', 99)`.
   - Caso error: `tipoActor='invalido'` → espera excepción.
   - Limpieza: opcional `SET @app_usuario_id=NULL, @app_tipo_actor='sistema', @app_credencial_id=NULL` al final.

2. **Test de integración manual** (Criterios de finalización #46, #49):
   - Login staff → POST `registrar_cliente.php` → `SELECT * FROM logs WHERE tabla_afectada='clientes' AND accion='INSERT' ORDER BY id_log DESC LIMIT 1` → verificar `tipo_actor='staff'`, `id_usuario` correcto.

3. **Regresión**: endpoints sin auth (ej. `login.php`) siguen funcionando y no rompen (RF-4).

## Orden de tareas (tasks.md) — TDD: test rojo → código verde

1. **T1. Crear `tests/audit_context.test.php` (rojo) y registrar en `tests/run.php` + añadir guard CLI mínimo a `conexion.php` (`if (php_sapi_name() !== 'cli')` alrededor del bloque de headers/CORS/OPTIONS Y del catch de conexión fallida) para que `require_once` funcione desde CLI**. RNF-4
   - Hecho cuando: `php tests/run.php` ejecuta el test y falla con "setAuditContext() no existe" (razón correcta, no muerte por headers/exit).

2. **T2. Implementar `setAuditContext()` en `conexion.php` (con `intval()` directo, sin placeholders)**. RF-1, RF-2, RF-3
   - Hecho cuando: `php tests/run.php` pasa (test verde).

3. **T3. Invocar `setAuditContext()` en `requireStaffAuth()` y `requireClientAuth()`**. RF-1, RF-2, RF-3
   - Hecho cuando: test sigue verde; inspección de código confirma llamada tras auth exitosa.

4. **T4. Añadir comentario RNF-3 junto a `new PDO(...)` en `conexion.php`**. RNF-3
   - Hecho cuando: comentario presente y describe los 3 puntos requeridos.

5. **T5. Prueba manual end-to-end staff → registrar_cliente.php → verificar logs**. Criterio finalización #49
   - Hecho cuando: log muestra `tabla_afectada='clientes'`, `accion='INSERT'`, `tipo_actor='staff'`, `id_usuario` correcto.
