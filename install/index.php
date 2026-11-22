<?php
/**
 * ShopCMS - Asistente de Instalación y Reparación Web
 * Estilo guiado (WordPress-like) con diseño Polaris Shopify
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$configFile = dirname(__DIR__) . '/config.php';
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$action = $_GET['action'] ?? '';
$reason = $_GET['reason'] ?? '';
$error = isset($_GET['error']) ? urldecode($_GET['error']) : null;
$success = null;

// =========================================================================
// ACCIÓN: REINSTALACIÓN TOTAL (Eliminar config.php para empezar de nuevo)
// =========================================================================
if ($action === 'force_reinstall') {
    if (file_exists($configFile)) {
        @unlink($configFile);
    }
    unset($_SESSION['install_db'], $_SESSION['installed_store']);
    header('Location: index.php?step=1');
    exit;
}

// =========================================================================
// MODO REPARACIÓN AUTOMÁTICA
// Si config.php ya existe, verificar el estado real de la base de datos
// =========================================================================
$configExists = file_exists($configFile);
$dbHealthy = false;
$missingTables = false;
$dbConnectionError = null;

if ($configExists) {
    require_once $configFile;
    if (defined('DB_HOST') && defined('DB_NAME')) {
        try {
            $testDsn = "mysql:host=" . DB_HOST . ";port=" . (defined('DB_PORT') ? DB_PORT : '3306') . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $testPdo = new PDO($testDsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 4
            ]);
            
            // Verificar si las tablas principales existen
            $prefix = defined('DB_PREFIX') ? DB_PREFIX : 'shop_';
            $checkTable = $testPdo->query("SHOW TABLES LIKE '{$prefix}settings'")->fetch();
            if ($checkTable) {
                $dbHealthy = true;
            } else {
                $missingTables = true;
            }
        } catch (PDOException $e) {
            $dbConnectionError = $e->getMessage();
        }
    }
}

// =========================================================================
// ACCIÓN: REPARAR TABLAS (Cuando config.php existe pero las tablas faltan)
// =========================================================================
if ($action === 'execute_repair' && $configExists && ($missingTables || $dbConnectionError)) {
    try {
        // Conectar a MySQL
        $dsnHost = "mysql:host=" . DB_HOST . ";port=" . (defined('DB_PORT') ? DB_PORT : '3306') . ";charset=utf8mb4";
        $pdo = new PDO($dsnHost, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // Crear la BD si no existe
        $dbName = DB_NAME;
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        $pdo->exec("USE `{$dbName}`;");

        // Ejecutar esquema y semillas
        $prefix = defined('DB_PREFIX') ? DB_PREFIX : 'shop_';
        executeSqlStatements($pdo, __DIR__ . '/schema.sql', ['{prefix}' => $prefix]);
        executeSqlStatements($pdo, __DIR__ . '/seeds.sql', [
            '{prefix}' => $prefix,
            '{store_name}' => 'Mi Tienda ShopCMS',
            '{store_email}' => 'contacto@mitienda.com'
        ]);

        // Verificar si existe algún administrador; si no existe, enviar al Paso 3 para crearlo manualmente
        $adminCheck = $pdo->query("SELECT * FROM `{$prefix}users` WHERE role = 'admin' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if (!$adminCheck) {
            $_SESSION['install_db'] = [
                'host' => DB_HOST,
                'port' => defined('DB_PORT') ? DB_PORT : '3306',
                'name' => DB_NAME,
                'user' => DB_USER,
                'pass' => DB_PASS,
                'prefix' => $prefix,
            ];
            header('Location: index.php?step=3&error=' . urlencode('Tablas restauradas. Ahora crea la cuenta del administrador para finalizar.'));
            exit;
        }

        $_SESSION['user_id'] = $adminCheck['id'];
        $_SESSION['user_name'] = $adminCheck['name'];
        $_SESSION['user_email'] = $adminCheck['email'];
        $_SESSION['user_role'] = 'admin';
        $_SESSION['flash_success'] = 'Base de datos restaurada correctamente. Bienvenido al Panel de Administrador.';

        // Redirigir directamente al panel admin
        header('Location: ../admin');
        exit;
    } catch (Exception $e) {
        $error = "Error al reparar la base de datos: " . $e->getMessage();
    }
}

// =========================================================================
// FUNCIÓN ROBUSTA DE EJECUCIÓN SQL
// =========================================================================
function executeSqlStatements(PDO $pdo, string $filePath, array $replacements = []): void {
    if (!file_exists($filePath)) {
        throw new Exception("Archivo SQL no encontrado: " . basename($filePath));
    }

    $sqlContent = file_get_contents($filePath);
    foreach ($replacements as $search => $replace) {
        $sqlContent = str_replace($search, $replace, $sqlContent);
    }

    // Normalizar saltos de línea a \n
    $sqlContent = str_replace(["\r\n", "\r"], "\n", $sqlContent);

    // Desactivar temporalmente foreign keys
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

    // Limpiar comentarios de línea (-- ...)
    $lines = explode("\n", $sqlContent);
    $cleanSql = "";
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if (str_starts_with($trimmed, '--') || str_starts_with($trimmed, '/*') || empty($trimmed)) {
            continue;
        }
        $cleanSql .= $line . "\n";
    }

    // Separar por punto y coma (;)
    $statements = array_filter(array_map('trim', explode(';', $cleanSql)));
    foreach ($statements as $stmt) {
        if (!empty($stmt)) {
            $pdo->exec($stmt);
        }
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
}

// =========================================================================
// PASO 1: Comprobación de Requisitos
// =========================================================================
$requirements = [
    'php' => [
        'name' => 'PHP 8.0 o superior',
        'status' => version_compare(PHP_VERSION, '8.0.0', '>='),
        'current' => PHP_VERSION,
        'required' => '>= 8.0.0'
    ],
    'pdo' => [
        'name' => 'Extensión PDO',
        'status' => extension_loaded('pdo'),
        'current' => extension_loaded('pdo') ? 'Instalado' : 'No disponible',
        'required' => 'Habilitado'
    ],
    'pdo_mysql' => [
        'name' => 'Driver PDO MySQL (MariaDB)',
        'status' => extension_loaded('pdo_mysql'),
        'current' => extension_loaded('pdo_mysql') ? 'Instalado' : 'No disponible',
        'required' => 'Habilitado'
    ],
    'zip' => [
        'name' => 'Extensión ZipArchive (para Plugins .ZIP)',
        'status' => class_exists('ZipArchive'),
        'current' => class_exists('ZipArchive') ? 'Instalado' : 'No disponible',
        'required' => 'Habilitado'
    ],
    'writable_root' => [
        'name' => 'Permiso de escritura en directorio raíz',
        'status' => is_writable(dirname(__DIR__)),
        'current' => is_writable(dirname(__DIR__)) ? 'Escritura permitida' : 'Solo lectura',
        'required' => 'Lectura / Escritura'
    ],
    'writable_uploads' => [
        'name' => 'Directorio /uploads',
        'status' => is_writable(dirname(__DIR__) . '/uploads'),
        'current' => is_writable(dirname(__DIR__) . '/uploads') ? 'Escritura permitida' : 'No disponible',
        'required' => 'Lectura / Escritura'
    ],
    'writable_plugins' => [
        'name' => 'Directorio /plugins',
        'status' => is_writable(dirname(__DIR__) . '/plugins'),
        'current' => is_writable(dirname(__DIR__) . '/plugins') ? 'Escritura permitida' : 'No disponible',
        'required' => 'Lectura / Escritura'
    ]
];

$allRequirementsPassed = true;
foreach ($requirements as $req) {
    if (!$req['status']) {
        $allRequirementsPassed = false;
        break;
    }
}

// =========================================================================
// PASO 2: Procesar Conexión y Creación de BD
// =========================================================================
if ($step === 2 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost = trim($_POST['db_host'] ?? 'localhost');
    $dbPort = trim($_POST['db_port'] ?? '3306');
    $dbName = trim($_POST['db_name'] ?? 'shopcms_db');
    $dbUser = trim($_POST['db_user'] ?? 'root');
    $dbPass = $_POST['db_pass'] ?? '';
    $dbPrefix = trim($_POST['db_prefix'] ?? 'shop_');

    if (empty($dbHost) || empty($dbName) || empty($dbUser)) {
        $error = 'Por favor completa todos los campos requeridos de la base de datos.';
    } else {
        try {
            // 1. Probar conexión al servidor MySQL
            $dsn = "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4";
            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5
            ]);

            // 2. Intentar crear o verificar la BD aquí mismo en el Paso 2
            $safeDbName = preg_replace('/[^a-zA-Z0-9_]/', '', $dbName);
            if (empty($safeDbName)) {
                throw new Exception("El nombre de la base de datos contiene caracteres no válidos.");
            }

            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$safeDbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
            $pdo->exec("USE `{$safeDbName}`;");

            // Guardar configuración en la sesión
            $_SESSION['install_db'] = [
                'host' => $dbHost,
                'port' => $dbPort,
                'name' => $safeDbName,
                'user' => $dbUser,
                'pass' => $dbPass,
                'prefix' => $dbPrefix
            ];

            header('Location: index.php?step=3');
            exit;
        } catch (PDOException $e) {
            $error = 'Error de conexión MySQL: ' . $e->getMessage() . '. Verifica que el módulo MySQL esté iniciado en XAMPP.';
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// =========================================================================
// PASO 3: Procesar Instalación Completa
// =========================================================================
if ($step === 3 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $storeName = trim($_POST['store_name'] ?? '');
    $storeEmail = trim($_POST['store_email'] ?? '');
    $storeCurrency = trim($_POST['store_currency'] ?? 'USD');
    $adminName = trim($_POST['admin_name'] ?? '');
    $adminEmail = trim($_POST['admin_email'] ?? '');
    $adminPass = $_POST['admin_password'] ?? '';
    $adminPassConfirm = $_POST['admin_password_confirm'] ?? '';

    if (empty($storeName) || empty($storeEmail) || empty($adminName) || empty($adminEmail) || empty($adminPass)) {
        $error = 'Todos los campos marcados con (*) son obligatorios.';
    } elseif (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo electrónico del administrador no es válido.';
    } elseif ($adminPass !== $adminPassConfirm) {
        $error = 'Las contraseñas no coinciden.';
    } elseif (strlen($adminPass) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif (!isset($_SESSION['install_db'])) {
        $error = 'Faltan los datos de conexión de la base de datos. Por favor regresa al paso 2.';
    } else {
        $db = $_SESSION['install_db'];

        try {
            // 1. Conectar a MySQL
            $dsn = "mysql:host={$db['host']};port={$db['port']};charset=utf8mb4";
            $pdo = new PDO($dsn, $db['user'], $db['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);

            // 2. Asegurar que la BD existe y seleccionarla
            $safeDbName = $db['name'];
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$safeDbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
            $pdo->exec("USE `{$safeDbName}`;");

            // 3. Ejecutar schema.sql con nuestra función robusta
            executeSqlStatements($pdo, __DIR__ . '/schema.sql', ['{prefix}' => $db['prefix']]);

            // 4. Ejecutar seeds.sql
            executeSqlStatements($pdo, __DIR__ . '/seeds.sql', [
                '{prefix}' => $db['prefix'],
                '{store_name}' => addslashes($storeName),
                '{store_email}' => addslashes($storeEmail)
            ]);

            // 5. Insertar / Actualizar Administrador
            $passwordHash = password_hash($adminPass, PASSWORD_BCRYPT);
            $userCheck = $pdo->prepare("SELECT id FROM `{$db['prefix']}users` WHERE email = ?");
            $userCheck->execute([$adminEmail]);
            $existingAdmin = $userCheck->fetch();
            if ($existingAdmin) {
                $stmt = $pdo->prepare("UPDATE `{$db['prefix']}users` SET `name` = ?, `password` = ?, `role` = 'admin' WHERE `email` = ?");
                $stmt->execute([$adminName, $passwordHash, $adminEmail]);
                $adminId = (int)$existingAdmin['id'];
            } else {
                $stmt = $pdo->prepare("INSERT INTO `{$db['prefix']}users` (`name`, `email`, `password`, `role`) VALUES (?, ?, ?, 'admin')");
                $stmt->execute([$adminName, $adminEmail, $passwordHash]);
                $adminId = (int)$pdo->lastInsertId();
            }

            // Actualizar moneda de la tienda en settings
            $stmtUpdate = $pdo->prepare("UPDATE `{$db['prefix']}settings` SET `setting_value` = ? WHERE `setting_key` = 'store_currency'");
            $stmtUpdate->execute([$storeCurrency]);

            // 6. Verificar que las tablas realmente existen ANTES de escribir config.php
            $verifyTable = $pdo->query("SHOW TABLES LIKE '{$db['prefix']}products'")->fetch();
            if (!$verifyTable) {
                throw new Exception("Error crítico: Las tablas no pudieron crearse en la base de datos.");
            }

            // 7. Generar config.php
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $scriptDir = dirname(dirname($_SERVER['SCRIPT_NAME'] ?? ''));
            $baseUrl = rtrim($protocol . $host . $scriptDir, '/\\');
            $appKey = bin2hex(random_bytes(16));

            $configContent = "<?php\n";
            $configContent .= "/**\n * ShopCMS Configuration File\n * Generado automáticamente el " . date('Y-m-d H:i:s') . "\n */\n\n";
            $configContent .= "define('SHOPCMS_INSTALLED', true);\n";
            $configContent .= "define('APP_KEY', '{$appKey}');\n";
            $configContent .= "define('APP_URL', '{$baseUrl}');\n\n";
            $configContent .= "// Credenciales de la Base de Datos\n";
            $configContent .= "define('DB_HOST', '{$db['host']}');\n";
            $configContent .= "define('DB_PORT', '{$db['port']}');\n";
            $configContent .= "define('DB_NAME', '{$safeDbName}');\n";
            $configContent .= "define('DB_USER', '{$db['user']}');\n";
            $configContent .= "define('DB_PASS', '{$db['pass']}');\n";
            $configContent .= "define('DB_PREFIX', '{$db['prefix']}');\n\n";
            $configContent .= "// Configuración de Entorno\n";
            $configContent .= "define('APP_ENV', 'development');\n";
            $configContent .= "define('APP_DEBUG', true);\n";

            file_put_contents($configFile, $configContent);

            // Iniciar sesión automáticamente como Administrador (estilo WordPress)
            $_SESSION['user_id'] = $adminId;
            $_SESSION['user_name'] = $adminName;
            $_SESSION['user_email'] = $adminEmail;
            $_SESSION['user_role'] = 'admin';
            $_SESSION['flash_success'] = '¡Instalación completada con éxito! Bienvenido al Panel de Administración de ShopCMS.';

            // Redirigir directamente al panel admin
            header('Location: ../admin');
            exit;

        } catch (Exception $e) {
            $error = 'Error durante la instalación: ' . $e->getMessage();
        }
    }
}

