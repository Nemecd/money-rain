<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/session.php';

/*
|--------------------------------------------------------------------------
| NOT LOGGED IN
|--------------------------------------------------------------------------
*/

if (

    empty($_SESSION['is_admin']) ||

    $_SESSION['is_admin'] !== true

) {

    header(

        'Location: login.php'

    );

    exit;
}

/*
|--------------------------------------------------------------------------
| REQUIRED SESSION VALUES
|--------------------------------------------------------------------------
*/

if (

    empty($_SESSION['admin_id']) ||

    empty($_SESSION['admin_email']) ||

    empty($_SESSION['admin_role'])

) {

    session_destroy();

    header(

        'Location: login.php'

    );

    exit;
}

/*
|--------------------------------------------------------------------------
| VERIFY ADMIN STILL EXISTS
|--------------------------------------------------------------------------
*/

$response =

    $supabase->selectRow(

        'admins',

        'id=eq.' .

            urlencode(

                $_SESSION['admin_id']

            ) .

            '&status=eq.active' .

            '&select=id,role'

    );

if (

    ($response['status'] ?? 500)

    >= 400

) {

    session_destroy();

    header(

        'Location: login.php'

    );

    exit;
}

$admin =

    $response['body'][0]

    ?? null;

if (!$admin) {

    session_destroy();

    header(

        'Location: login.php'

    );

    exit;
}
$currentFingerprint = hash(
    'sha256',
    ($_SERVER['HTTP_USER_AGENT'] ?? '') .
        session_id()
);

if (
    empty($_SESSION['fingerprint']) ||
    !hash_equals($_SESSION['fingerprint'], $currentFingerprint)
) {

    session_unset();
    session_destroy();

    header("Location: login.php");

    exit;
}
/*
|--------------------------------------------------------------------------
| ROLE SYNC
|--------------------------------------------------------------------------
*/

$_SESSION['admin_role'] = $admin['role'];

/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (

    empty($_SESSION['csrf_token'])

) {

    $_SESSION['csrf_token'] =

        bin2hex(

            random_bytes(32)

        );
}

$timeout = 1800; // 30 minutes

if (
    isset($_SESSION['last_activity']) &&
    (time() - $_SESSION['last_activity']) > $timeout
) {

    session_unset();
    session_destroy();

    header("Location: login.php?timeout=1");

    exit;
}

$_SESSION['last_activity'] = time();
