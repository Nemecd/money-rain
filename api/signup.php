<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'errors' => ['Method not allowed.']]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

$fullname       = trim($input['fullname'] ?? '');
$username       = trim($input['username'] ?? '');
$email          = trim($input['email'] ?? '');
$phone          = trim($input['phone'] ?? '');
$bank           = trim($input['bank'] ?? '');
$accountName    = trim($input['accountName'] ?? '');
$accountNumber  = trim($input['accountNumber'] ?? '');
$password       = $input['password'] ?? '';
$confirmPass    = $input['confirmPassword'] ?? '';
$referral       = trim($input['referral'] ?? '');
$agreement      = $input['agreement'] ?? false;

// ---- validation ----
$errors = [];
if ($fullname === '') $errors[] = 'Full name is required.';
if ($username === '' || !preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
    $errors[] = 'Username must be 3-20 characters (letters, numbers, underscore only).';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
if ($phone === '') $errors[] = 'Phone number is required.';
if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
if ($password !== $confirmPass) $errors[] = 'Passwords do not match.';
if (!$agreement) $errors[] = 'You must accept the Investor Agreement.';

if ($errors) {
    http_response_code(422);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// ---- check username uniqueness ourselves (Supabase Auth only checks email) ----
$existing = $supabase->selectRow('profiles', 'username=eq.' . urlencode($username) . '&select=id');
if ($existing['status'] === 200 && !empty($existing['body'])) {
    http_response_code(422);
    echo json_encode(['success' => false, 'errors' => ['That username is already taken.']]);
    exit;
}

// ---- create the auth user (anon key) ----
$signUp = $supabase->signUp($email, $password);
$userId = $signUp['body']['id'] ?? $signUp['body']['user']['id'] ?? null;

if ($signUp['status'] !== 200 || !$userId) {
    http_response_code(400);
    $msg = $signUp['body']['msg']
        ?? $signUp['body']['error_description']
        ?? 'Could not create account. That email may already be registered.';
    echo json_encode(['success' => false, 'errors' => [$msg]]);
    exit;
}

// ---- resolve referral code, if one was provided ----
$referredBy = null;
if ($referral !== '') {
    $ref = $supabase->selectRow('profiles', 'referral_code=eq.' . urlencode($referral) . '&select=id');
    if ($ref['status'] === 200 && !empty($ref['body'][0]['id'])) {
        $referredBy = $ref['body'][0]['id'];
    }
}

// ---- create the profile row (service role key — bypasses RLS) ----
$profile = $supabase->insertRow('profiles', [
    'id'             => $userId,
    'full_name'      => $fullname,
    'username'       => $username,
    'email'          => $email,
    'phone'          => $phone,
    'bank_name'      => $bank ?: null,
    'account_name'   => $accountName ?: null,
    'account_number' => $accountNumber ?: null,
    'referral_code'  => strtoupper(substr(bin2hex(random_bytes(4)), 0, 8)),
    'referred_by'    => $referredBy,
]);

if ($profile['status'] >= 400) {
    // Auth user exists but profile failed — surface this clearly rather
    // than silently leaving an orphaned auth user.
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'errors'  => ['Account was created but profile setup failed. Please contact support.'],
    ]);
    exit;
}

echo json_encode(['success' => true, 'message' => 'Account created. You can now log in.']);