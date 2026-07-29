<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/session.php';

requireAuth();

$userId = currentUserId();

$investmentId = $_GET['investment'] ?? '';

if (!is_string($investmentId) || $investmentId === '') {
    header('Location: invest.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Retrieve investment belonging to current user
|--------------------------------------------------------------------------
*/

$investmentResponse = $supabase->selectRow(
    'investments',
    'id=eq.' . urlencode($investmentId) .
    '&user_id=eq.' . urlencode($userId) .
    '&select=*' .
    '&limit=1'
);

$investment = $investmentResponse['body'][0] ?? null;

if (!is_array($investment)) {
    header('Location: invest.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Only pending investments can reach payment
|--------------------------------------------------------------------------
*/

if (
    ($investment['status'] ?? '') !== 'pending' ||
    ($investment['payment_status'] ?? '') !== 'awaiting_payment'
) {
    header(
        'Location: investment-details.php?id=' .
        urlencode($investmentId)
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| Generate CSRF token
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['csrf_token'])) {

    $_SESSION['csrf_token'] = bin2hex(
        random_bytes(32)
    );

}

/*
|--------------------------------------------------------------------------
| Payment configuration
|--------------------------------------------------------------------------
|
| Replace these values with your actual company wallet details.
|
*/

$paymentCurrency = 'USDT';

$paymentNetwork = 'BEP20';

$walletAddress = 'YOUR_USDT_BEP20_WALLET_ADDRESS';

$amount = (float) $investment['principal_amount'];

$formattedAmount = number_format(
    $amount,
    2
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Complete Payment | Money Rain</title>

    <meta
        name="csrf-token"
        content="<?= htmlspecialchars(
            $_SESSION['csrf_token'],
            ENT_QUOTES,
            'UTF-8'
        ) ?>"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <link
        rel="stylesheet"
        href="assets/css/crypto-payment.css"
    >

</head>

<body>

<main class="crypto-payment-page">

    <div class="container py-5">

        <div class="payment-header">

            <a
                href="invest.php"
                class="back-link"
            >

                <i class="bi bi-arrow-left"></i>

                Back to Investment Packages

            </a>

            <h1>

                Complete Your Investment

            </h1>

            <p>

                Send the exact USDT amount to the wallet address below.

            </p>

        </div>


        <div class="row g-4 justify-content-center">

            <!-- INVESTMENT SUMMARY -->

            <div class="col-lg-5">

                <div class="payment-card">

                    <div class="card-header-custom">

                        <div class="header-icon">

                            <i class="bi bi-graph-up-arrow"></i>

                        </div>

                        <div>

                            <h4>

                                Investment Summary

                            </h4>

                            <span>

                                <?= htmlspecialchars(
                                    $investment['investment_reference'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </span>

                        </div>

                    </div>


                    <div class="summary-list">

                        <div class="summary-row">

                            <span>

                                Package

                            </span>

                            <strong>

                                Level <?= (int) $investment['package_level'] ?>

                            </strong>

                        </div>


                        <div class="summary-row highlight-row">

                            <span>

                                Amount to Pay

                            </span>

                            <strong>

                                <?= $formattedAmount ?> USDT

                            </strong>

                        </div>


                        <div class="summary-row">

                            <span>

                                ROI

                            </span>

                            <strong>

                                <?= htmlspecialchars(
                                    (string) $investment['roi_percentage'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>%

                            </strong>

                        </div>


                        <div class="summary-row">

                            <span>

                                Expected Profit

                            </span>

                            <strong>

                                <?= number_format(
                                    (float) $investment['expected_profit'],
                                    2
                                ) ?> USDT

                            </strong>

                        </div>


                        <div class="summary-row">

                            <span>

                                Investment Cycle

                            </span>

                            <strong>

                                <?= (int) $investment[
                                    'investment_duration_days'
                                ] ?> Days

                            </strong>

                        </div>

                    </div>

                </div>

            </div>


            <!-- PAYMENT CARD -->

            <div class="col-lg-7">

                <div class="payment-card">

                    <div class="card-header-custom">

                        <div class="header-icon crypto-icon">

                            <i class="bi bi-currency-bitcoin"></i>

                        </div>

                        <div>

                            <h4>

                                Make Crypto Payment

                            </h4>

                            <span>

                                Send payment using the details below.

                            </span>

                        </div>

                    </div>


                    <div class="payment-alert">

                        <i class="bi bi-exclamation-triangle-fill"></i>

                        <p>

                            Send only

                            <strong>USDT</strong>

                            using the

                            <strong>TRC20</strong>

                            network to this address.

                            Sending the wrong cryptocurrency or using the wrong network may result in loss of funds.

                        </p>

                    </div>


                    <div class="payment-detail">

                        <label>

                            Payment Currency

                        </label>

                        <div class="detail-value">

                            <strong>

                                <?= $paymentCurrency ?>

                            </strong>

                        </div>

                    </div>


                    <div class="payment-detail">

                        <label>

                            Network

                        </label>

                        <div class="detail-value">

                            <strong>

                                <?= $paymentNetwork ?>

                            </strong>

                        </div>

                    </div>


                    <div class="payment-detail">

                        <label>

                            Amount to Send

                        </label>

                        <div class="amount-box">

                            <?= $formattedAmount ?> USDT

                        </div>

                    </div>


                    <div class="payment-detail">

                        <label>

                            Wallet Address

                        </label>

                        <div class="wallet-box">

                            <span id="walletAddress">

                                <?= htmlspecialchars(
                                    $walletAddress,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </span>

                            <button
                                type="button"
                                id="copyWallet"
                                class="copy-btn"
                            >

                                <i class="bi bi-copy"></i>

                                Copy

                            </button>

                        </div>

                    </div>


                    <div class="qr-placeholder">

                        <i class="bi bi-qr-code"></i>

                        <p>

                            Wallet QR Code

                        </p>

                        <small>

                            QR generation will be connected after the final wallet address is added.

                        </small>

                    </div>


                    <hr>


                    <form
                        id="paymentForm"
                        novalidate
                    >

                        <input
                            type="hidden"
                            name="investment_id"
                            value="<?= htmlspecialchars(
                                $investment['id'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >


                        <div class="mb-3">

                            <label
                                for="transactionHash"
                                class="form-label"
                            >

                                Transaction Hash / TXID

                            </label>

                            <input
                                type="text"
                                id="transactionHash"
                                name="transaction_hash"
                                class="form-control"
                                placeholder="Enter your crypto transaction hash"
                                required
                                minlength="20"
                                maxlength="255"
                            >

                            <div class="form-text">

                                After sending the USDT, paste the transaction hash here.

                            </div>

                        </div>


                        <button
                            type="submit"
                            class="btn submit-payment-btn"
                            id="submitPayment"
                        >

                            Submit Payment for Verification

                            <i class="bi bi-arrow-right"></i>

                        </button>

                    </form>

                </div>

            </div>

        </div>

    </div>

</main>


<script
    src="assets/js/crypto-payment.js"
></script>

</body>

</html>