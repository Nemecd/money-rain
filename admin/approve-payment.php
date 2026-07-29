<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/admin-auth.php';

header('Content-Type: application/json; charset=utf-8');
/*
|--------------------------------------------------------------------------
| AJAX ONLY
|--------------------------------------------------------------------------
*/

if (
    ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'XMLHttpRequest'
) {

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

    !hash_equals(

        $_SESSION['csrf_token'],

        $csrfToken

    )

) {

    respond(403, [

        'success' => false,

        'message' => 'Security validation failed.'

    ]);
}
function respond(int $status, array $data): never
{
    http_response_code($status);

    echo json_encode($data);

    exit;
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
| REQUEST
|--------------------------------------------------------------------------
*/

$data = json_decode(

    file_get_contents('php://input'),

    true

);

if (!is_array($data)) {

    respond(400, [

        'success' => false,

        'message' => 'Invalid request.'

    ]);
}

$investmentId = trim(

    $data['investment_id'] ?? ''

);

if ($investmentId === '') {

    respond(422, [

        'success' => false,

        'message' => 'Investment ID is required.'

    ]);
}

/*
|--------------------------------------------------------------------------
| LOAD INVESTMENT
|--------------------------------------------------------------------------
*/

$response = $supabase->selectRow(

    'investments',

    'id=eq.' .

        urlencode($investmentId) .

        '&select=*' .

        '&limit=1'

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
| ALREADY ACTIVE
|--------------------------------------------------------------------------
*/

if (

    $investment['status'] === 'active'

) {

    respond(409, [

        'success' => false,

        'message' =>

        'This investment has already been approved.'

    ]);
}

if (

    $investment['payment_status']

    ===

    'payment_verified'

) {

    respond(409, [

        'success' => false,

        'message' => 'Payment has already been verified.'

    ]);
}
/*
|--------------------------------------------------------------------------
| DATES
|--------------------------------------------------------------------------
*/

$startedAt = gmdate('c');

$endsAt = gmdate(

    'c',

    strtotime(

        '+' .

            (int)$investment['duration_days'] .

            ' days'

    )

);
/*
|--------------------------------------------------------------------------
| ACTIVATE
|--------------------------------------------------------------------------
*/

$update = $supabase->updateRow(

    'investments',

    'id=eq.' .

        urlencode($investmentId),

    [

        'status' => 'active',

        'payment_status' => 'payment_verified',

        'started_at' => $startedAt,

        'ends_at' => $endsAt,

        'approved_at' => gmdate('c'),

        'approved_by' => $_SESSION['admin_id']

    ]

);

if (

    ($update['status'] ?? 500)

    >= 400

) {

    respond(500, [

        'success' => false,

        'message' => 'Unable to activate investment.'

    ]);
}
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
respond(

    200,

    [

        'success' => true,

        'message' =>

        'Investment approved successfully.',
        'csrf_token' => $_SESSION['csrf_token']

    ]

);
