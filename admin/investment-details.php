<?php

declare(strict_types=1);
require_once __DIR__ . '/includes/admin-auth.php';
/*
|--------------------------------------------------------------------------
| VALIDATE ID
|--------------------------------------------------------------------------
*/
$investmentId = trim($_GET['id'] ?? '');
if ($investmentId === '') {
    header('Location: investments.php');
    exit;
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
    http_response_code(404);
    exit('Investment not found.');
}
$investment = $response['body'][0];
/*
|--------------------------------------------------------------------------
| LOAD INVESTOR
|--------------------------------------------------------------------------
*/
$userResponse = $supabase->selectRow(
    'users',
    'id=eq.' .
        urlencode($investment['user_id']) .
        '&select=*' .
        '&limit=1'
);
$user = $userResponse['body'][0] ?? [];
?>
<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">
<meta
    name="csrf-token"
    content="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
    <title>

        Investment Details

    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet">

    <link
        rel="stylesheet"
        href="assets/css/admin.css">

    <link
        rel="stylesheet"
        href="assets/css/investment-details.css">

</head>

<body>

    <div class="admin-layout">

        <?php require 'includes/admin-sidebar.php'; ?>

        <div class="admin-content">

            <div class="container-fluid py-4">
                <div
                    class="page-header">

                    <a
                        href="investments.php"
                        class="back-btn">

                        <i class="bi bi-arrow-left"></i>

                        Back

                    </a>

                    <h2>

                        Investment Details

                    </h2>

                </div>
                <div class="details-card">

                    <h4>

                        Investment Summary

                    </h4>

                    <div class="details-grid">

                        <div>

                            <label>

                                Reference

                            </label>

                            <p>

                                <?= htmlspecialchars($investment['reference']) ?>

                            </p>

                        </div>

                        <div>

                            <label>

                                Package

                            </label>

                            <p>

                                Level <?= htmlspecialchars($investment['package_level']) ?>

                            </p>

                        </div>

                        <div>

                            <label>

                                Amount

                            </label>

                            <p>

                                <?= number_format($investment['amount'], 2) ?>

                                USDT

                            </p>

                        </div>

                        <div>

                            <label>

                                ROI

                            </label>

                            <p>

                                <?= htmlspecialchars($investment['roi_percentage']) ?>%

                            </p>

                        </div>

                        <div>

                            <label>

                                Expected Return

                            </label>

                            <p>

                                <?= number_format($investment['expected_return'], 2) ?>

                                USDT

                            </p>

                        </div>

                        <div>

                            <label>

                                Duration

                            </label>

                            <p>

                                <?= htmlspecialchars($investment['duration_days']) ?>

                                Days

                            </p>

                        </div>

                    </div>

                </div>
                <div class="details-card">

                    <h4>

                        Investor

                    </h4>

                    <div class="details-grid">

                        <div>

                            <label>

                                Name

                            </label>

                            <p>

                                <?= htmlspecialchars($user['full_name'] ?? '-') ?>

                            </p>

                        </div>

                        <div>

                            <label>

                                Email

                            </label>

                            <p>

                                <?= htmlspecialchars($user['email'] ?? '-') ?>

                            </p>

                        </div>

                        <div>

                            <label>

                                Phone

                            </label>

                            <p>

                                <?= htmlspecialchars($user['phone_number'] ?? '-') ?>

                            </p>

                        </div>

                        <div>

                            <label>

                                Referral

                            </label>

                            <p>

                                <?= htmlspecialchars($user['referral_code'] ?? '-') ?>

                            </p>

                        </div>

                    </div>

                </div>
                <div class="details-card">

                    <h4>

                        Payment Information

                    </h4>

                    <div class="details-grid">

                        <div>

                            <label>

                                Network

                            </label>

                            <p>

                                TRC20

                            </p>

                        </div>

                        <div>

                            <label>

                                Wallet

                            </label>

                            <p>

                                <?= htmlspecialchars($investment['wallet_address'] ?? '-') ?>

                            </p>

                        </div>

                        <div>

                            <label>

                                TXID

                            </label>

                            <p class="txid">

                                <?= htmlspecialchars($investment['transaction_hash'] ?? '-') ?>

                            </p>

                        </div>

                        <div>

                            <label>

                                Payment Status

                            </label>

                            <p>

                                <?= ucwords(

                                    str_replace(

                                        '_',

                                        ' ',

                                        $investment['payment_status']

                                    )

                                ) ?>

                            </p>

                        </div>

                    </div>

                </div>
                <div class="details-card verify-card">

                    <h4>

                        Verification

                    </h4>

                    <p>

                        Review the payment information carefully before approving or rejecting this investment.

                    </p>

                    <div class="verify-actions">

                        <button

                            id="approveBtn"

                            class="btn btn-success"

                            data-id="<?= $investment['id'] ?>">

                            <i class="bi bi-check-circle-fill"></i>

                            Approve Payment

                        </button>

                        <button

                            id="rejectBtn"

                            class="btn btn-danger"

                            data-id="<?= $investment['id'] ?>">

                            <i class="bi bi-x-circle-fill"></i>

                            Reject Payment

                        </button>

                    </div>

                </div>
                <div class="details-card">

                    <h4>

                        Timeline

                    </h4>

                    <ul class="timeline">

                        <li>

                            Investment Created

                            <span>

                                <?= date(

                                    'd M Y H:i',

                                    strtotime($investment['created_at'])

                                ) ?>

                            </span>

                        </li>

                        <li>

                            Payment Submitted

                            <span>

                                <?= !empty($investment['payment_submitted_at'])

                                    ? date(

                                        'd M Y H:i',

                                        strtotime($investment['payment_submitted_at'])

                                    )

                                    : '-'; ?>

                            </span>

                        </li>

                        <li>

                            Status

                            <span>

                                <?= ucwords(

                                    str_replace(

                                        '_',

                                        ' ',

                                        $investment['status']

                                    )

                                ) ?>

                            </span>

                        </li>

                    </ul>

                </div>
            </div>

        </div>

    </div>