// Icono SVG Check reutilizable para pasos
$iconCheck = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador de ShopCMS</title>
    <link rel="stylesheet" href="assets/install.css">
</head>
<body>

<div class="installer-container">
    <div class="installer-header">
        <div class="installer-logo">
            <svg viewBox="0 0 24 24">
                <path d="M19 6h-2c0-2.76-2.24-5-5-5S7 3.24 7 6H5c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm-7-3c1.66 0 3 1.34 3 3H9c0-1.66 1.34-3 3-3zm7 17H5V8h14v12z"/>
            </svg>
            <h1>ShopCMS</h1>
        </div>
        <div class="installer-subtitle">Asistente de Configuración, Reparación e Instalación</div>
    </div>

    <!-- CASO ESPECIAL: Si config.php existe y la base de datos está sana -->
    <?php if ($configExists && $dbHealthy && !isset($_GET['step']) && empty($action)): ?>
        <div class="installer-card" style="text-align: center;">
            <div class="success-icon" style="margin-bottom: 16px;">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
            </div>
            <h2>ShopCMS ya está instalado y funcionando</h2>
            <p class="description">El archivo de configuración y la base de datos están correctamente enlazados.</p>
            
            <div style="display: flex; gap: 12px; justify-content: center; margin-top: 24px;">
                <a href="../admin" class="btn btn-primary">Ir al Panel de Administración</a>
                <a href="../" class="btn btn-secondary">Ver la Tienda</a>
            </div>

            <hr style="border: 0; border-top: 1px solid var(--color-border); margin: 28px 0 16px 0;">
            <div style="font-size: 13px; color: var(--color-text-secondary);">
                ¿Deseas reiniciar o cambiar la base de datos? 
                <a href="index.php?action=force_reinstall" onclick="return confirm('¿Seguro que deseas reiniciar el instalador? Se eliminará la configuración actual.');" style="color: var(--color-error); font-weight: 600; text-decoration: underline;">
                    Reinstalar desde cero
                </a>
            </div>
        </div>
        <?php exit; ?>
    <?php endif; ?>

    <!-- CASO DE REPARACIÓN: Si config.php existe pero faltan tablas o falló la BD -->
    <?php if ($configExists && ($missingTables || $dbConnectionError || $reason === 'missing_tables' || $reason === 'db_error') && $step !== 4): ?>
        <div class="installer-card">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--color-error)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <h2 style="color: var(--color-error); margin: 0;">Reparación de Base de Datos</h2>
            </div>
            <p class="description">
                Se detectó el archivo <code>config.php</code>, pero la base de datos presenta inconsistencias:
            </p>

            <div class="alert-box alert-error">
                <?php if ($missingTables || $reason === 'missing_tables'): ?>
                    <div>
                        <strong>Tablas no encontradas:</strong> La base de datos <code><?= htmlspecialchars(defined('DB_NAME') ? DB_NAME : '') ?></code> existe pero está vacía o le faltan tablas obligatorias (como <code><?= defined('DB_PREFIX') ? DB_PREFIX : 'shop_' ?>products</code>).
                    </div>
                <?php else: ?>
                    <div>
                        <strong>Error de conexión:</strong> <?= htmlspecialchars($dbConnectionError ?? $error ?? 'No se pudo conectar a la BD') ?>
                    </div>
                <?php endif; ?>
            </div>

            <p style="font-size: 14px; margin-bottom: 20px;">
                Puedes restaurar automáticamente todas las tablas con sus datos iniciales con un solo clic, o iniciar una instalación limpia desde cero.
            </p>

            <div style="display: flex; gap: 12px; justify-content: space-between; align-items: center; padding-top: 16px; border-top: 1px solid var(--color-border);">
                <a href="index.php?action=force_reinstall" class="btn btn-secondary" onclick="return confirm('¿Reiniciar instalador desde cero?');">
                    Reinstalar desde cero &rarr;
                </a>
                <a href="index.php?action=execute_repair" class="btn btn-primary" style="padding: 12px 20px; display: inline-flex; align-items: center; gap: 6px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                    </svg>
                    Crear Tablas e Instalar Datos Ahora
                </a>
            </div>
        </div>
        <?php exit; ?>
    <?php endif; ?>

    <!-- Barra de Pasos -->
    <div class="steps-bar">
        <div class="step-item <?= $step >= 1 ? ($step > 1 ? 'completed' : 'active') : '' ?>">
            <div class="step-circle"><?= $step > 1 ? $iconCheck : '1' ?></div>
            <span>Requisitos</span>
        </div>
        <div class="step-item <?= $step >= 2 ? ($step > 2 ? 'completed' : 'active') : '' ?>">
            <div class="step-circle"><?= $step > 2 ? $iconCheck : '2' ?></div>
            <span>Base de Datos</span>
        </div>
        <div class="step-item <?= $step >= 3 ? ($step > 3 ? 'completed' : 'active') : '' ?>">
            <div class="step-circle"><?= $step > 3 ? $iconCheck : '3' ?></div>
            <span>Tienda y Admin</span>
        </div>
        <div class="step-item <?= $step === 4 ? 'active completed' : '' ?>">
            <div class="step-circle"><?= $step === 4 ? $iconCheck : '4' ?></div>
            <span>Listo</span>
        </div>
    </div>

    <div class="installer-card">
        <?php if ($error): ?>
            <div class="alert-box alert-error">
                <strong>Error:</strong> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- PASO 1: REQUISITOS -->
        <?php if ($step === 1): ?>
            <h2>1. Verificación del Servidor y Entorno</h2>
            <p class="description">Verificando que tu entorno local (XAMPP / PHP) cumpla con los requisitos para ejecutar ShopCMS.</p>

            <table class="check-table">
                <thead>
                    <tr>
                        <th>Requisito</th>
                        <th>Requerido</th>
                        <th>Estado Actual</th>
                        <th>Resultado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requirements as $req): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($req['name']) ?></strong></td>
                            <td><?= htmlspecialchars($req['required']) ?></td>
                            <td><?= htmlspecialchars($req['current']) ?></td>
                            <td>
                                <?php if ($req['status']): ?>
                                    <span class="badge badge-success">Correcto</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Falta</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="btn-row">
                <div></div>
                <?php if ($allRequirementsPassed): ?>
                    <a href="index.php?step=2" class="btn btn-primary">Continuar a Base de Datos &rarr;</a>
                <?php else: ?>
                    <button class="btn btn-secondary" onclick="location.reload();">Volver a Comprobar</button>
                <?php endif; ?>
            </div>

        <!-- PASO 2: BASE DE DATOS -->
        <?php elseif ($step === 2): ?>
            <h2>2. Configuración de Base de Datos MySQL</h2>
            <p class="description">Ingresa las credenciales de MySQL en XAMPP. El instalador probará la conexión y creará la base de datos automáticamente si aún no existe.</p>

            <form method="POST" action="index.php?step=2">
                <div class="form-row">
                    <div class="form-group">
                        <label for="db_host">Servidor de BD (Host) *</label>
                        <input type="text" id="db_host" name="db_host" class="form-control" value="<?= htmlspecialchars($_SESSION['install_db']['host'] ?? 'localhost') ?>" required>
                        <div class="help-text">Normalmente <code>localhost</code> en XAMPP.</div>
                    </div>
                    <div class="form-group" style="max-width: 140px;">
                        <label for="db_port">Puerto *</label>
                        <input type="text" id="db_port" name="db_port" class="form-control" value="<?= htmlspecialchars($_SESSION['install_db']['port'] ?? '3306') ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="db_name">Nombre de la Base de Datos *</label>
                    <input type="text" id="db_name" name="db_name" class="form-control" value="<?= htmlspecialchars($_SESSION['install_db']['name'] ?? 'shopcms_db') ?>" required>
                    <div class="help-text">Ejemplo: <code>shopcms_db</code> (si no existe, se creará automáticamente).</div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="db_user">Usuario de MySQL *</label>
                        <input type="text" id="db_user" name="db_user" class="form-control" value="<?= htmlspecialchars($_SESSION['install_db']['user'] ?? 'root') ?>" required>
                        <div class="help-text">En XAMPP por defecto es <code>root</code>.</div>
                    </div>
                    <div class="form-group">
                        <label for="db_pass">Contraseña de MySQL</label>
                        <input type="password" id="db_pass" name="db_pass" class="form-control" value="<?= htmlspecialchars($_SESSION['install_db']['pass'] ?? '') ?>" placeholder="(vacía en XAMPP por defecto)">
                    </div>
                </div>

                <div class="form-group">
                    <label for="db_prefix">Prefijo de Tablas *</label>
                    <input type="text" id="db_prefix" name="db_prefix" class="form-control" value="<?= htmlspecialchars($_SESSION['install_db']['prefix'] ?? 'shop_') ?>" required>
                    <div class="help-text">Recomendado: <code>shop_</code></div>
                </div>

                <div class="btn-row">
                    <a href="index.php?step=1" class="btn btn-secondary">&larr; Atrás</a>
                    <button type="submit" class="btn btn-primary">Probar y Continuar &rarr;</button>
                </div>
            </form>

        <!-- PASO 3: TIENDA Y ADMIN -->
        <?php elseif ($step === 3): ?>
            <h2>3. Datos de la Tienda y Administrador</h2>
            <p class="description">Define los datos comerciales de tu tienda y la cuenta de acceso maestro al panel Polaris.</p>

            <form method="POST" action="index.php?step=3">
                <div class="form-row">
                    <div class="form-group">
                        <label for="store_name">Nombre de la Tienda *</label>
                        <input type="text" id="store_name" name="store_name" class="form-control" placeholder="Ej: Mi Tienda Online" value="<?= htmlspecialchars($_POST['store_name'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="store_currency">Moneda Principal *</label>
                        <select id="store_currency" name="store_currency" class="form-control">
                            <option value="USD">USD ($ - Dólar)</option>
                            <option value="PEN" selected>PEN (S/ - Sol Peruano)</option>
                            <option value="EUR">EUR (€ - Euro)</option>
                            <option value="MXN">MXN ($ - Peso Mexicano)</option>
                            <option value="COP">COP ($ - Peso Colombiano)</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="store_email">Correo de Contacto de la Tienda *</label>
                    <input type="email" id="store_email" name="store_email" class="form-control" placeholder="contacto@tutienda.com" value="<?= htmlspecialchars($_POST['store_email'] ?? '') ?>" required>
                </div>

                <hr style="border: 0; border-top: 1px solid var(--color-border); margin: 24px 0;">

                <div class="form-group">
                    <label for="admin_name">Nombre del Administrador *</label>
                    <input type="text" id="admin_name" name="admin_name" class="form-control" placeholder="Tu nombre completo" value="<?= htmlspecialchars($_POST['admin_name'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="admin_email">Correo de Acceso del Administrador *</label>
                    <input type="email" id="admin_email" name="admin_email" class="form-control" placeholder="tu-correo@dominio.com" value="<?= htmlspecialchars($_POST['admin_email'] ?? '') ?>" required>
                    <div class="help-text">Este correo será tu usuario para iniciar sesión en el panel.</div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="admin_password">Contraseña *</label>
                        <input type="password" id="admin_password" name="admin_password" class="form-control" placeholder="Mínimo 6 caracteres" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label for="admin_password_confirm">Confirmar Contraseña *</label>
                        <input type="password" id="admin_password_confirm" name="admin_password_confirm" class="form-control" placeholder="Repite tu contraseña" required minlength="6">
                    </div>
                </div>

                <div class="btn-row">
                    <a href="index.php?step=2" class="btn btn-secondary">&larr; Atrás</a>
                    <button type="submit" class="btn btn-primary">Instalar ShopCMS Ahora &rarr;</button>
                </div>
            </form>

        <!-- PASO 4: ÉXITO -->
        <?php elseif ($step === 4): ?>
            <div style="text-align: center;">
                <div class="success-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>
                <h2><?= isset($_GET['repaired']) ? '¡Base de Datos Reparada con Éxito!' : '¡Instalación Completada con Éxito!' ?></h2>
                <p class="description">
                    Se crearon las 17 tablas del sistema, los 4 temas base, los 6 plugins base y tu cuenta de administrador.
                </p>

                <div class="alert-box alert-info" style="text-align: left;">
                    <div>
                        <strong>Datos de acceso registrados:</strong><br>
                        • Panel Admin: <code>/admin</code><br>
                        • Correo: <code><?= htmlspecialchars($_SESSION['installed_store']['admin_email'] ?? ($_SESSION['user_email'] ?? '')) ?></code><br>
                        • Archivo <code>config.php</code> generado y protegido.
                    </div>
                </div>

                <div style="margin-top: 32px; display: flex; gap: 16px; justify-content: center;">
                    <a href="../admin" class="btn btn-primary" style="padding: 12px 24px;">Acceder al Panel de Administración &rarr;</a>
                    <a href="../" class="btn btn-secondary" style="padding: 12px 24px;">Ver Tienda Online</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
