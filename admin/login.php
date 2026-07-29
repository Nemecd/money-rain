<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

require_once __DIR__ . '/../includes/session.php';

/*
|--------------------------------------------------------------------------
| Redirect logged-in admins
|--------------------------------------------------------------------------
*/

if (!empty($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {

    header('Location: dashboard.php');

    exit;
}

/*
|--------------------------------------------------------------------------
| CSRF Token
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['csrf_token'])) {

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Admin Login | Money Rain</title>

    <meta
        name="robots"
        content="noindex,nofollow">

    <meta
        name="csrf-token"
        content="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">

    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <!-- Bootstrap Icons -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <!-- Google Font -->

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com">

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Admin CSS -->

    <link
        rel="stylesheet"
        href="assets/css/admin.css">

</head>

<body class="admin-login-page">

    <div class="admin-login-wrapper">

        <!-- Background Shapes -->

        <div class="admin-bg-circle circle-one"></div>

        <div class="admin-bg-circle circle-two"></div>

        <div class="admin-bg-circle circle-three"></div>

        <div class="login-card">

            <div class="text-center mb-4">

                <div class="admin-logo">

                    <i class="bi bi-shield-lock-fill"></i>

                </div>

                <h2 class="mt-3">

                    Money Rain

                </h2>

                <p class="text-muted">

                    Administration Portal

                </p>

            </div>

            <form
                id="adminLoginForm"
                autocomplete="off">

                <div class="mb-3">

                    <label class="form-label">

                        Email Address

                    </label>

                    <div class="input-group">

                        <span class="input-group-text">

                            <i class="bi bi-envelope-fill"></i>

                        </span>

                        <input
                            type="email"
                            id="email"
                            class="form-control"
                            placeholder="admin@moneyrain.com"
                            required>

                    </div>

                </div>

                <div class="mb-4">

                    <label class="form-label">

                        Password

                    </label>

                    <div class="input-group">

                        <span class="input-group-text">

                            <i class="bi bi-lock-fill"></i>

                        </span>

                        <input
                            type="password"
                            id="password"
                            class="form-control"
                            placeholder="Enter password"
                            required>

                        <button
                            class="btn password-toggle"
                            type="button"
                            id="togglePassword">

                            <i class="bi bi-eye"></i>

                        </button>

                    </div>

                </div>

                <button
                    class="btn btn-admin w-100"
                    id="loginBtn">

                    <span class="login-text">

                        Login to Dashboard

                    </span>

                    <span
                        class="spinner-border spinner-border-sm d-none"
                        id="loginSpinner"></span>

                </button>

            </form>

            <div class="admin-divider">

                Authorized Personnel Only

            </div>

            <div class="security-box">

                <i class="bi bi-shield-check"></i>

                This portal is protected. Unauthorized access is prohibited and monitored.

            </div>

        </div>

    </div>

    <!-- Notification Container -->

    <div id="notificationContainer"></div>

    <script src="../assets/js/notifications.js"></script>

    <script src="assets/js/admin.js"></script>

</body>

</html>