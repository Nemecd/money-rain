<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| MONEY RAIN ADMIN AUTHENTICATION
|--------------------------------------------------------------------------
|
| Handles administrator login securely.
|
| Features
| --------
| • POST only
| • AJAX only
| • CSRF protection
| • Rate limiting
| • Password verification
| • Secure sessions
| • Session fingerprinting
| • Login logging
| • JSON responses only
|
*/

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
| EXCEPTION HANDLER
|--------------------------------------------------------------------------
*/

set_exception_handler(function (Throwable $exception): void {

    error_log(
        'ADMIN LOGIN ERROR: ' .
            $exception->getMessage()
    );

    respond(
        500,
        [
            'success' => false,
            'message' => 'An unexpected server error occurred.'
        ]
    );
});

/*
|--------------------------------------------------------------------------
| REQUEST METHOD
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    respond(
        405,
        [
            'success' => false,
            'message' => 'Method not allowed.'
        ]
    );
}

/*
|--------------------------------------------------------------------------
| AJAX REQUEST ONLY
|--------------------------------------------------------------------------
*/

if (

    ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')

    !==

    'XMLHttpRequest'

) {

    respond(
        403,
        [
            'success' => false,
            'message' => 'Invalid request.'
        ]
    );
}

/*
|--------------------------------------------------------------------------
| CSRF VALIDATION
|--------------------------------------------------------------------------
*/

$csrfToken =

    $_SERVER['HTTP_X_CSRF_TOKEN']

    ??

    '';

if (

    empty($_SESSION['csrf_token'])

    ||

    empty($csrfToken)

    ||

    !hash_equals(

        $_SESSION['csrf_token'],

        $csrfToken

    )

) {

    respond(
        403,
        [
            'success' => false,
            'message' => 'Invalid security token.'
        ]
    );
}

/*
|--------------------------------------------------------------------------
| REQUEST BODY
|--------------------------------------------------------------------------
*/

$input = json_decode(

    file_get_contents('php://input'),

    true

);

if (!is_array($input)) {

    respond(
        400,
        [
            'success' => false,
            'message' => 'Invalid request body.'
        ]
    );
}

$email = strtolower(

    trim(

        (string)

        ($input['email'] ?? '')

    )

);

$password =

    (string)

    ($input['password'] ?? '');

/*
|--------------------------------------------------------------------------
| VALIDATION
|--------------------------------------------------------------------------
*/

if (

    $email === ''

    ||

    $password === ''

) {

    respond(
        422,
        [
            'success' => false,
            'message' => 'Email and password are required.'
        ]
    );
}

/*
|--------------------------------------------------------------------------
| CLIENT IP
|--------------------------------------------------------------------------
*/

$ipAddress =

    $_SERVER['REMOTE_ADDR']

    ??

    'unknown';

/*
|--------------------------------------------------------------------------
| RATE LIMIT
|--------------------------------------------------------------------------
*/

$attempts =

    $supabase->selectRow(

        'admin_login_attempts',

        'email=eq.' .

            urlencode($email) .

            '&ip_address=eq.' .

            urlencode($ipAddress) .

            '&success=eq.false' .

            '&attempted_at=gte.' .

            urlencode(

                gmdate(

                    'c',

                    strtotime('-15 minutes')

                )

            ) .

            '&select=id'

    );

$failedAttempts =

    is_array($attempts['body'] ?? null)

    ?

    count($attempts['body'])

    :

    0;

if (

    $failedAttempts >= 5

) {

    respond(

        429,

        [

            'success' => false,

            'message' =>

            'Too many failed login attempts. Please try again later.'

        ]

    );
}

/*
|--------------------------------------------------------------------------
| FIND ADMIN
|--------------------------------------------------------------------------
*/

$response =

    $supabase->selectRow(

        'admins',

        'email=eq.' .

            urlencode($email) .

            '&status=eq.active' .

            '&select=*' .

            '&limit=1'

    );

