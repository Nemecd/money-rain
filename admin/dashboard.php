<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/admin-auth.php';

/*
|--------------------------------------------------------------------------
| DASHBOARD STATISTICS
|--------------------------------------------------------------------------
*/

$totalUsers = 0;
$totalActiveInvestments = 0;
$totalDeposits = 0;
$pendingPayments = 0;
$pendingWithdrawals = 0;
$totalReferrals = 0;

/*
|--------------------------------------------------------------------------
| TOTAL USERS
|--------------------------------------------------------------------------
*/

$usersResponse = $supabase->selectRow(
    'users',
    'select=id'
);

if (($usersResponse['status'] ?? 500) < 300) {
    $totalUsers = count($usersResponse['body'] ?? []);
}

/*
|--------------------------------------------------------------------------
| ACTIVE INVESTMENTS
|--------------------------------------------------------------------------
*/

$activeResponse = $supabase->selectRow(
    'investments',
    'status=eq.active&select=id'
);

if (($activeResponse['status'] ?? 500) < 300) {
    $totalActiveInvestments = count($activeResponse['body'] ?? []);
}

/*
|--------------------------------------------------------------------------
| TOTAL DEPOSITS
|--------------------------------------------------------------------------
*/

$depositResponse = $supabase->selectRow(
    'investments',
    'payment_status=eq.payment_verified&select=amount'
);

if (($depositResponse['status'] ?? 500) < 300) {

    foreach ($depositResponse['body'] ?? [] as $investment) {

        $totalDeposits += (float)$investment['amount'];
    }
}

/*
|--------------------------------------------------------------------------
| PENDING PAYMENTS
|--------------------------------------------------------------------------
*/

$pendingResponse = $supabase->selectRow(
    'investments',
    'payment_status=eq.payment_submitted&select=*'
);

$pendingPaymentsList = $pendingResponse['body'] ?? [];

$pendingPayments = count($pendingPaymentsList);

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard | Money Rain</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <link
        rel="stylesheet"
        href="assets/css/admin.css">

</head>

<body>

    <div class="admin-layout">

        <!-- SIDEBAR -->

        <aside class="admin-sidebar">

            <div class="sidebar-logo">

                <i class="bi bi-shield-lock-fill"></i>

                <h4>Money Rain</h4>

                <span>Admin Panel</span>

            </div>

            <ul class="sidebar-menu">

                <li class="active">

                    <a href="dashboard.php">

                        <i class="bi bi-grid"></i>

                        Dashboard

                    </a>

                </li>

                <li>

                    <a href="investments.php">

                        <i class="bi bi-cash-stack"></i>

                        Investments

                    </a>

                </li>

                <li>

                    <a href="users.php">

                        <i class="bi bi-people"></i>

                        Users

                    </a>

                </li>

                <li>

                    <a href="withdrawals.php">

                        <i class="bi bi-wallet2"></i>

                        Withdrawals

                    </a>

                </li>

                <li>

                    <a href="settings.php">

                        <i class="bi bi-gear"></i>

                        Settings

                    </a>

                </li>

                <li>

                    <a href="logout.php">

                        <i class="bi bi-box-arrow-right"></i>

                        Logout

                    </a>

                </li>

            </ul>

        </aside>

        <!-- CONTENT -->

        <main class="admin-content">

            <div class="container-fluid py-4">

                <div class="d-flex justify-content-between align-items-center mb-4">

                    <div>

                        <h2>

                            Dashboard

                        </h2>

                        <p class="text-muted">

                            Welcome back,

                            <strong>

                                <?= htmlspecialchars($_SESSION['admin_name']) ?>

                            </strong>

                        </p>

                    </div>

                </div>

                <!-- STAT CARDS -->

                <div class="row g-4">

                    <div class="col-xl-3 col-md-6">

                        <div class="stat-card">

                            <div class="stat-icon purple">

                                <i class="bi bi-people-fill"></i>

                            </div>

                            <h3>

                                <?= number_format($totalUsers) ?>

                            </h3>

                            <p>Total Users</p>

                        </div>

                    </div>

                    <div class="col-xl-3 col-md-6">

                        <div class="stat-card">

                            <div class="stat-icon green">

                                <i class="bi bi-graph-up-arrow"></i>

                            </div>

                            <h3>

                                <?= number_format($totalActiveInvestments) ?>

                            </h3>

                            <p>Active Investments</p>

                        </div>

                    </div>

                    <div class="col-xl-3 col-md-6">

                        <div class="stat-card">

                            <div class="stat-icon orange">

                                <i class="bi bi-hourglass-split"></i>

                            </div>

                            <h3>

                                <?= number_format($pendingPayments) ?>

                            </h3>

                            <p>Pending Payments</p>

                        </div>

                    </div>

                    <div class="col-xl-3 col-md-6">

                        <div class="stat-card">

                            <div class="stat-icon blue">

                                <i class="bi bi-currency-dollar"></i>

                            </div>

                            <h3>

                                <?= number_format($totalDeposits, 2) ?>

                                USDT

                            </h3>

                            <p>Total Deposits</p>

                        </div>

                    </div>

                </div>

                <!-- PENDING PAYMENTS -->

                <div class="admin-card mt-5">

                    <div class="d-flex justify-content-between align-items-center mb-4">

                        <h4>

                            Pending Payment Verification

                        </h4>

                        <a
                            href="investments.php"
                            class="btn btn-admin">

                            View All

                        </a>

                    </div>

                    <div class="table-responsive">

                        <table class="table align-middle">

                            <thead>

                                <tr>

                                    <th>User ID</th>

                                    <th>Package</th>

                                    <th>Amount</th>

                                    <th>Status</th>

                                    <th></th>

                                </tr>

                            </thead>

                            <tbody>

                                <?php if (empty($pendingPaymentsList)): ?>

                                    <tr>

                                        <td colspan="5"
                                            class="text-center text-muted py-5">

                                            No pending payments.

                                        </td>

                                    </tr>

                                <?php else: ?>

                                    <?php foreach ($pendingPaymentsList as $investment): ?>

                                        <tr>

                                            <td>

                                                <?= htmlspecialchars($investment['user_id']) ?>

                                            </td>

                                            <td>

                                                Level <?= htmlspecialchars($investment['package_level']) ?>

                                            </td>

                                            <td>

                                                <?= number_format($investment['amount'], 2) ?>

                                                USDT

                                            </td>

                                            <td>

                                                <span class="badge bg-warning">

                                                    Submitted

                                                </span>

                                            </td>

                                            <td>

                                                <a
                                                    href="investment-details.php?id=<?= urlencode($investment['id']) ?>"
                                                    class="btn btn-sm btn-primary">

                                                    Review

                                                </a>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                <?php endif; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        </main>

    </div>

    <script src="assets/js/admin.js"></script>

</body>

</html>