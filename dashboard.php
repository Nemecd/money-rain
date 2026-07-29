<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/session.php';
requireAuth();

$userId = currentUserId();

// --- pull the logged-in user's profile (service role key — server-side only) ---
$profileRes = $supabase->selectRow(
    'profiles',
    'id=eq.' . urlencode($userId) . '&select=full_name,username,wallet_balance,referral_code,created_at'
);
$profile = $profileRes['body'][0] ?? null;

// --- count how many people this user referred ---
$referralsRes = $supabase->selectRow(
    'profiles',
    'referred_by=eq.' . urlencode($userId) . '&select=id'
);
$referralCount = is_array($referralsRes['body'] ?? null) ? count($referralsRes['body']) : 0;

$fullName      = $profile['full_name'] ?? 'Investor';
$firstName     = trim(explode(' ', $fullName)[0]);
$walletBalance = (float) ($profile['wallet_balance'] ?? 0);
$referralCode  = $profile['referral_code'] ?? '—';

// Fields below aren't backed by a real feature yet (no investment/VIP
// system built). Shown as static placeholders until those are built.
$investmentReturns = 0.00;
$vipPlan            = 'Inactive';
$accountStatus      = 'Standard';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard | Money Rain</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
  <link rel="stylesheet" href="assets/css/style.css" />
  <link rel="stylesheet" href="assets/css/dashboard.css" />
</head>
<body class="dashboard-body">

  <header class="dash-header">
    <div class="container dash-header-inner">
      <a href="dashboard.php" class="dash-logo">
        <div class="logo-circle">MR</div>
        <span>Money Rain</span>
      </a>

      <div class="dash-header-actions">
        <span class="dash-welcome d-none d-sm-inline">Welcome back, <?= htmlspecialchars($firstName) ?></span>
        <button id="logoutBtn" class="btn dash-logout-btn">
          <i class="bi bi-box-arrow-right"></i> Logout
        </button>
      </div>
    </div>
  </header>

  <main class="container dash-main">

    <!-- QUICK ACTIONS -->
    <h5 class="dash-section-title">Quick Actions</h5>
    <div class="row g-3 dash-actions-grid">

      <div class="col-6 col-md-3">
        <a href="tasks.php" class="dash-tile tile-tasks">
          <i class="bi bi-play-circle-fill"></i>
          <span class="tile-title">Tasks</span>
          <span class="tile-sub">Earn rewards</span>
        </a>
      </div>

      <div class="col-6 col-md-3">
        <a href="invest.php" class="dash-tile tile-invest">
          <i class="bi bi-graph-up-arrow"></i>
          <span class="tile-title">Invest Funds</span>
          <span class="tile-sub">Grow your income</span>
        </a>
      </div>

      <div class="col-6 col-md-3">
        <a href="withdraw.php" class="dash-tile tile-withdraw">
          <i class="bi bi-arrow-up-circle-fill"></i>
          <span class="tile-title">Withdraw</span>
          <span class="tile-sub">Transfer earnings</span>
        </a>
      </div>

      <div class="col-6 col-md-3">
        <a href="referrals.php" class="dash-tile tile-referrals">
          <i class="bi bi-people-fill"></i>
          <span class="tile-title">Referrals</span>
          <span class="tile-sub">Invite & earn</span>
        </a>
      </div>

      <div class="col-6 col-md-3">
        <a href="deposit.php" class="dash-tile tile-deposit">
          <i class="bi bi-plus-circle-fill"></i>
          <span class="tile-title">Deposit</span>
          <span class="tile-sub">Fund account</span>
        </a>
      </div>

      <div class="col-6 col-md-3">
        <a href="vip.php" class="dash-tile tile-vip">
          <i class="bi bi-award-fill"></i>
          <span class="tile-title">VIP Plans</span>
          <span class="tile-sub">Upgrade plans</span>
        </a>
      </div>

      <div class="col-6 col-md-3">
        <a href="wallet.php" class="dash-tile tile-wallet">
          <i class="bi bi-wallet2"></i>
          <span class="tile-title">Wallet</span>
          <span class="tile-sub">View balances</span>
        </a>
      </div>

      <div class="col-6 col-md-3">
        <a href="support.php" class="dash-tile tile-support">
          <i class="bi bi-headset"></i>
          <span class="tile-title">Support</span>
          <span class="tile-sub">Get help</span>
        </a>
      </div>

    </div>

    <!-- REFERRAL CODE STRIP -->
    <div class="dash-referral-strip">
      <div>
        <div class="referral-label"><i class="bi bi-share-fill"></i> Your referral code</div>
        <div class="referral-code"><?= htmlspecialchars($referralCode) ?></div>
      </div>
      <button class="btn dash-copy-btn" id="copyReferralBtn" data-code="<?= htmlspecialchars($referralCode) ?>">
        Copy
      </button>
    </div>

    <!-- STATS -->
    <div class="row g-3 dash-stats-row">
      <div class="col-12 col-md-4">
        <div class="dash-stat-card">
          <div class="stat-icon"><i class="bi bi-cash-coin"></i></div>
          <div>
            <div class="stat-label">Investment Returns</div>
            <div class="stat-sub">From matured plans only</div>
            <div class="stat-value">₦<?= number_format($investmentReturns, 2) ?></div>
          </div>
        </div>
      </div>

      <div class="col-12 col-md-4">
        <div class="dash-stat-card">
          <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
          <div>
            <div class="stat-label">Total Referrals</div>
            <div class="stat-value"><?= (int) $referralCount ?></div>
          </div>
        </div>
      </div>

      <div class="col-12 col-md-4">
        <div class="dash-stat-card">
          <div class="stat-icon"><i class="bi bi-shield-check"></i></div>
          <div>
            <div class="stat-label">Account Status</div>
            <div class="stat-value"><?= htmlspecialchars($accountStatus) ?></div>
          </div>
        </div>
      </div>
    </div>

    <!-- ACCOUNT SNAPSHOT -->
    <div class="dash-snapshot">
      <h5 class="dash-section-title">Account Snapshot</h5>

      <div class="snapshot-row">
        <span>Wallet balance</span>
        <strong>₦<?= number_format($walletBalance, 2) ?></strong>
      </div>
      <div class="snapshot-row">
        <span>Total investment returns</span>
        <strong>₦<?= number_format($investmentReturns, 2) ?></strong>
      </div>
      <div class="snapshot-row">
        <span>Referral status</span>
        <strong><?= $referralCount > 0 ? 'Active' : 'Not yet active' ?></strong>
      </div>
      <div class="snapshot-row">
        <span>VIP plan</span>
        <strong><?= htmlspecialchars($vipPlan) ?></strong>
      </div>

      <a href="activity.php" class="btn dash-activity-btn w-100">View activity log</a>
    </div>

  </main>

  <script>
    document.getElementById('logoutBtn').addEventListener('click', async () => {
      const res = await fetch('api/logout.php', { method: 'POST' });
      const data = await res.json();
      if (data.redirect) window.location.href = data.redirect;
    });

    document.getElementById('copyReferralBtn').addEventListener('click', (e) => {
      const code = e.target.dataset.code;
      navigator.clipboard.writeText(code).then(() => {
        e.target.textContent = 'Copied!';
        setTimeout(() => (e.target.textContent = 'Copy'), 1500);
      });
    });
  </script>

</body>
</html>