if (

    ($response['status'] ?? 500)

    >=

    400

) {

    respond(

        500,

        [

            'success' => false,

            'message' =>

            'Unable to verify administrator.'

        ]

    );
}

$admin =

    $response['body'][0]

    ??

    null;

if (!$admin) {

    $supabase->insertRow(

        'admin_login_attempts',

        [

            'email' => $email,

            'ip_address' => $ipAddress,

            'success' => false

        ]

    );

    respond(

        401,

        [

            'success' => false,

            'message' =>

            'Invalid email or password.'

        ]

    );
}

/*
|--------------------------------------------------------------------------
| PASSWORD VERIFY
|--------------------------------------------------------------------------
*/

if (

    !password_verify(

        $password,

        $admin['password_hash']

    )

) {

    $supabase->insertRow(

        'admin_login_attempts',

        [

            'email' => $email,

            'ip_address' => $ipAddress,

            'success' => false

        ]

    );

    respond(

        401,

        [

            'success' => false,

            'message' =>

            'Invalid email or password.'

        ]

    );
}

/*
/*
|--------------------------------------------------------------------------
| CREATE SECURE SESSION
|--------------------------------------------------------------------------
*/

session_regenerate_id(true);

$_SESSION['is_admin'] = true;

$_SESSION['admin_id'] = $admin['id'];

$_SESSION['admin_email'] = $admin['email'];

$_SESSION['admin_name'] = $admin['full_name'] ?? '';

$_SESSION['admin_role'] = $admin['role'];

/*
|--------------------------------------------------------------------------
| SESSION FINGERPRINT
|--------------------------------------------------------------------------
|
| NOTE:
| We intentionally DO NOT include REMOTE_ADDR.
| Including IP addresses can cause users to be logged
| out if their IP changes (mobile networks, proxies,
| Cloudflare, VPNs, etc.).
|
*/

$_SESSION['fingerprint'] = hash(

    'sha256',

    ($_SERVER['HTTP_USER_AGENT'] ?? '') .

        session_id()

);

/*
|--------------------------------------------------------------------------
| SESSION ACTIVITY
|--------------------------------------------------------------------------
*/

$_SESSION['last_activity'] = time();

/*
|--------------------------------------------------------------------------
| NEW CSRF TOKEN
|--------------------------------------------------------------------------
*/

$_SESSION['csrf_token'] = bin2hex(

    random_bytes(32)

);

/*
|--------------------------------------------------------------------------
| UPDATE LAST LOGIN
|--------------------------------------------------------------------------
*/

$supabase->updateRow(

    'admins',

    'id=eq.' .

        urlencode($admin['id']),

    [

        'last_login' => gmdate('c')

    ]

);

/*
|--------------------------------------------------------------------------
| LOG SUCCESSFUL LOGIN
|--------------------------------------------------------------------------
*/

$supabase->insertRow(

    'admin_login_attempts',

    [

        'email' => $email,

        'ip_address' => $ipAddress,

        'success' => true,

        'attempted_at' => gmdate('c')

    ]

);

/*
|--------------------------------------------------------------------------
| OPTIONAL AUDIT LOG
|--------------------------------------------------------------------------
|
| Only executes if the audit_logs table exists.
|
*/

try {

    $supabase->insertRow(

        'audit_logs',

        [

            'admin_id' => $admin['id'],

            'action' => 'Administrator Login',

            'reference' => null,

            'description' => 'Successful administrator login.',

            'ip_address' => $ipAddress,

            'created_at' => gmdate('c')

        ]

    );
} catch (Throwable $e) {

    /*
    Ignore if audit logging isn't configured yet.
    */
}

/*
|--------------------------------------------------------------------------
| SUCCESS RESPONSE
|--------------------------------------------------------------------------
*/

respond(

    200,

    [

        'success' => true,

        'message' => 'Login successful.',

        'admin' => [

            'id' => $admin['id'],

            'name' => $admin['full_name'] ?? '',

            'email' => $admin['email'],

            'role' => $admin['role']

        ],

        'csrf_token' => $_SESSION['csrf_token']

    ]

);
