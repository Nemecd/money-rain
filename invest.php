<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

require_once __DIR__ . '/includes/session.php';

requireAuth();

$userId = currentUserId();


if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pendingInvestment = null;

$pendingInvestmentResponse = $supabase->selectRow(

    'investments',

    'user_id=eq.' . urlencode($userId) .

        '&status=eq.pending' .

        '&select=*' .

        '&order=created_at.desc' .

        '&limit=1'

);

if (

    ($pendingInvestmentResponse['status'] ?? 500) >= 200 &&

    ($pendingInvestmentResponse['status'] ?? 500) < 300 &&

    !empty($pendingInvestmentResponse['body'][0])

) {

    $pendingInvestment =
        $pendingInvestmentResponse['body'][0];
}

$packages = [
    [
        'level' => 1,
        'amount' => 50,
        'roi' => 10
    ],
    [
        'level' => 2,
        'amount' => 100,
        'roi' => 10
    ],
    [
        'level' => 3,
        'amount' => 300,
        'roi' => 10
    ],
    [
        'level' => 4,
        'amount' => 500,
        'roi' => 10
    ],
    [
        'level' => 5,
        'amount' => 750,
        'roi' => 10
    ],
    [
        'level' => 6,
        'amount' => 1000,
        'roi' => 10
    ],
    [
        'level' => 7,
        'amount' => 2000,
        'roi' => 10
    ],
    [
        'level' => 8,
        'amount' => 3000,
        'roi' => 10
    ],
    [
        'level' => 9,
        'amount' => 4000,
        'roi' => 10
    ],
    [
        'level' => 10,
        'amount' => 5000,
        'roi' => 10
    ]
];

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
        content="width=device-width, initial-scale=1.0">
    <meta
        name="csrf-token"
        content="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

    <title>Invest | Money Rain</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link
        rel="stylesheet"
        href="assets/css/invest.css">

</head>

