<?php
require 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user']['id'];

try {
    // Fetch user details
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    // Fetch account details
    $stmt = $conn->prepare("SELECT * FROM accounts WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $account = $stmt->fetch();

    if (!$user || !$account) {
        throw new Exception("User or account not found!");
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
} catch (Exception $e) {
    die($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NZ Capital Bank - My Account</title>
    <link rel="stylesheet" href="dashboard.css"> <!-- Reuse the same CSS -->
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

    <div class="dashboard-grid">
        <!-- User Details Card -->
        <div class="card">
            <h2>Personal Information</h2>
            <div class="details">
                <p><strong>Full Name:</strong> <?= htmlspecialchars($user['full_name']) ?></p>
                <p><strong>Username:</strong> <?= htmlspecialchars($user['username']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                <p><strong>Mobile:</strong> <?= htmlspecialchars($user['mobile']) ?></p>
                <p><strong>NIC:</strong> <?= htmlspecialchars($user['nic']) ?></p>
            </div>
        </div>

        <!-- Account Details Card -->
        <div class="card">
            <h2>Account Information</h2>
            <div class="details">
                <p><strong>Account Number:</strong> <?= htmlspecialchars($account['account_number']) ?></p>
                <p><strong>Account Balance:</strong> NZ$ <?= number_format($account['balance'], 2) ?></p>
                <p><strong>Account Created:</strong> <?= date('d M Y', strtotime($account['created_at'])) ?></p>
            </div>
        </div>
    </div>
</body>
</html>