<!-- ==========================================
APPROVE MODAL
=========================================== -->

<div class="modal fade" id="approveModal" tabindex="-1">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title">

                    <i class="bi bi-check-circle-fill text-success me-2"></i>

                    Approve Investment

                </h5>

                <button
                    class="btn-close"
                    data-bs-dismiss="modal"></button>

            </div>

            <div class="modal-body">

                <p>

                    You are about to activate this investment.

                </p>

                <div class="alert alert-success">

                    <strong>

                        This action will:

                    </strong>

                    <ul class="mb-0 mt-2">

                        <li>Activate the investment</li>

                        <li>Start ROI calculation</li>

                        <li>Notify the investor</li>

                    </ul>

                </div>

            </div>

            <div class="modal-footer">

                <button
                    class="btn btn-light"
                    data-bs-dismiss="modal">

                    Cancel

                </button>

                <button
                    class="btn btn-success"
                    id="confirmApprove">

                    <i class="bi bi-check-circle"></i>

                    Approve

                </button>

            </div>

        </div>

    </div>

</div>



<!-- ==========================================
REJECT MODAL
=========================================== -->

<div class="modal fade" id="rejectModal" tabindex="-1">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title">

                    <i class="bi bi-x-circle-fill text-danger me-2"></i>

                    Reject Investment

                </h5>

                <button
                    class="btn-close"
                    data-bs-dismiss="modal"></button>

            </div>

            <div class="modal-body">

                <label class="form-label">

                    Rejection Reason

                </label>

                <select
                    id="rejectReason"
                    class="form-select">

                    <option value="">

                        Select Reason

                    </option>

                    <option>

                        Wrong Amount

                    </option>

                    <option>

                        Duplicate TXID

                    </option>

                    <option>

                        Wallet Mismatch

                    </option>

                    <option>

                        Invalid Transaction

                    </option>

                    <option>

                        Other

                    </option>

                </select>

                <div class="mt-3">

                    <label class="form-label">

                        Additional Comment

                    </label>

                    <textarea
                        id="rejectComment"
                        rows="4"
                        class="form-control"
                        placeholder="Optional..."></textarea>

                </div>

            </div>

            <div class="modal-footer">

                <button
                    class="btn btn-light"
                    data-bs-dismiss="modal">

                    Cancel

                </button>

                <button
                    class="btn btn-danger"
                    id="confirmReject">

                    Reject Investment

                </button>

            </div>

        </div>

    </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/notifications.js"></script>

    <script src="assets/js/admin.js"></script>

    <script src="assets/js/investment-details.js"></script>

</body>

</html>