<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/admin-auth.php';

header('Content-Type: application/json; charset=utf-8');

function respond(int $status, array $data): never
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

/*
|--------------------------------------------------------------------------
| AJAX ONLY
|--------------------------------------------------------------------------
*/

if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'XMLHttpRequest') {

    respond(403, [
        'success' => false,
        'message' => 'Invalid request.'
    ]);
}

/*
|--------------------------------------------------------------------------
| CSRF
|--------------------------------------------------------------------------
*/

$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

if (
    empty($_SESSION['csrf_token']) ||
    empty($csrfToken) ||
    !hash_equals($_SESSION['csrf_token'], $csrfToken)
) {

    respond(403, [
        'success' => false,
        'message' => 'Security validation failed.'
    ]);
}

/*
|--------------------------------------------------------------------------
| POST ONLY
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    respond(405, [
        'success' => false,
        'message' => 'Method not allowed.'
    ]);
}

/*
|--------------------------------------------------------------------------
| REQUEST BODY
|--------------------------------------------------------------------------
*/

$data = json_decode(file_get_contents('php://input'), true);

if (!is_array($data)) {

    respond(400, [
        'success' => false,
        'message' => 'Invalid request body.'
    ]);
}

$investmentId = trim($data['investment_id'] ?? '');
$reason = trim($data['reason'] ?? '');
$comment = trim($data['comment'] ?? '');

/*
|--------------------------------------------------------------------------
| VALIDATION
|--------------------------------------------------------------------------
*/

if ($investmentId === '') {

    respond(422, [
        'success' => false,
        'message' => 'Investment ID is required.'
    ]);
}

$allowedReasons = [

    'Wrong Amount',
    'Duplicate TXID',
    'Wallet Mismatch',
    'Invalid Transaction',
    'Other'

];

if (!in_array($reason, $allowedReasons, true)) {

    respond(422, [
        'success' => false,
        'message' => 'Invalid rejection reason.'
    ]);
}

/*
|--------------------------------------------------------------------------
| LOAD INVESTMENT
|--------------------------------------------------------------------------
*/

$response = $supabase->selectRow(

    'investments',

    'id=eq.' . urlencode($investmentId) . '&select=*'

);

if (

    ($response['status'] ?? 500) >= 400 ||

    empty($response['body'])

) {

    respond(404, [

        'success' => false,

        'message' => 'Investment not found.'

    ]);
}

$investment = $response['body'][0];

/*
|--------------------------------------------------------------------------
| BUSINESS RULES
|--------------------------------------------------------------------------
*/

if ($investment['status'] === 'active') {

    respond(409, [

        'success' => false,

        'message' => 'Active investments cannot be rejected.'

    ]);
}

if ($investment['status'] === 'rejected') {

    respond(409, [

        'success' => false,

        'message' => 'This investment has already been rejected.'

    ]);
}

if ($investment['status'] === 'cancelled') {

    respond(409, [

        'success' => false,

        'message' => 'Cancelled investments cannot be rejected.'

    ]);
}

/*
|--------------------------------------------------------------------------
| UPDATE
|--------------------------------------------------------------------------
*/

$update = $supabase->updateRow(

    'investments',

    'id=eq.' . urlencode($investmentId),

    [

        'status' => 'rejected',

        'payment_status' => 'payment_rejected',

        'rejected_by' => $_SESSION['admin_id'],

        'rejected_at' => gmdate('c'),

        'rejection_reason' => $reason,

        'rejection_comment' => $comment

    ]

);

if (($update['status'] ?? 500) >= 400) {

    respond(500, [

        'success' => false,

        'message' => 'Unable to reject investment.'

    ]);
}

/*
|--------------------------------------------------------------------------
| AUDIT LOG (OPTIONAL)
|--------------------------------------------------------------------------
*/

if (method_exists($supabase, 'insertRow')) {

    @$supabase->insertRow(

        'audit_logs',

        [

            'admin_id' => $_SESSION['admin_id'],

            'action' => 'Rejected Investment',

            'reference' => $investment['reference'],

            'description' => $reason,

            'created_at' => gmdate('c')

        ]

    );
}

/*
|--------------------------------------------------------------------------
| ROTATE CSRF TOKEN
|--------------------------------------------------------------------------
*/

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

/*
|--------------------------------------------------------------------------
| RESPONSE
|--------------------------------------------------------------------------
*/

respond(200, [

    'success' => true,

    'message' => 'Investment rejected successfully.',

    'csrf_token' => $_SESSION['csrf_token']

]);
