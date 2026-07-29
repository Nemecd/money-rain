<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/session.php';

header('Content-Type: application/json; charset=utf-8');


function respond(int $status, array $data): never
{
    http_response_code($status);

    echo json_encode(
        $data,
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


set_exception_handler(function (Throwable $exception): void {

    error_log(

        'CANCEL INVESTMENT ERROR: ' .

            $exception->getMessage()

    );

    respond(500, [

        'success' => false,

        'message' =>
        'A server error occurred while cancelling the investment.'

    ]);
});


requireAuth();

$userId = currentUserId();

if (!$userId) {

    respond(401, [

        'success' => false,

        'message' => 'Authentication required.'

    ]);
}


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    respond(405, [

        'success' => false,

        'message' => 'Method not allowed.'

    ]);
}


/*
|--------------------------------------------------------------------------
| CSRF
|--------------------------------------------------------------------------
*/

$csrfToken =
    $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

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

        'message' => 'Invalid security token.'

    ]);
}


/*
|--------------------------------------------------------------------------
| READ REQUEST
|--------------------------------------------------------------------------
*/

$rawInput =
    file_get_contents('php://input');

$data =
    json_decode($rawInput, true);


if (!is_array($data)) {

    respond(400, [

        'success' => false,

        'message' => 'Invalid request data.'

    ]);
}


$investmentId =
    trim((string) (

        $data['investment_id'] ?? ''

    ));


if ($investmentId === '') {

    respond(422, [

        'success' => false,

        'message' => 'Investment ID is required.'

    ]);
}


/*
|--------------------------------------------------------------------------
| FIND INVESTMENT
|--------------------------------------------------------------------------
*/

$investmentResponse =

    $supabase->selectRow(

        'investments',

        'id=eq.' .

            urlencode($investmentId) .

            '&user_id=eq.' .

            urlencode($userId) .

            '&select=id,status,payment_status' .

            '&limit=1'

    );


if (

    ($investmentResponse['status'] ?? 500) < 200 ||

    ($investmentResponse['status'] ?? 500) >= 300

) {

    error_log(

        'INVESTMENT LOOKUP FAILED: ' .

            json_encode(

                $investmentResponse

            )

    );

    respond(500, [

        'success' => false,

        'message' =>

        'Unable to verify this investment.'

    ]);
}


$investment =

    $investmentResponse['body'][0] ?? null;


if (!is_array($investment)) {

    respond(404, [

        'success' => false,

        'message' => 'Investment not found.'

    ]);
}


/*
|--------------------------------------------------------------------------
| ONLY CANCEL UNPAID PENDING INVESTMENTS
|--------------------------------------------------------------------------
*/

if (

    ($investment['status'] ?? '') !== 'pending'

) {

    respond(409, [

        'success' => false,

        'message' =>

        'This investment is no longer pending.'

    ]);
}


if (

    ($investment['payment_status'] ?? '') !==

    'awaiting_payment'

) {

    respond(409, [

        'success' => false,

        'message' =>

        'Payment has already been submitted for this investment.'

    ]);
}


/*
|--------------------------------------------------------------------------
| CANCEL
|--------------------------------------------------------------------------
*/

$updateResponse =

    $supabase->updateRow(

        'investments',

        'id=eq.' .

        urlencode($investmentId),

        [

            'status' => 'cancelled',

            'updated_at' => gmdate('c')

        ]

    );


if (

    ($updateResponse['status'] ?? 500) < 200 ||

    ($updateResponse['status'] ?? 500) >= 300

) {

    error_log(

        'INVESTMENT CANCELLATION FAILED: ' .

            json_encode(

                $updateResponse

            )

    );

    respond(500, [

        'success' => false,

        'message' =>

        'The investment could not be cancelled.',

        // 'debug' => $updateResponse

    ]);
}


respond(200, [

    'success' => true,

    'message' =>

    'Investment cancelled successfully.'

]);
