<?php
declare(strict_types=1);
require __DIR__ . '/api/_admin.php';

header('Cache-Control: no-store, private');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
$admin = nis2_admin_context();
if ($admin === null) {
    http_response_code(401);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="da"><meta charset="utf-8"><title>Adgang til NIS2</title>'
        . '<p>Log ind som administrator på <a href="/privat/">Hannestad</a> for at åbne facilitatorvisningen.</p>';
    exit;
}
header('Content-Type: text/html; charset=utf-8');
$template = file_get_contents(__DIR__ . '/facilitator.html');
echo str_replace('%%NIS2_CSRF%%', htmlspecialchars($admin['csrf'], ENT_QUOTES, 'UTF-8'), $template);
