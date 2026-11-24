<?php
namespace ShopCMS\Core;

class Security {
    public const MAX_ACCOUNT_ATTEMPTS = 5;
    public const MAX_IP_ATTEMPTS = 10;
    public const ATTEMPT_WINDOW_SEC = 900;  // 15 minutos
    public const LOCKOUT_DURATION_SEC = 300; // 5 minutos de bloqueo

    public static function csrfToken(): string {
        Auth::init();
        if (empty($_SESSION['_csrf_token']) || !is_string($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    public static function csrfField(): string {
        return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars(self::csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function csrfMetaTag(): string {
        return '<meta name="csrf-token" content="' . htmlspecialchars(self::csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function validateCsrfToken(?string $provided = null): bool {
        Auth::init();
        $expected = (string)($_SESSION['_csrf_token'] ?? '');
        if ($expected === '') {
            return false;
        }
        if ($provided === null) {
            $provided = $_POST['_csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        }
        if (!is_string($provided) || $provided === '') {
            return false;
        }
        return hash_equals($expected, $provided);
    }

    public static function enforcePostCsrf(string $route): void {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return;
        }
        if (!self::validateSameOrigin() || !self::validateCsrfToken()) {
            self::auditLog('csrf_or_origin_rejected', ['route' => $route]);
            self::rejectCsrf($route);
        }
    }

    public static function validateSameOrigin(): bool {
        $origin = trim((string)($_SERVER['HTTP_ORIGIN'] ?? ''));
        if ($origin === '' || strtolower($origin) === 'null') {
            return true;
        }
        $originHost = parse_url($origin, PHP_URL_HOST);
        $baseUrl = defined('APP_URL') ? (string)APP_URL : '';
        $expectedHost = parse_url($baseUrl !== '' ? $baseUrl : ('http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')), PHP_URL_HOST);
        $serverHost = parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'), PHP_URL_HOST);
        if ($originHost === null) {
            return false;
        }
        return strcasecmp((string)$originHost, (string)$expectedHost) === 0
            || strcasecmp((string)$originHost, (string)$serverHost) === 0;
    }

    private static function rejectCsrf(string $route): void {

        $accept = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
        $contentType = strtolower((string)($_SERVER['CONTENT_TYPE'] ?? ''));
        $isAjax = str_contains($accept, 'application/json')
            || str_contains($contentType, 'application/json')
            || str_starts_with($route, '/plugin/')
            || str_starts_with($route, '/admin/themes/customize/');

        http_response_code(403);
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => false,
                'csrf_error' => true,
                'error' => 'Token de seguridad CSRF inválido o sesión expirada. Recarga la página e intenta nuevamente.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        Auth::init();
        $msg = 'Sesión expirada o solicitud no verificada (CSRF inválido). Por favor, recarga la página e intenta nuevamente.';
        $_SESSION['flash_error'] = $msg;
        if (str_contains($route, 'login') || str_contains($route, 'register')) {
            $_SESSION['auth_error'] = $msg;
        }
        if (str_contains($route, 'cart') || str_contains($route, 'checkout')) {
            $_SESSION['cart_error'] = $msg;
        }

        $baseUrl = defined('APP_URL') ? APP_URL : '';
        $fallback = str_starts_with($route, '/admin') ? ($baseUrl . '/admin/login') : ($baseUrl . '/login');
        $referer = (string)($_SERVER['HTTP_REFERER'] ?? '');
        $target = $fallback;
        if ($referer !== '') {
            $refHost = parse_url($referer, PHP_URL_HOST);
            $curHost = parse_url($baseUrl ?: ('http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')), PHP_URL_HOST);
            if ($refHost === null || strcasecmp((string)$refHost, (string)$curHost) === 0) {
                $target = $referer;
            }
        }
        header('Location: ' . $target);
        exit;
    }

    public static function clientIp(): string {
        $ip = trim((string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'));
        return $ip !== '' ? $ip : '0.0.0.0';
    }

    private static function loadAttemptsLedger(): array {
        try {
            $raw = Settings::getRaw('security_login_attempts', '{}');
            $data = json_decode($raw, true);
            if (!is_array($data)) return [];
            $now = time();
            foreach ($data as $k => $entry) {
                if (!is_array($entry)) {
                    unset($data[$k]);
                    continue;
                }
                $last = (int)($entry['last_attempt'] ?? 0);
                $lockedUntil = (int)($entry['locked_until'] ?? 0);
                if ($lockedUntil < $now && ($last + self::ATTEMPT_WINDOW_SEC) < $now) {
                    unset($data[$k]);
                }
            }
            return $data;
        } catch (\Throwable $e) {
            return [];
        }
    }

    private static function saveAttemptsLedger(array $ledger): void {
        try {
            if (count($ledger) > 300) {
                $ledger = array_slice($ledger, -300, null, true);
            }
            Settings::saveMany([
                'security_login_attempts' => json_encode($ledger, JSON_UNESCAPED_UNICODE)
            ]);
        } catch (\Throwable $e) {
            // Ignorar si BD no está disponible en pruebas aisladas
        }
    }

    private static function attemptKeys(string $scope, string $email, ?string $ip = null): array {
        $ipAddr = $ip ?? self::clientIp();
        $acct = strtolower(trim($email));
        $keys = ['ip' => $scope . ':ip:' . sha1($ipAddr)];
        if ($acct !== '') {
            $keys['acct'] = $scope . ':acct:' . sha1($acct);
        }
        return $keys;
    }

    public static function checkLoginLockout(string $scope, string $email, ?string $ip = null): array {
        $ledger = self::loadAttemptsLedger();
        $keys = self::attemptKeys($scope, $email, $ip);
        $now = time();

        $maxRemLock = 0;
        $acctCount = 0;
        if (isset($keys['acct'], $ledger[$keys['acct']])) {
            $e = $ledger[$keys['acct']];
            if ((int)($e['locked_until'] ?? 0) > $now) {
                $maxRemLock = max($maxRemLock, (int)$e['locked_until'] - $now);
            }
            if (((int)($e['last_attempt'] ?? 0) + self::ATTEMPT_WINDOW_SEC) >= $now) {
                $acctCount = (int)($e['count'] ?? 0);
            }
        }
        $ipCount = 0;
        if (isset($ledger[$keys['ip']])) {
            $e = $ledger[$keys['ip']];
            if ((int)($e['locked_until'] ?? 0) > $now) {
                $maxRemLock = max($maxRemLock, (int)$e['locked_until'] - $now);
            }
            if (((int)($e['last_attempt'] ?? 0) + self::ATTEMPT_WINDOW_SEC) >= $now) {
                $ipCount = (int)($e['count'] ?? 0);
            }
        }

        return [
            'locked' => $maxRemLock > 0,
            'remaining_seconds' => $maxRemLock,
            'attempts' => $acctCount,
            'ip_attempts' => $ipCount,
            'remaining_attempts' => max(0, self::MAX_ACCOUNT_ATTEMPTS - $acctCount),
        ];
    }

    public static function recordFailedLogin(string $scope, string $email, ?string $ip = null): array {
        $ledger = self::loadAttemptsLedger();
        $keys = self::attemptKeys($scope, $email, $ip);
        $now = time();

        $bump = function (string $key, int $limit) use (&$ledger, $now): int {
            $prev = $ledger[$key] ?? ['count' => 0, 'last_attempt' => 0, 'locked_until' => 0];
            $count = (((int)$prev['last_attempt'] + self::ATTEMPT_WINDOW_SEC) >= $now)
                ? ((int)$prev['count'] + 1)
                : 1;
            $lockedUntil = (int)($prev['locked_until'] ?? 0);
            if ($count >= $limit && $lockedUntil <= $now) {
                $lockedUntil = $now + self::LOCKOUT_DURATION_SEC;
            }
            $ledger[$key] = [
                'count' => $count,
                'last_attempt' => $now,
                'locked_until' => $lockedUntil,
            ];
            return $lockedUntil > $now ? ($lockedUntil - $now) : 0;
        };

        $ipLock = $bump($keys['ip'], self::MAX_IP_ATTEMPTS);
        $acctLock = isset($keys['acct']) ? $bump($keys['acct'], self::MAX_ACCOUNT_ATTEMPTS) : 0;
        self::saveAttemptsLedger($ledger);

        $remLock = max($ipLock, $acctLock);
        $acctCount = isset($keys['acct']) ? (int)($ledger[$keys['acct']]['count'] ?? 0) : 0;
        return [
            'locked' => $remLock > 0,
            'remaining_seconds' => $remLock,
            'attempts' => $acctCount,
            'remaining_attempts' => max(0, self::MAX_ACCOUNT_ATTEMPTS - $acctCount),
        ];
    }

    public static function clearLoginAttempts(string $scope, string $email, ?string $ip = null): void {
        $ledger = self::loadAttemptsLedger();
        $keys = self::attemptKeys($scope, $email, $ip);
        $changed = false;
        foreach ($keys as $k) {
            if (isset($ledger[$k])) {
                unset($ledger[$k]);
                $changed = true;
            }
        }
        if ($changed) {
            self::saveAttemptsLedger($ledger);
        }
    }

    public static function formatWaitTime(int $seconds): string {
        $seconds = max(0, $seconds);
        $mins = intdiv($seconds, 60);
        $secs = $seconds % 60;
        return sprintf('%02d:%02d', $mins, $secs);
    }

    /**
     * Emite cabeceras HTTP de seguridad (OWASP A05 / A02 / A03) compatibles con el
     * personalizador de temas en el mismo origen (iframe SAMEORIGIN / frame-ancestors 'self').
     */
    public static function sendSecurityHeaders(): void {
        if (headers_sent()) {
            return;
        }
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
        header(
            "Content-Security-Policy: default-src 'self'; "
            . "script-src 'self' 'unsafe-inline'; "
            . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
            . "font-src 'self' https://fonts.gstatic.com data:; "
            . "img-src 'self' data: blob: https:; "
            . "connect-src 'self'; "
            . "frame-ancestors 'self'; "
            . "base-uri 'self'; "
            . "form-action 'self';"
        );

        $isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
            || (strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https');
        if ($isHttps) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }

    /**
     * Registra eventos de seguridad (logins, bloqueos, accesos denegados, errores)
     * omitiendo automáticamente contraseñas, tokens u otros secretos.
     */
    public static function auditLog(string $event, array $context = []): string {
        $correlationId = bin2hex(random_bytes(4));
        try {
            $logDir = dirname(__DIR__, 2) . '/storage/logs';
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0750, true);
            }
            $htPath = dirname($logDir) . '/.htaccess';
            if (!file_exists($htPath)) {
                @file_put_contents($htPath, "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n</IfModule>\n");
            }

            $blockedKeys = ['password', 'pass', 'pwd', 'token', 'csrf', 'secret', 'key', 'cookie', 'authorization', 'card', 'cvv'];
            $safeContext = [];
            foreach ($context as $k => $v) {
                $lk = strtolower((string)$k);
                $isSensitive = false;
                foreach ($blockedKeys as $bk) {
                    if (str_contains($lk, $bk)) {
                        $isSensitive = true;
                        break;
                    }
                }
                if ($isSensitive) {
                    continue;
                }
                if (is_scalar($v) || $v === null) {
                    $safeContext[$k] = is_string($v) ? substr($v, 0, 180) : $v;
                }
            }

            $entry = [
                'ts' => date('c'),
                'cid' => $correlationId,
                'event' => preg_replace('/[^a-zA-Z0-9_\-.]/', '', $event),
                'ip' => self::clientIp(),
                'method' => (string)($_SERVER['REQUEST_METHOD'] ?? 'CLI'),
                'route' => substr((string)($_GET['route'] ?? '/'), 0, 140),
                'ctx' => $safeContext,
            ];
            @file_put_contents(
                $logDir . '/security.log',
                json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
                FILE_APPEND | LOCK_EX
            );
        } catch (\Throwable $e) {
            // Nunca interrumpir la petición si falla la escritura del log
        }
        return $correlationId;
    }
}
