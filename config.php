<?php
/**
 * Bootstrap file — required at the top of every PHP entry point.
 * Loads .env, starts the session, and exposes $supabase.
 */

declare(strict_types=1);

// --- tiny .env loader (no Composer needed) ---
function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        return;
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
        putenv(trim($key) . '=' . trim($value));
    }
}

loadEnv(__DIR__ . '/.env');

define('SUPABASE_URL', getenv('SUPABASE_URL') ?: '');
define('SUPABASE_ANON_KEY', getenv('SUPABASE_ANON_KEY') ?: '');
define('SUPABASE_SERVICE_KEY', getenv('SUPABASE_SERVICE_KEY') ?: '');

if (!SUPABASE_URL || !SUPABASE_ANON_KEY || !SUPABASE_SERVICE_KEY) {
    http_response_code(500);
    die('Server misconfigured: missing Supabase credentials. Check .env — see SETUP-GUIDE.md.');
}

// --- session config (PHP owns the session, not the browser's Supabase SDK) ---
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    // 'secure' => true, // uncomment once the site is served over HTTPS
]);
session_start();
if (empty($_SESSION['csrf_token'])) {

    $_SESSION['csrf_token'] = bin2hex(

        random_bytes(32)

    );

}
require_once __DIR__ . '/includes/Supabase.php';
$supabase = new SupabaseClient(SUPABASE_URL, SUPABASE_ANON_KEY, SUPABASE_SERVICE_KEY);