<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/session.php';

header('Content-Type: application/json; charset=utf-8');


/*
|--------------------------------------------------------------------------
| JSON RESPONSE
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
| CATCH PHP ERRORS AS JSON
|--------------------------------------------------------------------------
*/

set_exception_handler(function (Throwable $exception): void {

    error_log($exception->getMessage());

    respond(500, [

        'success' => false,

        'message' => 'A server error occurred. Please try again.'

    ]);

});


/*
|--------------------------------------------------------------------------
| AUTHENTICATION
|--------------------------------------------------------------------------
*/

requireAuth();

$userId = currentUserId();

if (!$userId) {

    respond(401, [

        'success' => false,

        'message' => 'Authentication required.'

    ]);

}


/*
|--------------------------------------------------------------------------
| REQUEST METHOD
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
| CSRF TOKEN
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

        'message' => 'Invalid security token.'

    ]);

}


/*
|--------------------------------------------------------------------------
| READ REQUEST
|--------------------------------------------------------------------------
*/

$rawInput = file_get_contents('php://input');

$data = json_decode(

    $rawInput,

    true

);

if (!is_array($data)) {

    respond(400, [

        'success' => false,

        'message' => 'Invalid request data.'

    ]);

}


/*
|--------------------------------------------------------------------------
| PACKAGE LEVEL
|--------------------------------------------------------------------------
*/

$packageLevel = filter_var(

    $data['package_level'] ?? null,

    FILTER_VALIDATE_INT

);

if (

    $packageLevel === false ||

    $packageLevel === null ||

    $packageLevel < 1 ||

    $packageLevel > 10

) {

    respond(422, [

        'success' => false,

        'message' => 'Invalid investment package.'

    ]);

}


/*
|--------------------------------------------------------------------------
| SERVER-SIDE PACKAGES
|--------------------------------------------------------------------------
*/

$packages = [

    1 => 50,

    2 => 100,

    3 => 300,

    4 => 500,

    5 => 750,

    6 => 1000,

    7 => 2000,

    8 => 3000,

    9 => 4000,

    10 => 5000

];


$amount = (float) $packages[$packageLevel];


/*
|--------------------------------------------------------------------------
| ROI
|--------------------------------------------------------------------------
*/

$roiPercentage = 10.00;


$expectedProfit = round(

    $amount * ($roiPercentage / 100),

    2

);


$totalReturn = round(

    $amount + $expectedProfit,

    2

);


/*
|--------------------------------------------------------------------------
| CHECK EXISTING PENDING INVESTMENT
|--------------------------------------------------------------------------
*/

$existingInvestment = $supabase->selectRow(

    'investments',

    'user_id=eq.' . urlencode($userId) .

    '&status=eq.pending' .

    '&select=id' .

    '&limit=1'

);


if (

    ($existingInvestment['status'] ?? 500) >= 400

) {

    error_log(

        'Pending investment check failed: ' .

        json_encode($existingInvestment)

    );

    respond(500, [

        'success' => false,

        'message' =>

            'Unable to verify existing investments.'

    ]);

}


if (

    is_array($existingInvestment['body'] ?? null) &&

    count($existingInvestment['body']) > 0

) {

    respond(409, [

        'success' => false,

        'message' =>

            'You already have a pending investment.'

    ]);

}


/*
|--------------------------------------------------------------------------
| UNIQUE REFERENCE
|--------------------------------------------------------------------------
*/

$investmentReference =

    'MR-' .

    strtoupper(

        bin2hex(

            random_bytes(8)

        )

    );


/*
|--------------------------------------------------------------------------
| INSERT INVESTMENT
|--------------------------------------------------------------------------
*/

$investmentData = [

    'user_id' => $userId,

    'package_level' => $packageLevel,

    'principal_amount' => $amount,

    'currency' => 'USDT',

    'roi_percentage' => $roiPercentage,

    'expected_profit' => $expectedProfit,

    'total_return' => $totalReturn,

    'investment_duration_days' => 15,

    'investment_reference' => $investmentReference,

    'status' => 'pending',

    'payment_status' => 'awaiting_payment',

    'created_at' => gmdate('c'),

    'updated_at' => gmdate('c')

];


$investmentResponse = $supabase->insertRow(

    'investments',

    $investmentData

);


/*
|--------------------------------------------------------------------------
| CHECK INSERT RESPONSE
|--------------------------------------------------------------------------
*/

if (

    ($investmentResponse['status'] ?? 500) < 200 ||

    ($investmentResponse['status'] ?? 500) >= 300

) {

    error_log(

        'Investment creation failed: ' .

        json_encode($investmentResponse)

    );

    respond(500, [

        'success' => false,

        'message' =>

            'Unable to create investment. Please try again.'

    ]);

}


/*
|--------------------------------------------------------------------------
| CREATED INVESTMENT
|--------------------------------------------------------------------------
*/

$createdInvestment =

    $investmentResponse['body'][0] ?? null;


if (

    !is_array($createdInvestment) ||

    empty($createdInvestment['id'])

) {

    respond(500, [

        'success' => false,

        'message' =>

            'Investment was not created correctly.'

    ]);

}


/*
|--------------------------------------------------------------------------
| SUCCESS
|--------------------------------------------------------------------------
*/

respond(201, [

    'success' => true,

    'investment_id' =>

        $createdInvestment['id'],

    'message' =>

        'Investment created. Continue to payment.'

]);