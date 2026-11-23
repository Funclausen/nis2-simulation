<?php
declare(strict_types=1);

/*
 * Integration adapter for hannestad.dk/private.
 * Replace ONLY the three adapter functions below after inspecting the existing
 * /private authentication/session implementation. Do not invent a second login.
 */
function nis2_admin_authenticated(): bool {
    return false; // fail closed until /private auth is wired in
}
function nis2_admin_user_id(): ?int {
    return null;
}
function nis2_admin_csrf_valid(): bool {
    return false; // fail closed until existing CSRF/session contract is known
}

function nis2_require_admin(): void {
    if (!nis2_admin_authenticated()) nis2_json(['error'=>'unauthorized'],401);
}
function nis2_verify_csrf(): void {
    if (!nis2_admin_csrf_valid()) nis2_json(['error'=>'invalid_csrf'],403);
}
