<?php
// tests/verify_136f.php
// Verificación estática de los 9 repositorios de spec 136f.
// Uso: php tests/verify_136f.php

$repoDir = __DIR__ . '/../repositories';

$expected = [
    'UsuarioRepository'              => ['table' => 'usuarios',              'methods' => ['findByUsername','findByEmail','findAllOrderedByUsername','findByEstado']],
    'RolRepository'                  => ['table' => 'rol',                   'methods' => ['findAllOrderedById','findByNombreRol']],
    'ModuloRepository'               => ['table' => 'modulos',               'methods' => ['findAllOrderedById']],
    'PaginaRepository'               => ['table' => 'paginas',               'methods' => ['findAllOrderedByModulo','findByModuloId','findByUrlPagina']],
    'PermisoRepository'              => ['table' => 'permisos',              'methods' => ['findAllOrderedById','findByNombre']],
    'SesionRepository'               => ['table' => 'sesiones',              'methods' => ['findByToken','findVigenteByToken','findByUsuarioId','findByCredencialId','revocarByToken','deleteExpiradasByUsuarioId']],
    'ConfiguracionEmpresaRepository' => ['table' => 'configuracion_empresa', 'methods' => ['findSingleton','findPublicBranding']],
    'NotificacionRepository'         => ['table' => 'notificaciones',        'methods' => ['findByClienteId','countNoLeidasByClienteId']],
    'NotificacionStaffRepository'    => ['table' => 'notificaciones_staff',  'methods' => ['findByUsuarioId','countNoLeidasByUsuarioId']],
];

$fail = 0;

foreach ($expected as $class => $spec) {
    $file = "$repoDir/$class.php";
    echo "\n▶ $class\n";

    if (!file_exists($file)) {
        echo "  ✗ archivo no existe\n";
        $fail++;
        continue;
    }

    $src = file_get_contents($file);

    $out = [];
    $ret = 0;
    exec('php -l ' . escapeshellarg($file) . ' 2>&1', $out, $ret);
    if ($ret !== 0) {
        echo "  ✗ php -l falló: " . implode(' ', $out) . "\n";
        $fail++;
        continue;
    }
    echo "  ✓ sintaxis OK\n";

    if (strpos($src, 'declare(strict_types=1);') === false) { echo "  ✗ falta declare(strict_types=1)\n"; $fail++; }
    if (strpos($src, 'namespace App\\Repositories;') === false) { echo "  ✗ namespace incorrecto\n"; $fail++; }
    if (substr_count($src, 'use App\\Core\\BaseRepository;') !== 1) { echo "  ✗ use App\\Core\\BaseRepository; debe aparecer 1 vez\n"; $fail++; }

    if (strpos($src, "final class $class extends BaseRepository") === false) {
        echo "  ✗ no declara final class $class extends BaseRepository\n";
        $fail++;
    }

    if (strpos($src, "protected string \$table = '{$spec['table']}';") === false) {
        echo "  ✗ \$table no es '{$spec['table']}'\n";
        $fail++;
    }
    if (strpos($src, "protected string \$primaryKey = 'id';") === false) {
        echo "  ✗ \$primaryKey no es 'id'\n";
        $fail++;
    }

    foreach ($spec['methods'] as $m) {
        if (strpos($src, "function $m(") === false) {
            echo "  ✗ falta método $m\n";
            $fail++;
        }
    }

    if (substr(rtrim($src), -2) === '?>') { echo "  ✗ termina con ?>\n"; $fail++; }
    if (preg_match('/protected\s+\?int\s+\$id\s*=/', $src)) { echo "  ✗ redeclara \$id\n"; $fail++; }
    if (preg_match('/function\s+(get|set)Id\s*\(/', $src)) { echo "  ✗ redeclara getId/setId\n"; $fail++; }

    if (preg_match('/\bid_(cliente|servicio|factura|plan|nap|olt|nodo|usuario|credencial|rol|modulo|pagina|permiso|metodo|pedido|equipo|ticket|tarea|categoria|sla|config)\b/', $src)) {
        echo "  ✗ usa columna vieja con prefijo id_ (viola ADR 016)\n";
        $fail++;
    }

    echo "  ✓ contrato base verificado\n";
}

echo "\n▶ Fixes específicos\n";

$sesion = @file_get_contents("$repoDir/SesionRepository.php");
if ($sesion && strpos($sesion, 'return $this->findByToken($token) !== null;') !== false) {
    echo "  ✓ §2.10: revocarByToken verifica con findByToken, no rowCount\n";
} else {
    echo "  ✗ §2.10: revocarByToken NO aplicó el fix (debe retornar findByToken !== null)\n";
    $fail++;
}

$servicio = @file_get_contents("$repoDir/ServicioRepository.php");
if ($servicio && strpos($servicio, "estado_comercial != 'retirado'") !== false) {
    echo "  ✓ §2.8: countByNapId filtra estado_comercial != 'retirado'\n";
} else {
    echo "  ✗ §2.8: countByNapId NO filtra estado_comercial (bug de 136b)\n";
    $fail++;
}

$usuario = @file_get_contents("$repoDir/UsuarioRepository.php");
if ($usuario && (strpos($usuario, 'password_hash') !== false || strpos($usuario, 'password_verify') !== false)) {
    echo "  ✗ seguridad: UsuarioRepository contiene password_hash/password_verify\n";
    $fail++;
} else {
    echo "  ✓ seguridad: UsuarioRepository no transforma password\n";
}

echo "\n▶ Indentación (excluye líneas de docblock y llaves de cierre)\n";
foreach (array_keys($expected) as $class) {
    $file = "$repoDir/$class.php";
    if (!file_exists($file)) continue;
    $lines = file($file, FILE_IGNORE_NEW_LINES);
    $bad = [];
    foreach ($lines as $i => $line) {
        $trimmed = ltrim($line);
        // Saltar líneas en blanco, tags de PHP, namespace/use, docblocks,
        // llaves de cierre y líneas de atributos.
        if ($trimmed === ''
            || $trimmed === '<?php'
            || strpos($trimmed, 'declare(') === 0
            || strpos($trimmed, 'namespace ') === 0
            || strpos($trimmed, 'use ') === 0
            || strpos($trimmed, '/**') === 0
            || strpos($trimmed, '*/') === 0
            || strpos($trimmed, '*') === 0
            || strpos($trimmed, '}') === 0
        ) {
            continue;
        }
        $spaces = strlen($line) - strlen(ltrim($line, ' '));
        if ($spaces % 4 !== 0) {
            $bad[] = ($i + 1) . ": '" . substr($trimmed, 0, 50) . "'";
        }
    }
    if (!empty($bad)) {
        echo "  ✗ $class: " . count($bad) . " línea(s) con indentación no múltiplo de 4\n";
        foreach (array_slice($bad, 0, 5) as $b) echo "      $b\n";
        $fail++;
    } else {
        echo "  ✓ $class\n";
    }
}

echo "\n────────────────────────\n";
echo $fail === 0 ? "TODO OK\n" : "$fail problema(s) detectado(s)\n";
exit($fail === 0 ? 0 : 1);