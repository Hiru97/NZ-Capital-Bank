<?php
require 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user']['id'];
$error = '';
$success = '';

// Fetch sender's account details
try {
    $stmt = $conn->prepare("SELECT * FROM accounts WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $sender_account = $stmt->fetch();

    if (!$sender_account) {
        throw new Exception("Your account was not found. Please contact support.");
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
} catch (Exception $e) {
    die($e->getMessage());
}

// Fetch credit card bill payment history
try {
    $stmt = $conn->prepare("SELECT * FROM transactions 
                          WHERE account_id = ? AND type = 'credit_card_payment'
                          ORDER BY created_at DESC");
    $stmt->execute([$sender_account['id']]);
    $credit_card_payments = $stmt->fetchAll();
} catch (PDOException $e) {
    $credit_card_payments = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $credit_card_number = trim($_POST['credit_card_number']);
    $amount = (float)$_POST['amount'];
    $remark = trim($_POST['remark']);

    // Validate input
    if (empty($credit_card_number)) {
        $error = "Credit card number is required.";
    } elseif ($amount <= 0) {
        $error = "Amount must be greater than 0.";
    } elseif ($amount > $sender_account['balance']) {
        $error = "Insufficient funds. Your available balance is NZ$ " . number_format($sender_account['balance'], 2);
    } else {
        try {
            $conn->beginTransaction();

            // Deduct amount from sender
            $stmt = $conn->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ?");
            $stmt->execute([$amount, $sender_account['id']]);

            // Record credit card payment transaction
            $stmt = $conn->prepare("INSERT INTO transactions 
                (account_id, type, amount, description, sender_remark)
                VALUES (?, 'credit_card_payment', ?, ?, ?)");
            $stmt->execute([
                $sender_account['id'],
                $amount,
                "Credit card payment for card ending with " . substr($credit_card_number, -4),
                $remark
            ]);

            $conn->commit();
            $success = "Credit card payment successful! NZ$ " . number_format($amount, 2) . " paid for card ending with " . substr($credit_card_number, -4) . ".";
        } catch (PDOException $e) {
            $conn->rollBack();
            $error = "Credit card payment failed due to a system error. Please try again later.";
        } catch (Exception $e) {
            $conn->rollBack();
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NZ Capital Bank - Pay Credit Card Bill</title>
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
            <a href="transfer.php">Transfer</a>
            <a href="pay_bill.php">Pay Bill</a>
            <a href="card.php">Credit Card Payment</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="dashboard-grid">
        <!-- Pay Credit Card Bill Card -->
        <div class="card">
            <h2>Pay Credit Card Bill</h2>
            <?php if ($error): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success-message"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <form action="card.php" method="POST">
                <div class="form-group">
                    <label for="credit_card_number">Credit Card Number:</label>
                    <input type="text" id="credit_card_number" name="credit_card_number" placeholder="Enter credit card number" required>
                </div>
                <div class="form-group">
                    <label for="amount">Amount (NZ$):</label>
                    <input type="number" id="amount" name="amount" step="0.01" placeholder="Enter amount" required>
                </div>
                <div class="form-group">
                    <label for="remark">Remark:</label>
                    <textarea id="remark" name="remark" placeholder="Enter remark (optional)"></textarea>
                </div>
                <button type="submit" class="transfer-button">Pay Credit Card Bill</button>
            </form>
        </div>

        <!-- Sender Account Summary -->
        <div class="card">
            <h2>Your Account Summary</h2>
            <div class="details">
                <p><strong>Account Number:</strong> <?= htmlspecialchars($sender_account['account_number']) ?></p>
                <p><strong>Available Balance:</strong> NZ$ <?= number_format($sender_account['balance'], 2) ?></p>
            </div>
        <br>
        <br>
        <br>

        <!-- Credit Card Payment History -->
        <div class="card">
            <h2>Credit Card Payment History</h2>
            <?php if (!empty($credit_card_payments)): ?>
                <table class="transaction-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Amount (NZ$)</th>
                            <th>Description</th>
                            <th>Remark</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($credit_card_payments as $payment): ?>
                            <tr>
                                <td><?= date('d M Y H:i', strtotime($payment['created_at'])) ?></td>
                                <td><?= ucfirst($payment['type']) ?></td>
                                <td><?= number_format($payment['amount'], 2) ?></td>
                                <td><?= htmlspecialchars($payment['description']) ?></td>
                                <td><?= htmlspecialchars($payment['sender_remark']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No credit card payments found.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>