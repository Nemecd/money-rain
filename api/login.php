<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'errors' => ['Method not allowed.']]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$email    = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$remember = $input['remember'] ?? false;

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'errors' => ['Email and password are required.']]);
    exit;
}

$signIn = $supabase->signIn($email, $password);

if ($signIn['status'] !== 200 || empty($signIn['body']['access_token'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'errors' => ['Invalid email or password.']]);
    exit;
}

$body = $signIn['body'];

// PHP owns the session: store the minimum needed server-side.
// The access/refresh tokens never touch the browser's JS.
session_regenerate_id(true);
$_SESSION['user_id']       = $body['user']['id'];
$_SESSION['email']         = $body['user']['email'];
$_SESSION['access_token']  = $body['access_token'];
$_SESSION['refresh_token'] = $body['refresh_token'];
$_SESSION['expires_at']    = time() + ($body['expires_in'] ?? 3600);

if ($remember) {
    // Extend the PHP session cookie lifetime for "remember me".
    setcookie(session_name(), session_id(), time() + (30 * 24 * 60 * 60), '/', '', false, true);
}

echo json_encode(['success' => true, 'redirect' => 'dashboard.php']);