<?php
declare(strict_types=1);

function nis2_admin_context(): ?array {
    static $checked = false, $context = null;
    if ($checked) {
        return $context;
    }
    $checked = true;

    $name = 'hannestad_private';
    if (empty($_COOKIE[$name])) {
        return null;
    }
    $portal = dirname(__DIR__, 4) . '/.hannestad-private';
    if (!is_file($portal . '/core.php') || !is_dir($portal . '/data/sessions')) {
        return null;
    }
    require_once $portal . '/core.php';

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_save_path($portal . '/data/sessions');
    session_name($name);
    session_set_cookie_params([
        'lifetime' => 0, 'path' => '/privat/', 'secure' => true,
        'httponly' => true, 'samesite' => 'Strict',
    ]);
    if (!session_start()) {
        return null;
    }
    try {
        $user = currentUser();
        if (($user['role'] ?? null) === 'admin') {
            $context = ['id' => (int)$user['id'], 'csrf' => csrf()];
        }
    } finally {
        session_write_close();
    }
    return $context;
}

function nis2_admin_user_id(): ?int {
    return nis2_admin_context()['id'] ?? null;
}

function nis2_require_admin(): void {
    if (nis2_admin_context() === null) {
        nis2_json(['error' => 'unauthorized'], 401);
    }
}

function nis2_verify_csrf(): void {
    $expected = nis2_admin_context()['csrf'] ?? '';
    if ($expected === '' || !hash_equals($expected, $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
        nis2_json(['error' => 'invalid_csrf'], 403);
    }
}
