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
        if (self::validateCsrfToken()) {
            return;
        }

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
}