<body>

    <div class="invest-page">

        <div class="container py-5">

            <!-- HEADER -->

            <div class="invest-header">

                <div>

                    <a
                        href="dashboard.php"
                        class="back-link">

                        <i class="bi bi-arrow-left"></i>

                        Back to Dashboard

                    </a>

                    <h1>

                        Invest with Money Rain

                    </h1>

                    <p>

                        Choose an investment package and start your USDT investment journey.

                    </p>

                </div>

                <div class="currency-badge">

                    <i class="bi bi-currency-bitcoin"></i>

                    USDT Investment

                </div>

            </div>


            <!-- IMPORTANT NOTICE -->

            <div class="investment-notice">

                <div class="notice-icon">

                    <i class="bi bi-shield-check"></i>

                </div>

                <div>

                    <h5>

                        Important Investment Information

                    </h5>

                    <p>

                        All investment packages are denominated in USDT.

                        After selecting a package, you will receive the approved cryptocurrency payment details for completing your investment.

                    </p>

                </div>

            </div>

            <div class="pending-investment-actions">

                <?php if (

                    ($pendingInvestment['payment_status'] ?? '')

                    === 'awaiting_payment'

                ): ?>

                    <a
                        href="crypto-payment.php?investment=<?= urlencode(

                                                                $pendingInvestment['id']

                                                            ) ?>"
                        class="continue-investment-btn">

                        Continue Payment

                        <i class="bi bi-arrow-right"></i>

                    </a>


                    <button
                        type="button"
                        class="cancel-investment-btn"
                        id="cancelInvestmentBtn"
                        data-investment-id="<?= htmlspecialchars(

                                                $pendingInvestment['id'],

                                                ENT_QUOTES,

                                                'UTF-8'

                                            ) ?>">

                        <i class="bi bi-x-circle"></i>

                        Cancel Investment

                    </button>

                <?php elseif (

                    ($pendingInvestment['payment_status'] ?? '')

                    === 'payment_submitted'

                ): ?>

                    <div class="payment-review-status">

                        <i class="bi bi-hourglass-split"></i>

                        Payment Under Review

                    </div>

                <?php endif; ?>

            </div>
            <!-- PACKAGE GRID -->

            <div class="row g-4">

                <?php foreach ($packages as $package): ?>

                    <?php

                    $amount = $package['amount'];

                    $roi = $amount * ($package['roi'] / 100);

                    $total = $amount + $roi;

                    ?>

                    <div class="col-md-6 col-lg-4 col-xl-3">

                        <div
                            class="investment-card
                        <?= $amount === 1000 ? 'featured-package' : '' ?>">

                            <?php if ($amount === 1000): ?>

                                <div class="popular-badge">

                                    Popular

                                </div>

                            <?php endif; ?>

                            <div class="package-icon">

                                <i class="bi bi-graph-up-arrow"></i>

                            </div>

                            <span class="package-level">

                                Level <?= $package['level'] ?>

                            </span>

                            <h2>

                                <?= number_format($amount) ?>

                                <small>USDT</small>

                            </h2>

                            <div class="package-details">

                                <div>

                                    <span>ROI</span>

                                    <strong>

                                        <?= $package['roi'] ?>%

                                    </strong>

                                </div>

                                <div>

                                    <span>Expected ROI</span>

                                    <strong>

                                        <?= number_format($roi) ?> USDT

                                    </strong>

                                </div>

                                <div>

                                    <span>Investment Cycle</span>

                                    <strong>

                                        15 Days

                                    </strong>

                                </div>

                            </div>

                            <button
                                class="btn select-package-btn"
                                data-level="<?= $package['level'] ?>"
                                data-amount="<?= $amount ?>"
                                data-roi="<?= $package['roi'] ?>"
                                data-profit="<?= $roi ?>"
                                data-total="<?= $total ?>">

                                Select Package

                                <i class="bi bi-arrow-right"></i>

                            </button>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        </div>

    </div>


    <!-- PACKAGE CONFIRMATION MODAL -->

    <div
        class="modal fade"
        id="packageModal"
        tabindex="-1">

        <div class="modal-dialog modal-dialog-centered">

            <div class="modal-content">

                <div class="modal-header">

                    <h5 class="modal-title">

                        Confirm Investment Package

                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal">

                    </button>

                </div>

                <div class="modal-body">

                    <div class="selected-package">

                        <span>

                            Selected Package

                        </span>

                        <strong id="selectedLevel">

                            Level 1

                        </strong>

                    </div>

                    <div class="selected-amount">

                        <span>

                            Investment Amount

                        </span>

                        <strong id="selectedAmount">

                            50 USDT

                        </strong>

                    </div>

                    <div class="selected-details">

                        <div>

                            <span>ROI</span>

                            <strong id="selectedROI">

                                10
                                %

                            </strong>

                        </div>

                        <div>

                            <span>Expected ROI</span>

                            <strong id="selectedProfit">

                                10 USDT

                            </strong>

                        </div>

                        <div>

                            <span>Total Return</span>

                            <strong id="selectedTotal">

                                60 USDT

                            </strong>

                        </div>

                    </div>

                    <div class="modal-warning">

                        <i class="bi bi-info-circle"></i>

                        <span>

                            Your investment will remain pending until your crypto payment is verified.

                        </span>

                    </div>

                </div>

                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal">

                        Cancel

                    </button>

                    <button
                        type="button"
                        class="btn btn-primary"
                        id="continueInvestment">

                        Continue to Payment

                        <i class="bi bi-arrow-right"></i>

                    </button>

                </div>

            </div>

        </div>

    </div>


    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
    </script>
    <script src="assets/js/notifications.js"></script>
    <script src="assets/js/invest.js"></script>
    <div
        class="money-confirm-overlay"
        id="confirmModal">

        <div class="money-confirm-modal">

            <div class="confirm-modal-icon">

                <i class="bi bi-exclamation-triangle"></i>

            </div>


            <h4 id="confirmModalTitle">

                Confirm Action

            </h4>


            <p id="confirmModalMessage">

                Are you sure?

            </p>


            <div class="confirm-modal-actions">

                <button

                    type="button"

                    class="confirm-cancel-btn"

                    id="confirmCancelBtn">

                    No, Go Back

                </button>


                <button

                    type="button"

                    class="confirm-yes-btn"

                    id="confirmYesBtn">

                    Yes, Continue

                </button>

            </div>

        </div>

    </div>
</body>

</html>