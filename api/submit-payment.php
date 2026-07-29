<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/session.php';

header('Content-Type: application/json; charset=utf-8');


/*
|--------------------------------------------------------------------------
| JSON RESPONSE HELPER
|--------------------------------------------------------------------------
*/

function respond(int $status, array $data): never
{
    http_response_code($status);

    echo json_encode(
        $data,
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| CATCH UNEXPECTED ERRORS
|--------------------------------------------------------------------------
*/

set_exception_handler(function (Throwable $exception): void {

    error_log($exception->getMessage());

    respond(500, [

        'success' => false,

        'message' =>
            'A server error occurred. Please try again.'

    ]);

});


/*
|--------------------------------------------------------------------------
| REQUIRE AUTHENTICATION
|--------------------------------------------------------------------------
*/

requireAuth();

$userId = currentUserId();

if (!$userId) {

    respond(401, [

        'success' => false,

        'message' =>
            'Authentication required.'

    ]);

}


/*
|--------------------------------------------------------------------------
| ONLY ALLOW POST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    respond(405, [

        'success' => false,

        'message' =>
            'Method not allowed.'

    ]);

}


/*
|--------------------------------------------------------------------------
| CSRF PROTECTION
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

        'message' =>
            'Invalid security token.'

    ]);

}


/*
|--------------------------------------------------------------------------
| READ JSON REQUEST
|--------------------------------------------------------------------------
*/

$rawInput =
    file_get_contents('php://input');

$data = json_decode(

    $rawInput,

    true

);

if (!is_array($data)) {

    respond(400, [

        'success' => false,

        'message' =>
            'Invalid request data.'

    ]);

}


/*
|--------------------------------------------------------------------------
| VALIDATE INVESTMENT ID
|--------------------------------------------------------------------------
*/

$investmentId =
    trim((string) ($data['investment_id'] ?? ''));

if (

    $investmentId === '' ||

    !preg_match(

        '/^[0-9a-fA-F-]{36}$/',

        $investmentId

    )

) {

    respond(422, [

        'success' => false,

        'message' =>
            'Invalid investment.'

    ]);

}


/*
|--------------------------------------------------------------------------
| VALIDATE TRANSACTION HASH
|--------------------------------------------------------------------------
*/

$transactionHash =
    trim((string) ($data['transaction_hash'] ?? ''));

if ($transactionHash === '') {

    respond(422, [

        'success' => false,

        'message' =>
            'Transaction hash is required.'

    ]);

}

if (strlen($transactionHash) < 20) {

    respond(422, [

        'success' => false,

        'message' =>
            'The transaction hash appears to be invalid.'

    ]);

}

if (strlen($transactionHash) > 255) {

    respond(422, [

        'success' => false,

        'message' =>
            'The transaction hash is too long.'

    ]);

}


/*
|--------------------------------------------------------------------------
| ALLOW SAFE TXID CHARACTERS ONLY
|--------------------------------------------------------------------------
|
| Crypto transaction hashes normally contain hexadecimal
| characters. We also allow common safe characters for
| compatibility with different networks.
|
*/

if (

    !preg_match(

        '/^[a-zA-Z0-9]+$/',

        $transactionHash

    )

) {

    respond(422, [

        'success' => false,

        'message' =>
            'Invalid transaction hash format.'

    ]);

}


/*
|--------------------------------------------------------------------------
| CHECK THAT INVESTMENT BELONGS TO USER
|--------------------------------------------------------------------------
*/

$investmentResponse = $supabase->selectRow(

    'investments',

    'id=eq.' . urlencode($investmentId) .

    '&user_id=eq.' . urlencode($userId) .

    '&select=*' .

    '&limit=1'

);


if (

    ($investmentResponse['status'] ?? 500) >= 400

) {

    error_log(

        'Investment lookup failed: ' .

        json_encode($investmentResponse)

    );

    respond(500, [

        'success' => false,

        'message' =>
            'Unable to verify investment.'

    ]);

}


$investment =
    $investmentResponse['body'][0] ?? null;


if (!is_array($investment)) {

    respond(404, [

        'success' => false,

        'message' =>
            'Investment not found.'

    ]);

}


/*
|--------------------------------------------------------------------------
| PREVENT DUPLICATE PAYMENT SUBMISSION
|--------------------------------------------------------------------------
*/

$currentPaymentStatus =
    $investment['payment_status'] ?? '';


if (

    $currentPaymentStatus ===

    'payment_submitted'

) {

    respond(409, [

        'success' => false,

        'message' =>
            'Payment has already been submitted for this investment.'

    ]);

}


if (

    $currentPaymentStatus ===

    'payment_verified'

) {

    respond(409, [

        'success' => false,

        'message' =>
            'This payment has already been verified.'

    ]);

}


/*
|--------------------------------------------------------------------------
| INVESTMENT MUST STILL BE PENDING
|--------------------------------------------------------------------------
*/

if (

    ($investment['status'] ?? '') !==

    'pending'

) {

    respond(409, [

        'success' => false,

        'message' =>
            'This investment can no longer receive a payment submission.'

    ]);

}


/*
|--------------------------------------------------------------------------
| CHECK IF TXID HAS ALREADY BEEN USED
|--------------------------------------------------------------------------
*/

$existingTransaction = $supabase->selectRow(

    'investments',

    'transaction_hash=eq.' .

    urlencode($transactionHash) .

    '&select=id,user_id' .

    '&limit=1'

);


if (

    ($existingTransaction['status'] ?? 500) >= 400

) {

    error_log(

        'Transaction hash lookup failed: ' .

        json_encode($existingTransaction)

    );

    respond(500, [

        'success' => false,

        'message' =>
            'Unable to verify transaction hash.'

    ]);

}


if (

    is_array($existingTransaction['body'] ?? null) &&

    count($existingTransaction['body']) > 0

) {

    respond(409, [

        'success' => false,

        'message' =>
            'This transaction hash has already been submitted.'

    ]);

}


/*
|--------------------------------------------------------------------------
| UPDATE PAYMENT STATUS
|--------------------------------------------------------------------------
*/

$updateData = [

    'transaction_hash' =>
        $transactionHash,

    'payment_status' =>
        'payment_submitted',

    'updated_at' =>
        gmdate('c')

];


$updateResponse = $supabase->updateRow(

    'investments',

    'id=eq.' .

    urlencode($investmentId) .

    '&user_id=eq.' .

    urlencode($userId),

    $updateData

);


/*
|--------------------------------------------------------------------------
| CHECK UPDATE RESULT
|--------------------------------------------------------------------------
*/

if (

    ($updateResponse['status'] ?? 500) < 200 ||

    ($updateResponse['status'] ?? 500) >= 300

) {

    error_log(

        'Payment submission failed: ' .

        json_encode($updateResponse)

    );

    respond(500, [

        'success' => false,

        'message' =>
            'Unable to submit payment. Please try again.'

    ]);

}


/*
|--------------------------------------------------------------------------
| SUCCESS
|--------------------------------------------------------------------------
*/

respond(200, [

    'success' => true,

    'investment_id' =>
        $investmentId,

    'message' =>
        'Payment submitted successfully and is awaiting verification.'

]);