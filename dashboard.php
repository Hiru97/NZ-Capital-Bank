<?php
require 'config.php';

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user']['id'];

try {
    // Get account details with proper error handling
    $stmt = $conn->prepare("SELECT * FROM accounts WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $account = $stmt->fetch();

    if (!$account) {
        throw new Exception("Account not found - please contact support");
    }

    // Get recent transactions using proper column name
    $txn_stmt = $conn->prepare("SELECT * FROM transactions 
                              WHERE account_id = ?
                              ORDER BY created_at DESC LIMIT 5");
    $txn_stmt->execute([$account['id']]);
    $transactions = $txn_stmt->fetchAll();

    // Get interest rates
    $rates_stmt = $conn->query("SELECT * FROM rates");
    $rates = $rates_stmt->fetchAll();

} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
} catch(Exception $e) {
    die($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NZ Capital Bank - Dashboard</title>
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
    <nav>
        <div class="logo">
            <img src="./assets/logo.jpeg" alt="NZ Capital Bank">
            <h1 style="color: white;">NZ Capital Bank</h1>
        </div>
        <div class="nav-links">
            <span class="account-info">
                A/C: <?= htmlspecialchars($_SESSION['user']['account_number']) ?>
            </span>
            <a href="dashboard.php">Home</a>
            <a href="accounts.php">Accounts</a>
            <a href="transfer_full.php">Transfer</a>
            <a href="pay_bill.php">Bill Payment</a>
            <a href="card.php">Credit Card Payment</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="hero" id="hero"></div>

    <div class="dashboard-grid">
        <div class="card balance-card">
            <h2>Account Summary</h2>
            <div class="account-details">
                <p>Account Number: <?= htmlspecialchars($account['account_number']) ?></p>
                <div class="balance">NZ$ <?= number_format($account['balance'], 2) ?></div>
            </div>
        </div>

        <div class="card">
            <h2>Quick Fund Transfer</h2>
            <form action="transfer.php" method="POST">
                <input type="text" name="account_number" placeholder="Recipient Account" required>
                <input type="number" name="amount" step="0.01" placeholder="Amount" required>
                <button type="submit">Transfer Funds</button>
            </form>
        </div>

        <div class="card">
            <h2>Recent Transactions</h2>
            <?php if (!empty($transactions)): ?>
                <div class="transaction-list">
                    <?php foreach($transactions as $txn): ?>
                        <div class="transaction-item">
                            <span class="txn-date"><?= date('d M Y', strtotime($txn['created_at'])) ?></span>
                            <span class="txn-amount">NZ$ <?= number_format($txn['amount'], 2) ?></span>
                            <span class="txn-desc"><?= htmlspecialchars($txn['description']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No recent transactions</p>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>Current Rates</h2>
            <div class="rates-list">
                <?php foreach($rates as $rate): ?>
                    <div class="rate-item">
                        <span><?= htmlspecialchars($rate['type']) ?></span>
                        <span><?= htmlspecialchars($rate['rate']) ?>%</span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>